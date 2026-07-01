<?php

namespace App\Services;

use App\Models\PerjadinEntry;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class PerjadinExcelExporter
{
    private const CATEGORIES = [
        'Perjadin Luar Daerah',
        'Perjadin Dalam Daerah',
    ];

    private const MONEY_COLUMNS = [
        20, 21, 23, 24, 27, 28, 29, 40, 43, 44, 45, 46, 47, 48, 49, 50,
    ];

    private const HEADERS = [
        'No',
        'Bulan',
        'Nama SKPD',
        'Nama Pelaksana',
        'Jabatan',
        'Eselon',
        'Golongan',
        'No Surat Tugas',
        'Tanggal Surat',
        'Tanggal Mulai',
        'Tanggal Selesai',
        'Kota/Kab Tujuan',
        'Kabupaten Asal',
        'Kecamatan Asal',
        'Kabupaten Tujuan',
        'Kecamatan Tujuan',
        'Jenis Perjalanan Sofifi',
        'Sofifi > 8 Jam',
        'Hari Uang Harian',
        'Tarif Uang Harian',
        'Total Uang Harian',
        'Hari Representasi',
        'Tarif Representasi',
        'Total Representasi',
        'Jenis Transport Tiket',
        'Tgl Berangkat Tiket',
        'Harga Tiket Berangkat',
        'Tgl Pulang Tiket',
        'Harga Tiket Pulang',
        'Total Tiket',
        'Operator Berangkat',
        'Operator Pulang',
        'No Tiket Berangkat',
        'No Tiket Pulang',
        'Kode Booking Berangkat',
        'Kode Booking Pulang',
        'Malam Penginapan',
        'Ada Nota Penginapan',
        'Tarif Penginapan',
        'Nama Hotel',
        'Total Penginapan',
        'Transport Domisili ke Bandara',
        'Transport Bandara ke Domisili',
        'Transport Bandara ke Hotel',
        'Transport Hotel ke Bandara',
        'Transport Lain-lain',
        'Total Transport Lokal',
        'Biaya Lain-lain',
        'Grand Total',
        'Lampiran Kegiatan',
        'Lampiran Nota/Tiket',
        'Lampiran Laporan',
        'Dibuat Oleh',
        'Diedit Oleh',
        'Tanggal Input',
        'Terakhir Update',
        'Keterangan',
    ];

    public function export(Collection $entries): string
    {
        $directory = storage_path('app/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder export tidak bisa dibuat.');
        }

        $path = $directory.'/perjadin-'.now()->format('Ymd-His').'-'.uniqid().'.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File Excel tidak bisa dibuat.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        foreach (self::CATEGORIES as $index => $category) {
            $sheetEntries = $entries
                ->where('category', $category)
                ->sortBy([
                    ['start_date', 'asc'],
                    ['assignment_date', 'asc'],
                    ['id', 'asc'],
                ])
                ->values();

            $zip->addFromString(
                'xl/worksheets/sheet'.($index + 1).'.xml',
                $this->worksheetXml($category, $sheetEntries)
            );
        }

        $zip->close();

        return $path;
    }

    private function worksheetXml(string $category, Collection $entries): string
    {
        $rowNumber = 1;
        $rows = [];
        $merges = [];
        $highestColumn = $this->columnName(count(self::HEADERS));

        $rows[] = $this->rowXml($rowNumber++, [
            $this->stringCell(1, 'Export Data '.$category, 1),
        ]);
        $merges[] = 'A1:'.$highestColumn.'1';

        $rows[] = $this->rowXml($rowNumber++, [
            $this->stringCell(1, 'Dibuat pada '.now()->translatedFormat('d F Y H:i'), 2),
        ]);
        $merges[] = 'A2:'.$highestColumn.'2';

        $rows[] = $this->rowXml($rowNumber++, []);

        if ($entries->isEmpty()) {
            $rows[] = $this->rowXml($rowNumber++, [
                $this->stringCell(1, 'Belum ada data '.$category.'.', 2),
            ]);
            $merges[] = 'A4:'.$highestColumn.'4';
        } else {
            $sequence = 1;
            $entries
                ->groupBy(fn (PerjadinEntry $entry) => optional($entry->start_date)->format('Y-m') ?: 'tanpa-tanggal')
                ->each(function (Collection $monthEntries, string $monthKey) use (&$rowNumber, &$rows, &$merges, &$sequence, $highestColumn): void {
                    $monthLabel = $this->monthGroupLabel($monthKey, $monthEntries);

                    $rows[] = $this->rowXml($rowNumber, [
                        $this->stringCell(1, $monthLabel, 3),
                    ]);
                    $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
                    $rowNumber++;

                    $headerCells = [];
                    foreach (self::HEADERS as $column => $header) {
                        $headerCells[] = $this->stringCell($column + 1, $header, 4);
                    }
                    $rows[] = $this->rowXml($rowNumber++, $headerCells);

                    foreach ($monthEntries as $entry) {
                        $rows[] = $this->rowXml($rowNumber++, $this->entryCells($entry, $sequence++));
                    }

                    $rows[] = $this->rowXml($rowNumber++, []);
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

        $xml .= '<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private function entryCells(PerjadinEntry $entry, int $sequence): array
    {
        $effectiveLodgingRate = $this->effectiveLodgingRate($entry);

        $values = [
            $sequence,
            optional($entry->start_date)->translatedFormat('F Y') ?: '-',
            $entry->skpd_name,
            $entry->executor_name,
            $entry->position_name,
            $entry->echelon_level ?: '-',
            $entry->grade,
            $entry->assignment_number,
            $this->dateLabel($entry->assignment_date),
            $this->dateLabel($entry->start_date),
            $this->dateLabel($entry->end_date),
            $entry->destination_city ?: '-',
            $entry->origin_regency ?: '-',
            $entry->origin_district ?: '-',
            $entry->destination_regency ?: '-',
            $entry->destination_district ?: '-',
            $this->regionalTripScopeLabel($entry->regional_trip_scope),
            $entry->sofifi_over_8_hours ? 'Ya' : 'Tidak',
            $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_days : null,
            $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_rate : null,
            (int) $entry->daily_allowance_total,
            $entry->representation_enabled ? (int) $entry->representation_days : null,
            $entry->representation_enabled ? (int) $entry->representation_rate : null,
            (int) $entry->representation_total,
            $entry->ticket_enabled ? ($entry->ticket_transport_type ?: '-') : '-',
            $entry->ticket_enabled ? $this->dateLabel($entry->ticket_departure_date) : '-',
            $entry->ticket_enabled ? (int) $entry->ticket_departure_price : null,
            $entry->ticket_enabled ? $this->dateLabel($entry->ticket_return_date) : '-',
            $entry->ticket_enabled ? (int) $entry->ticket_return_price : null,
            (int) $entry->ticket_total,
            $entry->ticket_departure_operator ?: '-',
            $entry->ticket_return_operator ?: '-',
            $entry->ticket_departure_number ?: '-',
            $entry->ticket_return_number ?: '-',
            $entry->ticket_departure_booking_code ?: '-',
            $entry->ticket_return_booking_code ?: '-',
            $entry->lodging_enabled ? (int) $entry->lodging_nights : null,
            $entry->lodging_enabled ? ($entry->lodging_has_receipt ? 'Ya' : 'Tidak') : '-',
            $entry->lodging_enabled ? $effectiveLodgingRate : null,
            $entry->lodging_hotel_name ?: '-',
            (int) $entry->lodging_total,
            $entry->local_transport_enabled ? (int) $entry->local_transport_domicile_to_airport : null,
            $entry->local_transport_enabled ? (int) $entry->local_transport_airport_to_domicile : null,
            $entry->local_transport_enabled ? (int) $entry->local_transport_airport_to_hotel : null,
            $entry->local_transport_enabled ? (int) $entry->local_transport_hotel_to_airport : null,
            $entry->local_transport_enabled ? (int) $entry->local_transport_other : null,
            (int) $entry->local_transport_total,
            $entry->other_cost_enabled ? (int) $entry->other_cost_amount : null,
            (int) $entry->grand_total,
            $entry->activity_file_original_name ?: '-',
            $entry->receipt_file_original_name ?: '-',
            $entry->report_file_original_name ?: '-',
            $entry->creator?->name ?: '-',
            $entry->updater?->name ?: '-',
            optional($entry->created_at)->format('Y-m-d H:i') ?: '-',
            optional($entry->updated_at)->format('Y-m-d H:i') ?: '-',
            $this->paymentStatusLabel($entry),
        ];

        $cells = [];
        foreach ($values as $index => $value) {
            $column = $index + 1;

            if (is_int($value)) {
                $cells[] = $this->numberCell($column, $value, in_array($column, self::MONEY_COLUMNS, true) ? 5 : 0);
            } elseif ($value === null) {
                $cells[] = $this->stringCell($column, '-', 0);
            } else {
                $cells[] = $this->stringCell($column, (string) $value, 0);
            }
        }

        return $cells;
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

    private function rowXml(int $rowNumber, array $cells): string
    {
        return '<row r="'.$rowNumber.'">'.implode('', $cells).'</row>';
    }

    private function stringCell(int $column, string $value, int $style = 0): string
    {
        return '<c t="inlineStr"'.($style ? ' s="'.$style.'"' : '').'><is><t>'.$this->escape($value).'</t></is></c>';
    }

    private function numberCell(int $column, int $value, int $style = 0): string
    {
        return '<c'.($style ? ' s="'.$style.'"' : '').'><v>'.$value.'</v></c>';
    }

    private function monthGroupLabel(string $monthKey, Collection $entries): string
    {
        $entry = $entries->first();
        $label = optional($entry?->start_date)->translatedFormat('F Y');

        return $label ?: ($monthKey === 'tanpa-tanggal' ? 'Tanpa Tanggal' : $monthKey);
    }

    private function dateLabel(mixed $date): string
    {
        return optional($date)->format('Y-m-d') ?: '-';
    }

    private function regionalTripScopeLabel(?string $value): string
    {
        return match ($value) {
            'dalam_kota_sofifi' => 'Dalam Kota Sofifi',
            'luar_kota_sofifi' => 'Luar Kota Sofifi',
            default => '-',
        };
    }

    private function paymentStatusLabel(PerjadinEntry $entry): string
    {
        return $entry->paid_at ? 'Sudah Dibayar' : 'Belum Dibayar';
    }

    private function columnsXml(): string
    {
        $widths = [
            1 => 6,
            2 => 18,
            3 => 28,
            4 => 24,
            5 => 28,
            8 => 28,
            12 => 22,
            19 => 14,
            20 => 16,
            21 => 16,
            49 => 18,
            50 => 28,
            51 => 28,
            52 => 28,
            53 => 20,
            54 => 20,
            55 => 18,
            56 => 18,
        ];

        $xml = '<cols>';
        for ($column = 1; $column <= count(self::HEADERS); $column++) {
            $width = $widths[$column] ?? 16;
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
<sheet name="Perjadin Luar Daerah" sheetId="1" r:id="rId1"/>
<sheet name="Perjadin Dalam Daerah" sheetId="2" r:id="rId2"/>
</sheets>
</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp&quot; #,##0"/></numFmts>
<fonts count="4">
<font><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/></font>
<font><b/><sz val="16"/><color rgb="FF0F172A"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
<font><b/><sz val="12"/><color rgb="FF0F172A"/><name val="Calibri"/></font>
</fonts>
<fills count="5">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFE0F2FE"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="6">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>Export Data Perjadin</dc:title>
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
