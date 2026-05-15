<?php

namespace App\Http\Controllers;

use App\Models\ControlEntry;
use App\Models\PerjadinEntry;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $isVerifikator = $user->role === 'verifikator';

        if ($isVerifikator) {
            $entries = PerjadinEntry::query()->latest('assignment_date')->latest('start_date')->latest()->get();

            return view('report', [
                'title' => 'Report',
                'isVerifikator' => true,
                'lastColumnLabel' => 'No Surat Tugas',
                'cards' => [
                    ['label' => 'Total Perjadin', 'value' => $entries->count().' Dokumen', 'note' => 'Seluruh data perjadin yang tersimpan'],
                    ['label' => 'Kategori Aktif', 'value' => $entries->pluck('category')->unique()->count().' Kategori', 'note' => 'Kategori yang sudah terisi data'],
                    ['label' => 'Nominal Perjadin', 'value' => 'Rp '.number_format((int) $entries->sum('grand_total'), 0, ',', '.'), 'note' => 'Akumulasi grand total seluruh perjadin'],
                ],
                'rows' => $entries->take(8)->map(fn (PerjadinEntry $entry) => [
                    'periode' => optional($entry->assignment_date)->translatedFormat('d M Y'),
                    'kategori' => $entry->category.' / '.$entry->destination_city,
                    'nominal' => 'Rp '.number_format((int) $entry->grand_total, 0, ',', '.'),
                    'status' => $entry->assignment_number,
                ])->all(),
            ]);
        }

        $controlEntries = ControlEntry::query()->latest('entry_date')->latest()->get();
        $perjadinEntries = PerjadinEntry::query()->latest('assignment_date')->latest('start_date')->latest()->get();

        return view('report', [
            'title' => 'Report',
            'isVerifikator' => false,
            'lastColumnLabel' => 'Status',
            'cards' => [
                ['label' => 'Total Pengeluaran', 'value' => 'Rp '.number_format((int) $controlEntries->sum('amount_out'), 0, ',', '.'), 'note' => 'Akumulasi semua dana keluar'],
                ['label' => 'Dana Masuk', 'value' => 'Rp '.number_format((int) $controlEntries->sum('amount_in'), 0, ',', '.'), 'note' => 'Akumulasi dana yang diterima'],
                ['label' => 'Total Perjadin', 'value' => 'Rp '.number_format((int) $perjadinEntries->sum('grand_total'), 0, ',', '.'), 'note' => 'Total nominal data perjadin'],
            ],
            'rows' => $controlEntries->take(8)->map(fn (ControlEntry $entry) => [
                'periode' => optional($entry->entry_date)->translatedFormat('d M Y'),
                'kategori' => $entry->fund_source,
                'nominal' => 'Rp '.number_format((int) max($entry->amount_out, $entry->amount_in), 0, ',', '.'),
                'status' => $entry->status,
            ])->all(),
        ]);
    }
}
