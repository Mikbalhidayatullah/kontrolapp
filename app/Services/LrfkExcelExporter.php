<?php

namespace App\Services;

use App\Models\LrfkEntry;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class LrfkExcelExporter
{
    private const HEADERS = [
        'Kode',
        'Kode Rekening',
        'Program / Kegiatan / Sub Kegiatan',
        'Pagu Anggaran',
        'Kontrak Nilai',
        'Nomor / Tanggal',
        'Pelaksana',
        'Keluaran',
        'Volume',
        'Satuan',
        'Realisasi Rp',
        'Keuangan %',
        'Fisik %',
        'Lokasi',
        'Ket.',
    ];

    private const LEVELS = [
        'dinas',
        'belanja_daerah',
        'program',
        'kegiatan',
        'sub_kegiatan',
        'rekening',
    ];

    private const LEVEL_FILLS = [
        'FFFFEDD5',
        'FFD1FAE5',
        'FFE0F2FE',
        'FFFCE7F3',
        'FFE2E8F0',
        'FFFFFFFF',
    ];

    public function export(
        Collection $entries,
        array $metrics,
        array $linkedUsageByEntry,
        array $levelOptions,
        array $filters
    ): string {
        $directory = storage_path('app/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder export tidak bisa dibuat.');
        }

        $path = $directory.'/lrfk-'.now()->format('Ymd-His').'-'.uniqid().'.xlsx';
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File Excel LRFK tidak bisa dibuat.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString(
            'xl/worksheets/sheet1.xml',
            $this->worksheetXml($entries, $metrics, $linkedUsageByEntry, $levelOptions, $filters)
        );
        $zip->close();

        return $path;
    }

    private function worksheetXml(
        Collection $entries,
        array $metrics,
        array $linkedUsageByEntry,
        array $levelOptions,
        array $filters
    ): string {
        $rows = [];
        $rowNumber = 1;
        $lastColumn = $this->columnName(count(self::HEADERS));

        $rows[] = $this->rowXml($rowNumber, [
            $this->stringCell(1, $rowNumber, 'LAPORAN REALISASI FISIK DAN KEUANGAN (LRFK)', 1),
        ], 28);
        $rowNumber++;

        $rows[] = $this->rowXml($rowNumber, [
            $this->stringCell(1, $rowNumber, $this->filterLabel($filters, $levelOptions), 2),
        ], 20);
        $rowNumber++;

        $rows[] = $this->rowXml($rowNumber, [
            $this->stringCell(1, $rowNumber, 'Dibuat pada '.now()->translatedFormat('d F Y H:i'), 2),
        ], 20);
        $rowNumber += 2;

        $headerCells = [];
        foreach (self::HEADERS as $index => $header) {
            $headerCells[] = $this->stringCell($index + 1, $rowNumber, $header, 3);
        }
        $rows[] = $this->rowXml($rowNumber++, $headerCells, 42);

        if ($entries->isEmpty()) {
            $rows[] = $this->rowXml($rowNumber, [
                $this->stringCell(1, $rowNumber, 'Belum ada data LRFK pada filter ini.', 2),
            ], 24);
        } else {
            foreach ($entries as $entry) {
                $linkedUsages = $linkedUsageByEntry[$entry->id] ?? [];
                $rows[] = $this->rowXml(
                    $rowNumber,
                    $this->entryCells($entry, $rowNumber, $metrics, $linkedUsages, $levelOptions),
                    max(28, 28 + (count($linkedUsages) * 17))
                );
                $rowNumber++;
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="18"/>';
        $xml .= $this->columnsXml();
        $xml .= '<sheetData>'.implode('', $rows).'</sheetData>';
        $xml .= '<mergeCells count="3"><mergeCell ref="A1:'.$lastColumn.'1"/><mergeCell ref="A2:'.$lastColumn.'2"/><mergeCell ref="A3:'.$lastColumn.'3"/></mergeCells>';

        $xml .= '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>';
        $xml .= '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0" paperSize="9"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private function entryCells(
        LrfkEntry $entry,
        int $rowNumber,
        array $metrics,
        array $linkedUsages,
        array $levelOptions
    ): array {
        $rowMetrics = $metrics[$entry->id] ?? [
            'contract' => (int) $entry->contract_value,
            'realization' => (int) $entry->financial_realization,
        ];
        $effectiveContract = (int) $rowMetrics['contract'];
        $effectiveRealization = (int) $rowMetrics['realization'];
        $percentage = (int) $entry->pagu_anggaran > 0
            ? $effectiveRealization / (int) $entry->pagu_anggaran
            : 0;
        [$textStyle, $moneyStyle, $percentStyle] = $this->stylesForLevel($entry->level);
        $programLabel = trim($entry->program_kegiatan);
        if ($programLabel !== '') {
            $programLabel .= "\n".($levelOptions[$entry->level] ?? $entry->level);
        }

        return [
            $this->stringCell(1, $rowNumber, $entry->kode ?: '-', $textStyle),
            $this->stringCell(2, $rowNumber, $entry->kode_rekening ?: '-', $textStyle),
            $this->stringCell(3, $rowNumber, $programLabel ?: '-', $textStyle),
            $this->numberCell(4, $rowNumber, (int) $entry->pagu_anggaran, $moneyStyle),
            $this->numberCell(5, $rowNumber, $effectiveContract, $moneyStyle),
            $this->stringCell(6, $rowNumber, $this->combinedText($entry->contract_number_date, $linkedUsages, 'assignment_number_date'), $textStyle),
            $this->stringCell(7, $rowNumber, $this->combinedText($entry->implementer, $linkedUsages, 'implementer'), $textStyle),
            $this->stringCell(8, $rowNumber, $this->combinedText($entry->output, $linkedUsages, 'purpose'), $textStyle),
            $this->stringCell(9, $rowNumber, $entry->volume ?: '-', $textStyle),
            $this->stringCell(10, $rowNumber, $entry->unit ?: '-', $textStyle),
            $this->numberCell(11, $rowNumber, $effectiveRealization, $moneyStyle),
            $this->numberCell(12, $rowNumber, $percentage, $percentStyle),
            $this->numberCell(13, $rowNumber, $percentage, $percentStyle),
            $this->stringCell(14, $rowNumber, $entry->location ?: '-', $textStyle),
            $this->stringCell(15, $rowNumber, $entry->notes ?: '-', $textStyle),
        ];
    }

    private function combinedText(?string $baseValue, array $linkedUsages, string $key): string
    {
        $values = [];
        if (filled($baseValue)) {
            $values[] = trim((string) $baseValue);
        }

        foreach ($linkedUsages as $usage) {
            $values[] = trim((string) ($usage[$key] ?? '')) ?: '-';
        }

        return $values === [] ? '-' : implode("\n", $values);
    }

    private function filterLabel(array $filters, array $levelOptions): string
    {
        $level = (string) ($filters['level'] ?? '');
        $keyword = trim((string) ($filters['keyword'] ?? ''));

        return 'Jenis: '.($levelOptions[$level] ?? 'Semua jenis')
            .' | Pencarian: '.($keyword !== '' ? $keyword : 'Semua data');
    }

    private function stylesForLevel(string $level): array
    {
        $levelIndex = array_search($level, self::LEVELS, true);
        $levelIndex = $levelIndex === false ? count(self::LEVELS) - 1 : $levelIndex;
        $firstStyle = 4 + ($levelIndex * 3);

        return [$firstStyle, $firstStyle + 1, $firstStyle + 2];
    }

    private function rowXml(int $rowNumber, array $cells, ?int $height = null): string
    {
        return '<row r="'.$rowNumber.'"'.($height ? ' ht="'.$height.'" customHeight="1"' : '').'>'.implode('', $cells).'</row>';
    }

    private function stringCell(int $column, int $row, string $value, int $style = 0): string
    {
        $reference = $this->columnName($column).$row;

        return '<c r="'.$reference.'" t="inlineStr"'.($style ? ' s="'.$style.'"' : '').'><is><t xml:space="preserve">'.$this->escape($value).'</t></is></c>';
    }

    private function numberCell(int $column, int $row, int|float $value, int $style = 0): string
    {
        $reference = $this->columnName($column).$row;
        $number = is_float($value)
            ? rtrim(rtrim(sprintf('%.10F', $value), '0'), '.')
            : (string) $value;

        return '<c r="'.$reference.'"'.($style ? ' s="'.$style.'"' : '').'><v>'.$number.'</v></c>';
    }

    private function columnsXml(): string
    {
        $widths = [14, 23, 44, 18, 18, 29, 30, 45, 13, 13, 18, 14, 14, 22, 28];
        $xml = '<cols>';

        foreach ($widths as $index => $width) {
            $column = $index + 1;
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
<sheets><sheet name="LRFK" sheetId="1" r:id="rId1"/></sheets>
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
        $fills = '<fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>';
        $fills .= '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill>';
        foreach (self::LEVEL_FILLS as $color) {
            $fills .= '<fill><patternFill patternType="solid"><fgColor rgb="'.$color.'"/><bgColor indexed="64"/></patternFill></fill>';
        }

        $cellFormats = [
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>',
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>',
            '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>',
            '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>',
        ];

        foreach (self::LEVEL_FILLS as $index => $color) {
            $fillId = $index + 3;
            $cellFormats[] = '<xf numFmtId="0" fontId="0" fillId="'.$fillId.'" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>';
            $cellFormats[] = '<xf numFmtId="164" fontId="0" fillId="'.$fillId.'" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>';
            $cellFormats[] = '<xf numFmtId="10" fontId="0" fillId="'.$fillId.'" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp&quot; #,##0"/></numFmts>
<fonts count="4">
<font><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/></font>
<font><b/><sz val="16"/><color rgb="FF0F172A"/><name val="Calibri"/></font>
<font><sz val="10"/><color rgb="FF64748B"/><name val="Calibri"/></font>
<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
</fonts>
<fills count="'.(count(self::LEVEL_FILLS) + 3).'">'.$fills.'</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FFCBD5E1"/></left><right style="thin"><color rgb="FFCBD5E1"/></right><top style="thin"><color rgb="FFCBD5E1"/></top><bottom style="thin"><color rgb="FFCBD5E1"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="'.count($cellFormats).'">'.implode('', $cellFormats).'</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>Export LRFK</dc:title><dc:creator>Kontrol App</dc:creator><cp:lastModifiedBy>Kontrol App</cp:lastModifiedBy>
<dcterms:created xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:modified>
</cp:coreProperties>';
    }

    private function appPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Kontrol App</Application></Properties>';
    }
}
