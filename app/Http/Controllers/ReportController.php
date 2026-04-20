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
            $entries = PerjadinEntry::query()->latest('submission_date')->latest()->get();

            return view('report', [
                'title' => 'Report',
                'isVerifikator' => true,
                'cards' => [
                    ['label' => 'Perjadin Diverifikasi', 'value' => $entries->where('status', 'Terverifikasi')->count().' Dokumen', 'note' => 'Dokumen yang sudah lolos verifikasi'],
                    ['label' => 'Butuh Revisi', 'value' => $entries->where('status', 'Butuh Revisi Bukti')->count().' Dokumen', 'note' => 'Perlu kelengkapan bukti atau catatan'],
                    ['label' => 'Nominal Perjadin', 'value' => 'Rp '.number_format((int) $entries->sum('budget_amount'), 0, ',', '.'), 'note' => 'Akumulasi semua budget perjadin'],
                ],
                'rows' => $entries->take(8)->map(fn (PerjadinEntry $entry) => [
                    'periode' => optional($entry->submission_date)->translatedFormat('d M Y'),
                    'kategori' => $entry->destination_city.' / '.$entry->transport_type,
                    'nominal' => 'Rp '.number_format((int) $entry->budget_amount, 0, ',', '.'),
                    'status' => $entry->status,
                ])->all(),
            ]);
        }

        $controlEntries = ControlEntry::query()->latest('entry_date')->latest()->get();
        $perjadinEntries = PerjadinEntry::query()->latest('submission_date')->latest()->get();

        return view('report', [
            'title' => 'Report',
            'isVerifikator' => false,
            'cards' => [
                ['label' => 'Total Pengeluaran', 'value' => 'Rp '.number_format((int) $controlEntries->sum('amount_out'), 0, ',', '.'), 'note' => 'Akumulasi semua dana keluar'],
                ['label' => 'Dana Masuk', 'value' => 'Rp '.number_format((int) $controlEntries->sum('amount_in'), 0, ',', '.'), 'note' => 'Akumulasi dana yang diterima'],
                ['label' => 'Total Perjadin', 'value' => 'Rp '.number_format((int) $perjadinEntries->sum('budget_amount'), 0, ',', '.'), 'note' => 'Total nominal data perjadin'],
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
