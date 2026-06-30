<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('perjadin_entries', 'employee_status')) {
            return;
        }

        DB::table('perjadin_entries')
            ->select(['id', 'grade'])
            ->orderBy('id')
            ->chunkById(100, function ($entries): void {
                foreach ($entries as $entry) {
                    DB::table('perjadin_entries')
                        ->where('id', $entry->id)
                        ->update([
                            'employee_status' => $this->employeeStatusForGrade((string) $entry->grade),
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function employeeStatusForGrade(string $grade): string
    {
        preg_match('/\d+/', trim($grade), $matches);
        $gradeNumber = isset($matches[0]) ? (int) $matches[0] : 0;

        return $gradeNumber >= 6 ? 'PPPK' : 'PNS';
    }
};
