<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lrfk_entries') || ! Schema::hasColumn('lrfk_entries', 'dataset_version')) {
            return;
        }

        DB::transaction(function (): void {
            $entryIds = DB::table('lrfk_entries')
                ->where('dataset_version', 'perubahan')
                ->pluck('id');

            if ($entryIds->isEmpty()) {
                return;
            }

            if (Schema::hasTable('perjadin_entries') && Schema::hasColumn('perjadin_entries', 'lrfk_entry_id')) {
                DB::table('perjadin_entries')
                    ->whereIn('lrfk_entry_id', $entryIds)
                    ->update(['lrfk_entry_id' => null]);
            }

            if (Schema::hasTable('lrfk_entry_details')) {
                DB::table('lrfk_entry_details')
                    ->whereIn('lrfk_entry_id', $entryIds)
                    ->delete();
            }

            DB::table('lrfk_entries')
                ->where('dataset_version', 'perubahan')
                ->where('level', 'rekening')
                ->delete();

            DB::table('lrfk_entries')
                ->where('dataset_version', 'perubahan')
                ->delete();
        });
    }

    public function down(): void
    {
        // Dataset rapat sengaja tidak dipulihkan karena penghapusan ini bersifat permanen.
    }
};
