<?php

namespace App\Services;

use App\Models\PerjadinEntry;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class PerjadinBpkExcelExporter
{
    private const SHEETS = [
        [
            'name' => 'Perjadin Luar Provinsi',
            'category' => 'Perjadin Luar Daerah',
            'scope' => null,
        ],
        [
            'name' => 'Perjadin Luar Kota Dalam Prov',
            'category' => 'Perjadin Dalam Daerah',
            'scope' => 'luar_kota_sofifi',
        ],
        [
            'name' => 'Perjadin dalam Kota',
            'category' => 'Perjadin Dalam Daerah',
            'scope' => 'dalam_kota_sofifi',
        ],
    ];

    private const COLUMN_COUNT = 41;

    private const MONEY_COLUMNS = [
        11, 12, 14, 15, 18, 19, 20, 28, 29, 31, 32, 33, 34, 35, 36, 37, 38,
    ];

    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function export(Collection $entries): string
    {
        $directory = storage_path('app/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder export tidak bisa dibuat.');
        }

        $path = $directory.'/perjadin-bpk-'.now()->format('Ymd-His').'-'.uniqid().'.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File Excel BPK tidak bisa dibuat.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        foreach (self::SHEETS as $index => $sheet) {
            $sheetEntries = $this->filterEntries($entries, $sheet['category'], $sheet['scope']);
            $zip->addFromString(
                'xl/worksheets/sheet'.($index + 1).'.xml',
                $this->worksheetXml($sheet['name'], $sheetEntries, $this->periodLabel($sheetEntries))
            );
        }

        $zip->close();

        return $path;
    }

    private function filterEntries(Collection $entries, string $category, ?string $scope): Collection
    {
        return $entries
            ->filter(function (PerjadinEntry $entry) use ($category, $scope): bool {
                if ($entry->category !== $category) {
                    return false;
                }

                return $scope === null || $entry->regional_trip_scope === $scope;
            })
            ->sortBy([
                ['start_date', 'asc'],
                ['assignment_date', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
    }

    private function worksheetXml(string $sheetName, Collection $entries, string $periodLabel): string
    {
        $rowNumber = 1;
        $rows = [];
        $merges = [];
        $highestColumn = $this->columnName(self::COLUMN_COUNT);

        $rows[] = $this->rowXml($rowNumber, $this->filledRowCells(['REKAPITULASI BELANJA PERJALANAN DINAS'], 1), 24);
        $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
        $rowNumber++;

        $rows[] = $this->rowXml($rowNumber, $this->filledRowCells([$periodLabel], 2), 20);
        $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
        $rowNumber++;

        $rows[] = $this->rowXml($rowNumber, $this->filledRowCells(['PROVINSI MALUKU UTARA'], 1), 22);
        $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
        $rowNumber++;

        $rows[] = $this->rowXml($rowNumber, $this->filledRowCells(['DIMOHON UNTUK TIDAK MENGGUBAH FORMAT'], 3), 20);
        $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
        $rowNumber++;

        $rows[] = $this->rowXml($rowNumber++, $this->blankCells());

        if ($entries->isEmpty()) {
            $rows[] = $this->rowXml($rowNumber, $this->filledRowCells(['BELUM ADA DATA '.$sheetName], 4), 22);
            $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
            $rowNumber++;
            [$headerRows, $headerMerges, $nextRow] = $this->headerRows($rowNumber);
            $rows = array_merge($rows, $headerRows);
            $merges = array_merge($merges, $headerMerges);
            $rowNumber = $nextRow;
        } else {
            $sequence = 1;
            $entries
                ->groupBy(fn (PerjadinEntry $entry) => optional($entry->start_date)->format('Y-m') ?: 'tanpa-tanggal')
                ->each(function (Collection $monthEntries, string $monthKey) use (&$rowNumber, &$rows, &$merges, &$sequence, $highestColumn): void {
                    $rows[] = $this->rowXml($rowNumber, $this->filledRowCells([$this->monthGroupLabel($monthKey, $monthEntries)], 4), 22);
                    $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
                    $rowNumber++;

                    [$headerRows, $headerMerges, $nextRow] = $this->headerRows($rowNumber);
                    $rows = array_merge($rows, $headerRows);
                    $merges = array_merge($merges, $headerMerges);
                    $rowNumber = $nextRow;

                    foreach ($monthEntries as $entry) {
                        $rows[] = $this->rowXml($rowNumber++, $this->entryCells($entry, $sequence++), 24);
                    }

                    $rows[] = $this->rowXml($rowNumber++, $this->blankCells());
                });
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="18"/>';
        $xml .= $this->columnsXml();
        $xml .= '<sheetData>'.implode('', $rows).'</sheetData>';

        if ($merges !== []) {
            $xml .= '<mergeCells count="'.count($merges).'">';
            foreach ($merges as $merge) {
                $xml .= '<mergeCell ref="'.$merge.'"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>';
        $xml .= '<pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private function headerRows(int $rowNumber): array
    {
        $top = [
            'No.',
            'Nama SKPD',
            'Nama Lengkap Pelaksana Tanpa Gelar',
            'Jabatan/Golongan',
            'Jangka Waktu Surat Perintah Tugas',
            '', '', '', '', '',
            'Uang Harian',
            '',
            'Uang Harian Representasi',
            '', '',
            'Tiket Pesawat',
            '', '', '', '', '', '', '', '', '', '',
            'Penginapan',
            '', '', '',
            'Transportasi Lokal (Taksi, Speed, dan Lain-lain)',
            '', '', '', '', '',
            'Biaya Lain-Lain',
            'Grand Total SPPD',
            'DOKUMENTASI',
            '', '',
        ];

        $bottom = [
            '', '', '', '',
            'Dari',
            'Sampai',
            'No Surat Tugas',
            'Tanggal Surat Tugas',
            'Kota Tujuan',
            'Jumlah Hari',
            'Nominal Sesuai SPPD',
            'Total',
            'Jumlah Hari',
            'Nominal Sesuai SPPD',
            'Total',
            'Tanggal Pesawat Berangkat',
            'Tanggal Pesawat Pulang',
            'Harga Tiket Berangkat',
            'Harga Tiket Kembali',
            'Total',
            'Maskapai Berangkat',
            'Maskapai Pulang',
            'Nomor Tiket Berangkat',
            'Nomor Tiket Pulang',
            'Kode Booking Berangkat',
            'Kode Booking Pulang',
            'Jumlah Malam',
            'Nominal Sesuai SPPD',
            'Total',
            'Nama Hotel',
            'Domisili Ke Bandara',
            'Bandara Ke Domisili',
            'Bandara ke Hotel',
            'Hotel Ke Bandara',
            'Lain-Lain',
            'Total Transportasi',
            '',
            '',
            'KEGIATAN',
            'BUKTI NOTA/TIKET',
            'DOKUMENTASI',
        ];

        $rows = [
            $this->rowXml($rowNumber, $this->cellsFromValues($top, 5), 34),
            $this->rowXml($rowNumber + 1, $this->cellsFromValues($bottom, 6), 42),
        ];

        $merges = [
            'A'.$rowNumber.':A'.($rowNumber + 1),
            'B'.$rowNumber.':B'.($rowNumber + 1),
            'C'.$rowNumber.':C'.($rowNumber + 1),
            'D'.$rowNumber.':D'.($rowNumber + 1),
            'E'.$rowNumber.':J'.$rowNumber,
            'K'.$rowNumber.':L'.$rowNumber,
            'M'.$rowNumber.':O'.$rowNumber,
            'P'.$rowNumber.':Z'.$rowNumber,
            'AA'.$rowNumber.':AD'.$rowNumber,
            'AE'.$rowNumber.':AJ'.$rowNumber,
            'AK'.$rowNumber.':AK'.($rowNumber + 1),
            'AL'.$rowNumber.':AL'.($rowNumber + 1),
            'AM'.$rowNumber.':AO'.$rowNumber,
        ];

        return [$rows, $merges, $rowNumber + 2];
    }

    private function entryCells(PerjadinEntry $entry, int $sequence): array
    {
        $days = $this->tripDays($entry);
        $dailyDays = $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_days : 0;
        $representationDays = $entry->representation_enabled ? (int) $entry->representation_days : 0;
        $ticketEnabled = (bool) $entry->ticket_enabled;
        $lodgingEnabled = (bool) $entry->lodging_enabled;
        $transportEnabled = (bool) $entry->local_transport_enabled;

        $values = [
            $sequence,
            $entry->skpd_name ?: '',
            $entry->executor_name ?: '',
            $this->positionGradeLabel($entry),
            $this->dateLabel($entry->start_date),
            $this->dateLabel($entry->end_date),
            $entry->assignment_number ?: '',
            $this->dateLabel($entry->assignment_date),
            $entry->destination_city ?: $entry->destination_regency ?: '',
            $days,
            $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_rate : 0,
            $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_total : 0,
            $representationDays,
            $entry->representation_enabled ? (int) $entry->representation_rate : 0,
            $entry->representation_enabled ? (int) $entry->representation_total : 0,
            $ticketEnabled ? $this->dateLabel($entry->ticket_departure_date) : '',
            $ticketEnabled ? $this->dateLabel($entry->ticket_return_date) : '',
            $ticketEnabled ? (int) $entry->ticket_departure_price : 0,
            $ticketEnabled ? (int) $entry->ticket_return_price : 0,
            $ticketEnabled ? (int) $entry->ticket_total : 0,
            $ticketEnabled ? ($entry->ticket_departure_operator ?: '') : '',
            $ticketEnabled ? ($entry->ticket_return_operator ?: '') : '',
            $ticketEnabled ? ($entry->ticket_departure_number ?: '') : '',
            $ticketEnabled ? ($entry->ticket_return_number ?: '') : '',
            $ticketEnabled ? ($entry->ticket_departure_booking_code ?: '') : '',
            $ticketEnabled ? ($entry->ticket_return_booking_code ?: '') : '',
            $lodgingEnabled ? (int) $entry->lodging_nights : 0,
            $lodgingEnabled ? $this->effectiveLodgingRate($entry) : 0,
            $lodgingEnabled ? (int) $entry->lodging_total : 0,
            $lodgingEnabled ? ($entry->lodging_hotel_name ?: '') : '',
            $transportEnabled ? (int) $entry->local_transport_domicile_to_airport : 0,
            $transportEnabled ? (int) $entry->local_transport_airport_to_domicile : 0,
            $transportEnabled ? (int) $entry->local_transport_airport_to_hotel : 0,
            $transportEnabled ? (int) $entry->local_transport_hotel_to_airport : 0,
            $transportEnabled ? (int) $entry->local_transport_other : 0,
            $transportEnabled ? (int) $entry->local_transport_total : 0,
            $entry->other_cost_enabled ? (int) $entry->other_cost_amount : 0,
            (int) $entry->grand_total,
            $entry->activity_file_original_name ?: '',
            $entry->receipt_file_original_name ?: '',
            $entry->report_file_original_name ?: '',
        ];

        $cells = [];
        foreach ($values as $index => $value) {
            $column = $index + 1;
            if (is_int($value)) {
                $style = in_array($column, self::MONEY_COLUMNS, true) ? 9 : 10;
                $cells[] = $this->numberCell($column, $value, $style);
            } else {
                $cells[] = $this->stringCell($column, (string) $value, in_array($column, [1, 10, 13, 27], true) ? 8 : 7);
            }
        }

        return $cells;
    }

    private function periodLabel(Collection $entries): string
    {
        $years = $entries
            ->map(fn (PerjadinEntry $entry) => optional($entry->start_date)->year)
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($years->count() === 1) {
            return 'Periode 1 Januari s.d 31 Desember '.$years->first();
        }

        if ($years->count() > 1) {
            return 'Periode '.$years->first().' s.d '.$years->last();
        }

        return 'Periode 1 Januari s.d 31 Desember '.now()->year;
    }

    private function monthGroupLabel(string $monthKey, Collection $entries): string
    {
        $entry = $entries->first();
        $month = optional($entry?->start_date)->month;
        $label = $month ? self::MONTHS[$month] : null;

        return $label ? mb_strtoupper($label) : ($monthKey === 'tanpa-tanggal' ? 'TANPA TANGGAL' : mb_strtoupper($monthKey));
    }

    private function tripDays(PerjadinEntry $entry): int
    {
        if ($entry->start_date && $entry->end_date) {
            return max($entry->start_date->diffInDays($entry->end_date) + 1, 1);
        }

        return max((int) $entry->daily_allowance_days, 0);
    }

    private function effectiveLodgingRate(PerjadinEntry $entry): int
    {
        if (! $entry->lodging_enabled) {
            return 0;
        }

        if ($entry->lodging_has_receipt) {
            return (int) $entry->lodging_rate;
        }

        if ((int) $entry->lodging_nights > 0 && (int) $entry->lodging_total > 0) {
            return (int) round((int) $entry->lodging_total / (int) $entry->lodging_nights);
        }

        return (int) round(max((int) $entry->lodging_rate, 0) * 0.3);
    }

    private function dateLabel(mixed $date): string
    {
        if (! $date) {
            return '';
        }

        $month = self::MONTHS[$date->month] ?? $date->format('F');

        return $date->format('d').' '.$month.' '.$date->format('Y');
    }

    private function positionGradeLabel(PerjadinEntry $entry): string
    {
        return collect([
            trim((string) $entry->position_name),
            trim((string) $entry->grade),
        ])->filter()->implode('-');
    }

    private function filledRowCells(array $values, int $style): array
    {
        $values = array_pad($values, self::COLUMN_COUNT, '');

        return $this->cellsFromValues($values, $style);
    }

    private function blankCells(): array
    {
        return $this->filledRowCells([], 0);
    }

    private function cellsFromValues(array $values, int $style): array
    {
        $values = array_slice(array_pad($values, self::COLUMN_COUNT, ''), 0, self::COLUMN_COUNT);

        return array_map(
            fn (string|int $value, int $index): string => is_int($value)
                ? $this->numberCell($index + 1, $value, $style)
                : $this->stringCell($index + 1, (string) $value, $style),
            $values,
            array_keys($values)
        );
    }

    private function rowXml(int $rowNumber, array $cells, ?int $height = null): string
    {
        return '<row r="'.$rowNumber.'"'.($height ? ' ht="'.$height.'" customHeight="1"' : '').'>'.implode('', $cells).'</row>';
    }

    private function stringCell(int $column, string $value, int $style = 0): string
    {
        return '<c t="inlineStr"'.($style ? ' s="'.$style.'"' : '').'><is><t>'.$this->escape($value).'</t></is></c>';
    }

    private function numberCell(int $column, int $value, int $style = 0): string
    {
        return '<c'.($style ? ' s="'.$style.'"' : '').'><v>'.$value.'</v></c>';
    }

    private function columnsXml(): string
    {
        $widths = [
            1 => 6,
            2 => 28,
            3 => 34,
            4 => 18,
            5 => 17,
            6 => 17,
            7 => 24,
            8 => 18,
            9 => 24,
            10 => 12,
            11 => 18,
            12 => 18,
            13 => 12,
            14 => 18,
            15 => 18,
            16 => 20,
            17 => 20,
            18 => 18,
            19 => 18,
            20 => 18,
            21 => 20,
            22 => 20,
            23 => 22,
            24 => 22,
            25 => 22,
            26 => 22,
            27 => 14,
            28 => 18,
            29 => 18,
            30 => 28,
            31 => 18,
            32 => 18,
            33 => 18,
            34 => 18,
            35 => 18,
            36 => 20,
            37 => 18,
            38 => 20,
            39 => 28,
            40 => 28,
            41 => 28,
        ];

        $xml = '<cols>';
        foreach ($widths as $column => $width) {
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        return $xml.'</cols>';
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="Perjadin Luar Provinsi" sheetId="1" r:id="rId1"/>
<sheet name="Perjadin Luar Kota Dalam Prov" sheetId="2" r:id="rId2"/>
<sheet name="Perjadin dalam Kota" sheetId="3" r:id="rId3"/>
</sheets>
</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>
<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp&quot; #,##0;[Red]&quot;Rp&quot; #,##0;&quot;Rp&quot; 0"/></numFmts>
<fonts count="6">
<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="14"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FF9A3412"/><name val="Calibri"/></font>
<font><b/><sz val="12"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
</fonts>
<fills count="6">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF047857"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF111827"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FF000000"/></left><right style="thin"><color rgb="FF000000"/></right><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="11">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="4" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="164" fontId="0" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>Export Data Perjadin BPK</dc:title>
<dc:creator>Kontrol App</dc:creator>
<cp:lastModifiedBy>Kontrol App</cp:lastModifiedBy>
<dcterms:created xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:created>
<dcterms:modified xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:modified>
</cp:coreProperties>';
    }

    private function appPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
<Application>Kontrol App</Application>
</Properties>';
    }
}
