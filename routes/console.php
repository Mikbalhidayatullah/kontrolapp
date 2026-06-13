<?php

use App\Models\PerjadinEntry;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('perjadin:fix-lodging-lumpsum {--apply : Simpan perubahan ke database}', function () {
    $entries = PerjadinEntry::query()
        ->where('lodging_enabled', true)
        ->where('lodging_has_receipt', false)
        ->orderBy('id')
        ->get();

    $changes = [];

    foreach ($entries as $entry) {
        $lodgingRate = (int) round(max((int) $entry->lodging_rate, 0) * 0.3);
        $lodgingTotal = (int) $entry->lodging_nights * $lodgingRate;
        $grandTotal = (int) $entry->daily_allowance_total
            + (int) $entry->representation_total
            + (int) $entry->ticket_total
            + $lodgingTotal
            + (int) $entry->local_transport_total
            + (int) $entry->other_cost_amount;

        if ((int) $entry->lodging_total === $lodgingTotal && (int) $entry->grand_total === $grandTotal) {
            continue;
        }

        $changes[] = [
            'entry' => $entry,
            'lodging_rate' => $lodgingRate,
            'lodging_total' => $lodgingTotal,
            'grand_total' => $grandTotal,
        ];
    }

    if ($changes === []) {
        $this->info('Tidak ada data perjadin lumpsum penginapan yang perlu diperbaiki.');

        return self::SUCCESS;
    }

    $this->table(
        ['ID', 'Pelaksana', 'Penginapan Lama', 'Penginapan Baru', 'Grand Total Lama', 'Grand Total Baru'],
        collect($changes)->map(fn (array $change) => [
            $change['entry']->id,
            $change['entry']->executor_name,
            'Rp '.number_format((int) $change['entry']->lodging_total, 0, ',', '.'),
            'Rp '.number_format($change['lodging_total'], 0, ',', '.'),
            'Rp '.number_format((int) $change['entry']->grand_total, 0, ',', '.'),
            'Rp '.number_format($change['grand_total'], 0, ',', '.'),
        ])->all()
    );

    if (! $this->option('apply')) {
        $this->warn('Dry-run saja. Jalankan dengan --apply untuk menyimpan perubahan.');

        return self::SUCCESS;
    }

    DB::transaction(function () use ($changes): void {
        foreach ($changes as $change) {
            $change['entry']->forceFill([
                'lodging_total' => $change['lodging_total'],
                'grand_total' => $change['grand_total'],
            ])->save();
        }
    });

    $this->info(count($changes).' data perjadin berhasil diperbaiki.');

    return self::SUCCESS;
})->purpose('Perbaiki total penginapan lumpsum 30% pada data perjadin lama.');
