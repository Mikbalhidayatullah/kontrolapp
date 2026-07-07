<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->resizeEmployeeStatusColumn(20);
        $this->normalizePppkGrades();
    }

    private function normalizePppkGrades(): void
    {
        DB::table('perjadin_entries')
            ->where('employee_status', 'PPPK')
            ->orderBy('id')
            ->select(['id', 'grade'])
            ->chunkById(100, function ($entries): void {
                foreach ($entries as $entry) {
                    preg_match('/\d+/', (string) $entry->grade, $matches);
                    $gradeNumber = $matches[0] ?? null;

                    if ($gradeNumber && $gradeNumber !== $entry->grade) {
                        DB::table('perjadin_entries')
                            ->where('id', $entry->id)
                            ->update(['grade' => $gradeNumber]);
                    }
                }
            });
    }

    public function down(): void
    {
        $this->resizeEmployeeStatusColumn(10);
    }

    private function resizeEmployeeStatusColumn(int $length): void
    {
        if (! Schema::hasColumn('perjadin_entries', 'employee_status')) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE perjadin_entries MODIFY employee_status VARCHAR({$length}) NULL"),
            'pgsql' => DB::statement("ALTER TABLE perjadin_entries ALTER COLUMN employee_status TYPE VARCHAR({$length})"),
            'sqlsrv' => DB::statement("ALTER TABLE perjadin_entries ALTER COLUMN employee_status NVARCHAR({$length}) NULL"),
            default => null,
        };
    }
};
