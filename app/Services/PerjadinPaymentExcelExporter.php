<?php

namespace App\Services;

use App\Models\PerjadinEntry;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class PerjadinPaymentExcelExporter
{
    private const COLUMN_COUNT = 17;

    private const MONEY_COLUMNS = [6, 9, 11, 12, 14, 15, 16];

    public function export(Collection $groups, array $period): string
    {
        $directory = storage_path('app/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder export tidak bisa dibuat.');
        }

        $path = $directory.'/daftar-penerimaan-perjadin-'.now()->format('Ymd-His').'-'.uniqid().'.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File Excel daftar penerimaan perjadin tidak bisa dibuat.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($groups, $period));

        $zip->close();

        return $path;
    }

    private function worksheetXml(Collection $groups, array $period): string
    {
        $rows = [];
        $merges = [];
        $rowNumber = 1;
        $highestColumn = $this->columnName(self::COLUMN_COUNT);

        $rows[] = $this->rowXml($rowNumber, $this->filledRowCells(['DAFTAR PENERIMAAN PERJALANAN DINAS'], 1), 26);
        $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
        $rowNumber += 2;

        $purposeNumber = 1;
        foreach ($groups as $group) {
            $rows[] = $this->rowXml($rowNumber, $this->filledRowCells([$purposeNumber++.'. '.$group['paymentGroup']->purpose], 2), 22);
            $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
            $rowNumber++;
        }

        $rowNumber++;

        $periodNumber = 1;
        foreach ($groups as $group) {
            $rows[] = $this->rowXml($rowNumber, $this->filledRowCells([$periodNumber++.'. Dari Tanggal : '.$group['periodLabel']], 2), 22);
            $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
            $rowNumber++;
        }

        $destinations = $groups
            ->pluck('destination')
            ->filter(fn (string $destination): bool => $destination !== '' && $destination !== '-')
            ->unique()
            ->values()
            ->implode(', ');

        $rows[] = $this->rowXml($rowNumber, [
            $this->stringCell(1, 'Tempat : '.($destinations !== '' ? $destinations : '-'), 2),
            $this->blankCell(2, 2),
            $this->blankCell(3, 2),
            $this->blankCell(4, 2),
            $this->blankCell(5, 2),
            $this->blankCell(6, 2),
            $this->blankCell(7, 2),
            $this->stringCell(8, 'Nomor Rekening :', 2),
            $this->blankCell(9, 2),
            $this->blankCell(10, 2),
            $this->blankCell(11, 2),
            $this->blankCell(12, 2),
            $this->blankCell(13, 2),
            $this->blankCell(14, 2),
            $this->blankCell(15, 2),
            $this->blankCell(16, 2),
            $this->blankCell(17, 2),
        ], 22);
        $merges[] = 'A'.$rowNumber.':G'.$rowNumber;
        $merges[] = 'H'.$rowNumber.':'.$highestColumn.$rowNumber;
        $rowNumber += 2;

        [$headerRows, $headerMerges, $nextRow] = $this->headerRows($rowNumber);
        $rows = array_merge($rows, $headerRows);
        $merges = array_merge($merges, $headerMerges);
        $rowNumber = $nextRow;

        $sequence = 1;
        $grandTotal = 0;
        foreach ($groups as $group) {
            $rows[] = $this->rowXml($rowNumber, $this->assignmentSeparatorCells($group), 28);
            $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
            $rowNumber++;

            foreach ($group['entries'] as $entry) {
                $rows[] = $this->rowXml($rowNumber++, $this->entryCells($entry, $sequence++), 36);
                $grandTotal += (int) $entry->grand_total;
            }
        }

        if ($sequence === 1) {
            $rows[] = $this->rowXml($rowNumber, $this->filledRowCells(['Belum ada data perjadin terbayar pada periode '.$period['label'].' '.$period['year']], 7), 28);
            $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
            $rowNumber++;
        }

        $rows[] = $this->rowXml($rowNumber, [
            $this->blankCell(1, 8),
            $this->stringCell(2, 'JUMLAH', 8),
            $this->blankCell(3, 8),
            $this->blankCell(4, 8),
            $this->blankCell(5, 8),
            $this->numberCell(6, $this->sumGroups($groups, 'transport'), 9),
            $this->blankCell(7, 8),
            $this->blankCell(8, 8),
            $this->numberCell(9, $this->sumGroups($groups, 'daily_allowance_total'), 9),
            $this->blankCell(10, 8),
            $this->blankCell(11, 8),
            $this->numberCell(12, $this->sumGroups($groups, 'representation_total'), 9),
            $this->blankCell(13, 8),
            $this->blankCell(14, 8),
            $this->numberCell(15, $this->sumGroups($groups, 'lodging_total'), 9),
            $this->numberCell(16, $grandTotal, 9),
            $this->blankCell(17, 8),
        ], 24);
        $merges[] = 'B'.$rowNumber.':E'.$rowNumber;
        $rowNumber += 2;

        $rows[] = $this->rowXml($rowNumber, $this->filledRowCells([mb_convert_case(trim($this->terbilang($grandTotal)).' rupiah', MB_CASE_TITLE, 'UTF-8')], 14), 24);
        $merges[] = 'A'.$rowNumber.':'.$highestColumn.$rowNumber;
        $rowNumber += 2;

        [$signatureRows, $signatureMerges, $nextSignatureRow] = $this->signatureRows($rowNumber);
        $rows = array_merge($rows, $signatureRows);
        $merges = array_merge($merges, $signatureMerges);
        $rowNumber = $nextSignatureRow;

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
            'No',
            'Nama',
            'Gol',
            'Domain',
            'Asal Instansi/Asal Sekolah',
            'Transport',
            'Uang Harian',
            '',
            '',
            'Representasi',
            '',
            '',
            'Penginapan',
            '',
            '',
            'Jumlah Diterima',
            'Tanda Tangan',
        ];

        $bottom = [
            '',
            '',
            '',
            '',
            '',
            '',
            'Hari',
            'Besarnya',
            'Jumlah',
            'Hari',
            'Besarnya',
            'Jumlah',
            'Malam',
            'Besarnya',
            'Jumlah',
            '',
            '',
        ];

        $rows = [
            $this->rowXml($rowNumber, $this->cellsFromValues($top, 3), 34),
            $this->rowXml($rowNumber + 1, $this->cellsFromValues($bottom, 3), 28),
        ];

        $merges = [
            'A'.$rowNumber.':A'.($rowNumber + 1),
            'B'.$rowNumber.':B'.($rowNumber + 1),
            'C'.$rowNumber.':C'.($rowNumber + 1),
            'D'.$rowNumber.':D'.($rowNumber + 1),
            'E'.$rowNumber.':E'.($rowNumber + 1),
            'F'.$rowNumber.':F'.($rowNumber + 1),
            'G'.$rowNumber.':I'.$rowNumber,
            'J'.$rowNumber.':L'.$rowNumber,
            'M'.$rowNumber.':O'.$rowNumber,
            'P'.$rowNumber.':P'.($rowNumber + 1),
            'Q'.$rowNumber.':Q'.($rowNumber + 1),
        ];

        return [$rows, $merges, $rowNumber + 2];
    }

    private function entryCells(PerjadinEntry $entry, int $sequence): array
    {
        $transportTotal = $this->transportTotal($entry);
        $dailyDays = $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_days : 0;
        $representationDays = $entry->representation_enabled ? (int) $entry->representation_days : 0;
        $lodgingNights = $entry->lodging_enabled ? (int) $entry->lodging_nights : 0;

        $values = [
            $sequence,
            $entry->executor_name ?: '',
            $this->gradeLabel($entry),
            $entry->position_name ?: '',
            $entry->skpd_name ?: '',
            $transportTotal,
            $dailyDays,
            $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_rate : 0,
            $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_total : 0,
            $representationDays,
            $entry->representation_enabled ? (int) $entry->representation_rate : 0,
            $entry->representation_enabled ? (int) $entry->representation_total : 0,
            $lodgingNights,
            $entry->lodging_enabled ? $this->effectiveLodgingRate($entry) : 0,
            $entry->lodging_enabled ? (int) $entry->lodging_total : 0,
            (int) $entry->grand_total,
            '',
        ];

        $cells = [];
        foreach ($values as $index => $value) {
            $column = $index + 1;

            if (is_int($value)) {
                $cells[] = $this->numberCell($column, $value, in_array($column, self::MONEY_COLUMNS, true) ? 6 : 5);
            } else {
                $cells[] = $this->stringCell($column, (string) $value, in_array($column, [1, 3, 7, 10, 13, 17], true) ? 5 : 4);
            }
        }

        return $cells;
    }

    private function assignmentSeparatorCells(array $group): array
    {
        $assignmentNumber = trim((string) $group['paymentGroup']->assignment_number);
        $label = '('.($assignmentNumber !== '' ? $assignmentNumber : 'nomor surat tugas').')';

        return $this->filledRowCells([$label], 15);
    }

    private function signatureRows(int $rowNumber): array
    {
        $rows = [];
        $merges = [];
        $leftRange = fn (int $row): string => 'B'.$row.':F'.$row;
        $rightRange = fn (int $row): string => 'M'.$row.':Q'.$row;
        $lineCells = function (?string $leftText = null, ?string $rightText = null, int $style = 12): array {
            $cells = $this->filledRowCells([], 0);

            if ($leftText !== null) {
                $cells[1] = $this->stringCell(2, $leftText, $style);
            }

            if ($rightText !== null) {
                $cells[12] = $this->stringCell(13, $rightText, $style);
            }

            return $cells;
        };

        $rows[] = $this->rowXml($rowNumber, $lineCells(null, 'Sofifi, '.now()->translatedFormat('d F Y')), 22);
        $merges[] = $rightRange($rowNumber);
        $rowNumber += 2;

        $rows[] = $this->rowXml($rowNumber, $lineCells('Mengetahui,', 'BENDAHARA PENGELUARAN'), 22);
        $merges[] = $leftRange($rowNumber);
        $merges[] = $rightRange($rowNumber);
        $rowNumber++;

        $rows[] = $this->rowXml($rowNumber, $lineCells('Pengguna Anggaran Dinas Pendidikan dan Kebudayaan'), 22);
        $merges[] = $leftRange($rowNumber);
        $rowNumber++;

        $rows[] = $this->rowXml($rowNumber, $lineCells('Provinsi Maluku Utara'), 22);
        $merges[] = $leftRange($rowNumber);
        $rowNumber += 5;

        $rows[] = $this->rowXml($rowNumber, $lineCells('Dr. ABUBAKAR HI. ABDULLAH, S.Pd., M.Si', 'VIVI IRIYANTI, ST', 13), 22);
        $merges[] = $leftRange($rowNumber);
        $merges[] = $rightRange($rowNumber);
        $rowNumber++;

        $rows[] = $this->rowXml($rowNumber, $lineCells('NIP. 19730524 200212 1 002', 'NIP. 19810131 201001 2 014'), 22);
        $merges[] = $leftRange($rowNumber);
        $merges[] = $rightRange($rowNumber);

        return [$rows, $merges, $rowNumber + 1];
    }

    private function sumGroups(Collection $groups, string $field): int
    {
        return (int) $groups->sum(function (array $group) use ($field): int {
            return (int) $group['entries']->sum(function (PerjadinEntry $entry) use ($field): int {
                if ($field === 'transport') {
                    return $this->transportTotal($entry);
                }

                if ($field === 'daily_allowance_total') {
                    return $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_total : 0;
                }

                if ($field === 'representation_total') {
                    return $entry->representation_enabled ? (int) $entry->representation_total : 0;
                }

                if ($field === 'lodging_total') {
                    return $entry->lodging_enabled ? (int) $entry->lodging_total : 0;
                }

                return (int) $entry->{$field};
            });
        });
    }

    private function transportTotal(PerjadinEntry $entry): int
    {
        return ($entry->ticket_enabled ? (int) $entry->ticket_total : 0)
            + ($entry->local_transport_enabled ? (int) $entry->local_transport_total : 0)
            + ($entry->other_cost_enabled ? (int) $entry->other_cost_amount : 0);
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

    private function gradeLabel(PerjadinEntry $entry): string
    {
        return trim((string) $entry->grade) ?: '-';
    }

    private function rowXml(int $rowNumber, array $cells, ?int $height = null): string
    {
        return '<row r="'.$rowNumber.'"'.($height ? ' ht="'.$height.'" customHeight="1"' : '').'>'.implode('', $cells).'</row>';
    }

    private function blankCell(int $column, int $style = 0): string
    {
        return $this->stringCell($column, '', $style);
    }

    private function stringCell(int $column, string $value, int $style = 0): string
    {
        return '<c t="inlineStr"'.($style ? ' s="'.$style.'"' : '').'><is><t>'.$this->escape($value).'</t></is></c>';
    }

    private function numberCell(int $column, int $value, int $style = 0): string
    {
        return '<c'.($style ? ' s="'.$style.'"' : '').'><v>'.$value.'</v></c>';
    }

    private function filledRowCells(array $values, int $style): array
    {
        $values = array_slice(array_pad($values, self::COLUMN_COUNT, ''), 0, self::COLUMN_COUNT);

        return $this->cellsFromValues($values, $style);
    }

    private function cellsFromValues(array $values, int $style): array
    {
        return array_map(
            fn (string|int $value, int $index): string => is_int($value)
                ? $this->numberCell($index + 1, $value, $style)
                : $this->stringCell($index + 1, (string) $value, $style),
            $values,
            array_keys($values)
        );
    }

    private function columnsXml(): string
    {
        $widths = [
            1 => 6,
            2 => 28,
            3 => 12,
            4 => 20,
            5 => 30,
            6 => 16,
            7 => 10,
            8 => 16,
            9 => 16,
            10 => 10,
            11 => 16,
            12 => 16,
            13 => 10,
            14 => 16,
            15 => 16,
            16 => 18,
            17 => 22,
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

    private function terbilang(int $value): string
    {
        $value = abs($value);
        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($value < 12) {
            return ' '.$words[$value];
        }

        if ($value < 20) {
            return $this->terbilang($value - 10).' belas';
        }

        if ($value < 100) {
            return $this->terbilang((int) floor($value / 10)).' puluh'.$this->terbilang($value % 10);
        }

        if ($value < 200) {
            return ' seratus'.$this->terbilang($value - 100);
        }

        if ($value < 1000) {
            return $this->terbilang((int) floor($value / 100)).' ratus'.$this->terbilang($value % 100);
        }

        if ($value < 2000) {
            return ' seribu'.$this->terbilang($value - 1000);
        }

        if ($value < 1000000) {
            return $this->terbilang((int) floor($value / 1000)).' ribu'.$this->terbilang($value % 1000);
        }

        if ($value < 1000000000) {
            return $this->terbilang((int) floor($value / 1000000)).' juta'.$this->terbilang($value % 1000000);
        }

        if ($value < 1000000000000) {
            return $this->terbilang((int) floor($value / 1000000000)).' miliar'.$this->terbilang($value % 1000000000);
        }

        return $this->terbilang((int) floor($value / 1000000000000)).' triliun'.$this->terbilang($value % 1000000000000);
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
<sheet name="Daftar Penerimaan" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp&quot; #,##0;[Red]&quot;Rp&quot; #,##0;&quot;Rp&quot; 0"/></numFmts>
<fonts count="7">
<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="16"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><i/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><u/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
</fonts>
<fills count="5">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFD9EAF7"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFCE4D6"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FF000000"/></left><right style="thin"><color rgb="FF000000"/></right><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="16">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="2" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="2" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="164" fontId="0" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="2" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="164" fontId="4" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="0" fontId="5" fillId="2" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="2" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="2" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
<xf numFmtId="0" fontId="6" fillId="2" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
<xf numFmtId="0" fontId="4" fillId="2" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="4" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>Daftar Penerimaan Perjadin</dc:title>
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
