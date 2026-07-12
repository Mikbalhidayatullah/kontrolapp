<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lrfk_entries', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('lrfk_entries')
                ->nullOnDelete();
        });

        $currentSubKegiatanId = null;

        DB::table('lrfk_entries')
            ->select(['id', 'level'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function ($entry) use (&$currentSubKegiatanId): void {
                if ($entry->level === 'sub_kegiatan') {
                    $currentSubKegiatanId = $entry->id;

                    return;
                }

                if ($entry->level !== 'rekening') {
                    return;
                }

                DB::table('lrfk_entries')
                    ->where('id', $entry->id)
                    ->update(['parent_id' => $currentSubKegiatanId]);
            });
    }

    public function down(): void
    {
        Schema::table('lrfk_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
