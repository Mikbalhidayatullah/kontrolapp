<?php

namespace App\Services;

use App\Models\TaxEntry;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class TaxExcelExporter
{
    private const HEADERS = [
        'Tanggal',
        'No. Bukti',
        'Uraian',
        'Kode Rekening',
        'Nama Rekening',
        'ID Billing',
        'NTPN',
        'Penerimaan',
        'Pengeluaran',
        'Saldo',
    ];

    private const MONEY_COLUMNS = [8, 9, 10];

    public function export(Collection $entries): string
    {
        $directory = storage_path('app/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder export tidak bisa dibuat.');
        }

        $path = $directory.'/pajak-'.now()->format('Ymd-His').'-'.uniqid().'.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File Excel tidak bisa dibuat.');
        }

        $sheetGroups = $this->sheetGroups($entries);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml($sheetGroups->count()));
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetGroups));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml($sheetGroups->count()));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        $sheetGroups->values()->each(function (array $sheet, int $index) use ($zip): void {
            $zip->addFromString(
                'xl/worksheets/sheet'.($index + 1).'.xml',
                $this->worksheetXml($sheet['entries'])
            );
        });

        $zip->close();

        return $path;
    }

    private function sheetGroups(Collection $entries): Collection
    {
        if ($entries->isEmpty()) {
            return collect([
                [
                    'name' => 'Data Pajak',
                    'entries' => collect(),
                ],
            ]);
        }

        return $entries
            ->groupBy('category')
            ->sortKeysUsing(fn (string $left, string $right): int => strnatcasecmp($left, $right))
            ->map(fn (Collection $categoryEntries, string $category): array => [
                'name' => $category,
                'entries' => $categoryEntries
                    ->sortBy([
                        ['entry_date', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->values(),
            ])
            ->values();
    }

    private function worksheetXml(Collection $entries): string
    {
        $rows = [];
        $merges = [];
        $rowNumber = 1;

        $headerCells = [];
        foreach (self::HEADERS as $index => $header) {
            $headerCells[] = $this->stringCell($index + 1, $header, 1);
        }
        $rows[] = $this->rowXml($rowNumber++, $headerCells, 38);

        $rows[] = $this->rowXml($rowNumber++, [
            $this->stringCell(1, '', 2),
            $this->stringCell(2, '', 2),
            $this->stringCell(3, '', 2),
            $this->stringCell(4, '', 2),
            $this->stringCell(5, '', 2),
            $this->stringCell(6, '', 2),
            $this->stringCell(7, 'Saldo Sebelumnya', 2),
            $this->numberCell(8, 0, 3),
            $this->numberCell(9, 0, 3),
            $this->numberCell(10, 0, 3),
        ], 16);

        foreach ($entries as $entry) {
            $rows[] = $this->rowXml($rowNumber++, $this->entryCells($entry), 22);
        }

        $receiptTotal = (int) $entries->sum('receipt_amount');
        $expenseTotal = (int) $entries->sum('expense_amount');
        $balanceTotal = (int) $entries->sum('balance_amount');

        $rows[] = $this->rowXml($rowNumber, [
            $this->stringCell(1, '', 5),
            $this->stringCell(2, 'T  o  t  a  l', 5),
            $this->stringCell(3, '', 5),
            $this->stringCell(4, '', 5),
            $this->stringCell(5, '', 5),
            $this->stringCell(6, '', 5),
            $this->stringCell(7, '', 5),
            $this->numberCell(8, $receiptTotal, 6),
            $this->numberCell(9, $expenseTotal, 6),
            $this->numberCell(10, $balanceTotal, 6),
        ], 19);
        $merges[] = 'B'.$rowNumber.':G'.$rowNumber;

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
        $xml .= '</worksheet>';

        return $xml;
    }

    private function entryCells(TaxEntry $entry): array
    {
        return [
            $this->stringCell(1, optional($entry->entry_date)->translatedFormat('d F Y') ?: '', 7),
            $this->stringCell(2, $entry->proof_number ?: '', 7),
            $this->stringCell(3, $entry->description ?: '', 7),
            $this->stringCell(4, $entry->account_code ?: '', 7),
            $this->stringCell(5, $entry->account_name ?: '', 8),
            $this->stringCell(6, $entry->billing_id ?: '', 8),
            $this->stringCell(7, $entry->ntpn ?: '', 8),
            $this->numberCell(8, (int) $entry->receipt_amount, 4),
            $this->numberCell(9, (int) $entry->expense_amount, 4),
            $this->numberCell(10, (int) $entry->balance_amount, 4),
        ];
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
            1 => 13,
            2 => 22,
            3 => 46,
            4 => 22,
            5 => 14,
            6 => 20,
            7 => 26,
            8 => 18,
            9 => 18,
            10 => 18,
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

    private function safeSheetName(string $name): string
    {
        $name = trim(preg_replace('/[\[\]\:\*\?\/\\\\]+/', ' ', $name) ?: 'Sheet');
        $name = $name !== '' ? $name : 'Sheet';

        return mb_substr($name, 0, 31);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypesXml(int $sheetCount): string
    {
        $sheetOverrides = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
'.$sheetOverrides.'
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

    private function workbookXml(Collection $sheetGroups): string
    {
        $sheets = '';
        $usedNames = [];

        $sheetGroups->values()->each(function (array $sheet, int $index) use (&$sheets, &$usedNames): void {
            $name = $this->uniqueSheetName($this->safeSheetName($sheet['name']), $usedNames);
            $sheets .= '<sheet name="'.$this->escape($name).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        });

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>'.$sheets.'</sheets>
</workbook>';
    }

    private function uniqueSheetName(string $name, array &$usedNames): string
    {
        $base = $name;
        $counter = 1;

        while (in_array(mb_strtolower($name), $usedNames, true)) {
            $suffix = ' '.$counter++;
            $name = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
        }

        $usedNames[] = mb_strtolower($name);

        return $name;
    }

    private function workbookRelationshipsXml(int $sheetCount): string
    {
        $relationships = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $relationships .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }
        $relationships .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
'.$relationships.'
</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp&quot;* #,##0;[Red]&quot;Rp&quot;* #,##0;&quot;Rp&quot;* -"/></numFmts>
<fonts count="5">
<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="12"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="9"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
</fonts>
<fills count="4">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFCE4D6"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FF000000"/></left><right style="thin"><color rgb="FF000000"/></right><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="9">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="bottom"/></xf>
<xf numFmtId="164" fontId="2" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="bottom"/></xf>
<xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="164" fontId="3" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>Export Data Pajak</dc:title>
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
