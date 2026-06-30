<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('perjadin_entries', 'employee_status')) {
                $table->string('employee_status', 10)->nullable()->after('executor_name');
            }
        });

        $this->resizeGradeColumn(3);
        $this->backfillEmployeeStatus();
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            if (Schema::hasColumn('perjadin_entries', 'employee_status')) {
                $table->dropColumn('employee_status');
            }
        });
    }

    private function resizeGradeColumn(int $length): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE perjadin_entries MODIFY grade VARCHAR({$length}) NOT NULL"),
            'pgsql' => DB::statement("ALTER TABLE perjadin_entries ALTER COLUMN grade TYPE VARCHAR({$length})"),
            'sqlsrv' => DB::statement("ALTER TABLE perjadin_entries ALTER COLUMN grade NVARCHAR({$length}) NOT NULL"),
            default => null,
        };
    }

    private function backfillEmployeeStatus(): void
    {
        DB::table('perjadin_entries')
            ->select(['id', 'grade'])
            ->orderBy('id')
            ->chunkById(100, function ($entries): void {
                foreach ($entries as $entry) {
                    $grade = trim((string) $entry->grade);

                    DB::table('perjadin_entries')
                        ->where('id', $entry->id)
                        ->whereNull('employee_status')
                        ->update([
                            'employee_status' => $this->employeeStatusForGrade($grade),
                        ]);
                }
            });
    }

    private function employeeStatusForGrade(string $grade): string
    {
        preg_match('/\d+/', $grade, $matches);
        $gradeNumber = isset($matches[0]) ? (int) $matches[0] : 0;

        return $gradeNumber >= 6 ? 'PPPK' : 'PNS';
    }
};
