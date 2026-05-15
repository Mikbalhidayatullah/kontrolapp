<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('perjadin_entries')
            ->whereIn('category', ['Perjadin Luar Provinsi', 'Perjadin Luar Kota Dalam Provinsi'])
            ->update(['category' => 'Perjadin Luar Daerah']);

        DB::table('perjadin_entries')
            ->where('category', 'Perjadin Dalam Kota')
            ->update(['category' => 'Perjadin Dalam Daerah']);
    }

    public function down(): void
    {
        DB::table('perjadin_entries')
            ->where('category', 'Perjadin Luar Daerah')
            ->update(['category' => 'Perjadin Luar Provinsi']);

        DB::table('perjadin_entries')
            ->where('category', 'Perjadin Dalam Daerah')
            ->update(['category' => 'Perjadin Dalam Kota']);
    }
};
