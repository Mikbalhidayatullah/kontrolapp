<?php

namespace App\Services;

use App\Models\PerjadinEntry;
use App\Models\PerjadinPaymentGroup;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class PerjadinReceiptExcelExporter
{
    private const COLUMN_COUNT = 21;

    public function export(Collection $entries, array $receiptOverrides = [], array $options = []): string
    {
        $directory = storage_path('app/exports');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder export kuitansi tidak bisa dibuat.');
        }

        $path = $directory.'/kuitansi-perjadin-'.now()->format('Ymd-His').'-'.uniqid().'.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File Excel kuitansi perjadin tidak bisa dibuat.');
        }

        $sheetEntries = $entries->values();
        $sheetCount = max($sheetEntries->count(), 1);
        $logoPath = public_path('images/logos/maluku_utara.png');
        $hasLogo = is_file($logoPath);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml($sheetCount, $hasLogo));
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetEntries));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml($sheetCount));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        if ($hasLogo) {
            $zip->addFile($logoPath, 'xl/media/maluku_utara.png');
        }

        if ($sheetEntries->isEmpty()) {
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->emptyWorksheetXml($hasLogo));
            if ($hasLogo) {
                $this->addDrawingParts($zip, 1);
            }
        } else {
            foreach ($sheetEntries as $index => $entry) {
                $sheetNumber = $index + 1;
                $zip->addFromString('xl/worksheets/sheet'.$sheetNumber.'.xml', $this->worksheetXml($entry, $hasLogo, $receiptOverrides, $options));
                if ($hasLogo) {
                    $this->addDrawingParts($zip, $sheetNumber);
                }
            }
        }

        $zip->close();

        return $path;
    }

    private function worksheetXml(PerjadinEntry $entry, bool $hasLogo, array $receiptOverrides = [], array $options = []): string
    {
        $rows = [];
        $merges = [];
        $rowHeights = $this->receiptRowHeights();
        $receipt = $this->receiptData($entry, $receiptOverrides);
        $breakdown = $this->receiptBreakdown($entry);
        $receiptHeadValueInColumnB = (bool) ($options['receipt_head_value_in_column_b'] ?? false);

        for ($row = 1; $row <= 46; $row++) {
            $cells = [];

            match ($row) {
                1 => $cells[] = $this->stringCell(1, 'PEMERINTAH PROVINSI MALUKU UTARA', 1),
                2 => $cells[] = $this->stringCell(1, 'DINAS PENDIDIKAN DAN KEBUDAYAAN', 2),
                3 => $cells[] = $this->stringCell(1, 'JL. Ki Hajar Dewantara No.1', 3),
                4 => $cells[] = $this->stringCell(1, 'S O F I F I', 4),
                5 => $cells = $this->filledRowCells([''], 5),
                7 => $cells = [
                    $this->stringCell(1, 'No. Kuitansi', 7),
                    $this->stringCell(2, ': '.($receiptHeadValueInColumnB ? $receipt['receipt_number'] : ''), 7),
                    ...($receiptHeadValueInColumnB ? [] : [$this->stringCell(3, $receipt['receipt_number'], 7)]),
                    $this->stringCell(19, 'Tahun Anggaran : '.$receipt['budget_year'], 13),
                ],
                8 => $cells = [
                    $this->stringCell(1, 'Lembaran', 7),
                    $this->stringCell(2, ': '.($receiptHeadValueInColumnB ? $receipt['sheet_title'] : ''), 7),
                    ...($receiptHeadValueInColumnB ? [] : [$this->stringCell(3, $receipt['sheet_title'], 7)]),
                ],
                11 => $cells[] = $this->stringCell(1, 'K U I T A N S I', 6),
                13 => $cells = $this->metaCells('Sudah terima dari', $receipt['received_from']),
                15 => $cells = $this->metaCells('Sebesar', $receipt['grand_total_label'], 9),
                17 => $cells = $this->metaCells('Terbilang', $receipt['grand_total_words'], 10),
                19 => $cells = $this->metaCells('Untuk pengeluaran', $receipt['payment_purpose'], 11),
                20, 21, 22 => $cells = [
                    ...$this->emptyCells(range(4, 21), 18),
                ],
                23 => $cells[] = $this->stringCell(4, 'dengan rincian :', 7),
                24, 25, 26, 27, 28 => $cells = $this->breakdownCells($row - 24, $breakdown[$row - 24]),
                30 => $cells[] = $this->stringCell(7, $receipt['receipt_place'].', '.$receipt['receipt_date'], 13),
                31 => $cells[] = $this->stringCell(7, 'Penerima', 13),
                35 => $cells[] = $this->stringCell(7, $receipt['recipient_name'], 15),
                36 => $cells[] = $this->stringCell(7, 'NIP. '.$receipt['recipient_nip'], 13),
                38 => $cells[] = $this->stringCell(1, 'Mengetahui dan Menyetujui ;', 13),
                39 => $cells = [
                    $this->stringCell(1, 'Kepala Dinas Pendidikan Dan Kebudayaan', 13),
                    $this->stringCell(7, 'Bendahara Pengeluaran,', 13),
                ],
                40 => $cells[] = $this->stringCell(1, 'Provinsi Maluku Utara', 13),
                45 => $cells = [
                    $this->stringCell(1, $receipt['approver_name'], 15),
                    $this->stringCell(7, $receipt['treasurer_name'], 15),
                ],
                46 => $cells = [
                    $this->stringCell(1, 'NIP. '.$receipt['approver_nip'], 13),
                    $this->stringCell(7, 'NIP. '.$receipt['treasurer_nip'], 13),
                ],
                default => [],
            };

            $rows[] = $this->rowXml($row, $cells, $rowHeights[$row] ?? 18);
        }

        foreach ([
            'A1:U1', 'A2:U2', 'A3:U3', 'A4:U4',
            'A11:U11',
            'D13:U13',
            'D19:U19',
            'A23:C23',
            'G30:U30', 'G31:U31', 'G32:U32', 'G34:U34', 'G35:U35', 'G36:U36',
            'A38:E38', 'G38:U38',
            'A39:E39', 'G39:U39',
            'A40:E40', 'G40:U40',
            'A41:E41', 'G41:U41',
            'A42:E42', 'G42:U42',
            'A43:E43', 'G43:U43',
            'A44:E44', 'G44:U44',
            'A45:E45', 'G45:U45',
            'A46:E46', 'G46:U46',
        ] as $merge) {
            $merges[] = $merge;
        }

        foreach ([24, 26] as $calculationRow) {
            $merges[] = 'K'.$calculationRow.':P'.$calculationRow;
            $merges[] = 'S'.$calculationRow.':T'.$calculationRow;
        }

        $merges[] = 'G25:L25';
        $merges[] = 'G27:U27';
        $merges[] = 'G28:U28';

        return $this->worksheetDocument($rows, $merges, $hasLogo);
    }

    private function emptyWorksheetXml(bool $hasLogo): string
    {
        $rows = [
            $this->rowXml(1, [$this->stringCell(1, 'Belum ada data perjadin untuk dibuat kuitansi.', 6)], 30),
        ];

        return $this->worksheetDocument($rows, ['A1:U1'], $hasLogo);
    }

    private function receiptData(PerjadinEntry $entry, array $overrides = []): array
    {
        $grandTotal = (int) $entry->grand_total;

        return array_replace([
            'receipt_number' => '-',
            'sheet_title' => '-',
            'budget_year' => (string) (optional($entry->assignment_date)->year ?? now()->year),
            'received_from' => 'Bendahara Pengeluaran Dinas Pendidikan dan Kebudayaan',
            'payment_purpose' => $this->receiptPaymentPurpose($entry),
            'receipt_place' => $entry->signature_location ?: 'Sofifi',
            'receipt_date' => optional($entry->assignment_date)->translatedFormat('d F Y') ?: now()->translatedFormat('d F Y'),
            'recipient_name' => $entry->executor_name ?: '-',
            'recipient_nip' => '-',
            'approver_name' => 'DR. Abubakar Hi. Abdullah, S. Pd.,M. Si.',
            'approver_nip' => '1973052420012 1 002',
            'treasurer_name' => 'Vivi Iriyanti, ST',
            'treasurer_nip' => '19810131201001 2 014',
            'grand_total_label' => $this->receiptMoneyLabel($grandTotal),
            'grand_total_words' => ucfirst(trim($this->terbilang($grandTotal))).' rupiah',
        ], $overrides);
    }

    private function receiptPaymentPurpose(PerjadinEntry $entry): string
    {
        $destination = trim((string) ($entry->destination_city ?: $entry->destination_regency ?: $entry->signature_location));
        $purpose = trim($this->paymentGroupPurpose($entry));

        $text = 'Biaya Perjalanan Dinas';

        if ($destination !== '') {
            $text .= ' tujuan '.$destination;
        }

        if ($purpose !== '') {
            $text .= ', Dalam Rangka '.$purpose;
        }

        return $text;
    }

    private function paymentGroupPurpose(PerjadinEntry $entry): string
    {
        if (! $entry->assignment_date) {
            return '';
        }

        return (string) (PerjadinPaymentGroup::query()
            ->where('assignment_number', $entry->assignment_number)
            ->whereDate('assignment_date', $entry->assignment_date)
            ->value('purpose') ?? '');
    }

    private function receiptBreakdown(PerjadinEntry $entry): array
    {
        $dailyAllowanceTotal = $entry->daily_allowance_enabled ? (int) $entry->daily_allowance_total : 0;
        $ticketTotal = $entry->ticket_enabled ? (int) $entry->ticket_total : 0;
        $lodgingTotal = $entry->lodging_enabled ? (int) $entry->lodging_total : 0;
        $representationTotal = $entry->representation_enabled ? (int) $entry->representation_total : 0;
        $localTransportTotal = $entry->local_transport_enabled ? (int) $entry->local_transport_total : 0;

        return [
            [
                'title' => 'Uang Harian',
                'days' => $dailyAllowanceTotal > 0 ? (int) $entry->daily_allowance_days : null,
                'unit' => 'hari',
                'rate' => $dailyAllowanceTotal > 0 ? $this->excelMoney((int) $entry->daily_allowance_rate) : null,
                'total' => $this->excelMoney($dailyAllowanceTotal),
            ],
            [
                'title' => 'Biaya Transportasi',
                'total' => $this->excelMoney($ticketTotal),
            ],
            [
                'title' => 'Biaya Penginapan',
                'days' => $lodgingTotal > 0 ? (int) $entry->lodging_nights : null,
                'unit' => 'hari',
                'rate' => $lodgingTotal > 0 ? $this->excelMoney($this->effectiveLodgingRate($entry)) : null,
                'total' => $this->excelMoney($lodgingTotal),
            ],
            [
                'title' => 'Uang Representasi Perjalanan Dinas',
                'total' => $this->excelMoney($representationTotal),
            ],
            [
                'title' => 'Biaya Taksi',
                'total' => $this->excelMoney($localTransportTotal),
            ],
        ];
    }

    private function breakdownCells(int $index, array $item): array
    {
        $cells = [
            $this->stringCell(4, ($index + 1).'. '.$item['title'], 7),
            $this->stringCell(6, ':', 13),
        ];

        if (isset($item['days'], $item['rate'])) {
            $cells[] = $this->stringCell(7, (string) $item['days'], 7);
            $cells[] = $this->stringCell(8, $item['unit'], 7);
            $cells[] = $this->stringCell(10, 'x', 7);
            $cells[] = $this->stringCell(11, 'Rp. '.$item['rate'], 7);
            $cells[] = $this->stringCell(18, '=', 7);
            $cells[] = $this->stringCell(19, 'Rp. '.$item['total'], 7);
        } else {
            $cells[] = $this->stringCell(7, 'Rp. '.$item['total'], 7);
        }

        return $cells;
    }

    private function metaCells(string $label, string $value, int $valueStyle = 8): array
    {
        return [
            $this->stringCell(1, $label, 7),
            $this->stringCell(3, ' :', 13),
            $this->stringCell(4, $value, $valueStyle),
            ...$this->emptyCells(range(5, 21), $valueStyle),
        ];
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

    private function receiptMoneyLabel(int|float|null $amount): string
    {
        return 'Rp. '.number_format((int) $amount, 0, ',', '.').',00';
    }

    private function excelMoney(int $amount): string
    {
        return number_format($amount, 0, ',', '.').',00';
    }

    private function worksheetDocument(array $rows, array $merges, bool $hasLogo): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>';
        $xml .= '<dimension ref="A1:U46"/>';
        $xml .= '<sheetViews><sheetView zoomScale="85" zoomScaleNormal="85" workbookViewId="0"/></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';
        $xml .= $this->columnsXml();
        $xml .= '<sheetData>'.implode('', $rows).'</sheetData>';

        if ($merges !== []) {
            $xml .= '<mergeCells count="'.count($merges).'">';
            foreach ($merges as $merge) {
                $xml .= '<mergeCell ref="'.$merge.'"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '<pageMargins left="0.3937007874015748" right="0.3937007874015748" top="0.3937007874015748" bottom="0.3937007874015748" header="0.31496062992125984" footer="0.31496062992125984"/>';
        $xml .= '<pageSetup paperSize="9" orientation="portrait"/>';
        if ($hasLogo) {
            $xml .= '<drawing r:id="rId1"/>';
        }
        $xml .= '</worksheet>';

        return $xml;
    }

    private function rowXml(int $rowNumber, array $cells, int|float|null $height = null): string
    {
        $cellsXml = preg_replace_callback('/data-col="(\d+)"/', function (array $match) use ($rowNumber): string {
            return 'r="'.$this->columnName((int) $match[1]).$rowNumber.'"';
        }, implode('', $cells)) ?? implode('', $cells);

        return '<row r="'.$rowNumber.'"'.($height ? ' ht="'.$this->formatDimension($height).'" customHeight="1"' : '').'>'.$cellsXml.'</row>';
    }

    private function blankCell(int $column, int $style = 0): string
    {
        return $this->stringCell($column, '', $style);
    }

    private function stringCell(int $column, string $value, int $style = 0): string
    {
        return '<c data-col="'.$column.'" t="inlineStr"'.($style ? ' s="'.$style.'"' : '').'><is><t>'.$this->escape($value).'</t></is></c>';
    }

    private function emptyCell(int $column, int $style = 0): string
    {
        return '<c data-col="'.$column.'"'.($style ? ' s="'.$style.'"' : '').'/>';
    }

    private function emptyCells(array $columns, int $style = 0): array
    {
        return array_map(fn (int $column): string => $this->emptyCell($column, $style), $columns);
    }

    private function filledRowCells(array $values, int $style): array
    {
        $values = array_slice(array_pad($values, self::COLUMN_COUNT, ''), 0, self::COLUMN_COUNT);

        return array_map(
            fn (string $value, int $index): string => $this->stringCell($index + 1, $value, $style),
            $values,
            array_keys($values)
        );
    }

    private function columnsXml(): string
    {
        $widths = [
            1 => 12.36328125,
            2 => 8.81640625,
            3 => 2.36328125,
            4 => 19.1796875,
            5 => 20.6328125,
            6 => 2.453125,
            7 => 3.6328125,
            8 => 3.6328125,
            9 => 2.36328125,
            10 => 3,
            11 => 3,
            12 => 3,
            13 => 3,
            14 => 3,
            15 => 3,
            16 => 2.6328125,
            17 => 0.6328125,
            18 => 3.36328125,
            19 => 16.08984375,
            20 => 2.1796875,
            21 => 5.90625,
        ];

        $xml = '<cols>';
        foreach ($widths as $column => $width) {
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$this->formatDimension($width).'" customWidth="1"/>';
        }

        return $xml.'</cols>';
    }

    private function receiptRowHeights(): array
    {
        $pixelHeights = [
            1 => 20,
            2 => 17.5,
            3 => 15,
            4 => 22.5,
            5 => 2.25,
            6 => 6.75,
            7 => 16,
            8 => 16.5,
            9 => 16,
            10 => 16,
            11 => 29,
            12 => 15,
            13 => 15.5,
            14 => 6.75,
            15 => 15,
            16 => 6.75,
            17 => 15,
            18 => 5.25,
            19 => 15,
            20 => 15,
            21 => 15,
            22 => 15,
            23 => 22.5,
            24 => 20,
            25 => 20,
            26 => 20,
            27 => 20,
            28 => 20,
            29 => 16,
            30 => 22.5,
            31 => 22.5,
            32 => 22.5,
            33 => 22.5,
            34 => 22,
            35 => 15.75,
            36 => 15.75,
            37 => 20,
            38 => 15,
            39 => 15.75,
            40 => 15,
            41 => 15,
            42 => 15,
            43 => 15,
            44 => 15,
            45 => 15,
            46 => 15.75,
        ];

        return $pixelHeights;
    }

    private function formatDimension(int|float $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
    }

    private function addDrawingParts(ZipArchive $zip, int $sheetNumber): void
    {
        $zip->addFromString('xl/worksheets/_rels/sheet'.$sheetNumber.'.xml.rels', $this->worksheetRelationshipsXml($sheetNumber));
        $zip->addFromString('xl/drawings/drawing'.$sheetNumber.'.xml', $this->drawingXml($sheetNumber));
        $zip->addFromString('xl/drawings/_rels/drawing'.$sheetNumber.'.xml.rels', $this->drawingRelationshipsXml());
    }

    private function worksheetRelationshipsXml(int $sheetNumber): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing'.$sheetNumber.'.xml"/>
</Relationships>';
    }

    private function drawingXml(int $sheetNumber): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
<xdr:twoCellAnchor editAs="oneCell">
<xdr:from><xdr:col>0</xdr:col><xdr:colOff>136071</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>60625</xdr:rowOff></xdr:from>
<xdr:to><xdr:col>1</xdr:col><xdr:colOff>154214</xdr:colOff><xdr:row>3</xdr:row><xdr:rowOff>208643</xdr:rowOff></xdr:to>
<xdr:pic>
<xdr:nvPicPr><xdr:cNvPr id="'.($sheetNumber + 1).'" name="Logo Provinsi Maluku Utara"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>
<xdr:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>
<xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>
</xdr:pic>
<xdr:clientData/>
</xdr:twoCellAnchor>
</xdr:wsDr>';
    }

    private function drawingRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/maluku_utara.png"/>
</Relationships>';
    }

    private function contentTypesXml(int $sheetCount, bool $hasLogo): string
    {
        $sheetOverrides = '';
        $drawingOverrides = '';
        for ($index = 1; $index <= $sheetCount; $index++) {
            $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            if ($hasLogo) {
                $drawingOverrides .= '<Override PartName="/xl/drawings/drawing'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
'.($hasLogo ? '<Default Extension="png" ContentType="image/png"/>' : '').'
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
'.$sheetOverrides.$drawingOverrides.'
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

    private function workbookXml(Collection $entries): string
    {
        $sheets = '';
        $definedNames = '';

        if ($entries->isEmpty()) {
            $sheets = '<sheet name="Tidak Ada Data" sheetId="1" r:id="rId1"/>';
            $definedNames = '<definedNames><definedName name="_xlnm.Print_Area" localSheetId="0">'.$this->escape($this->quotedSheetName('Tidak Ada Data').'!$A$1:$U$46').'</definedName></definedNames>';
        } else {
            $usedNames = [];
            foreach ($entries->values() as $index => $entry) {
                $name = $this->uniqueSheetName($this->sheetName($entry, $index + 1), $usedNames);
                $usedNames[] = $name;
                $sheets .= '<sheet name="'.$this->escapeAttribute($name).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
                $definedNames .= '<definedName name="_xlnm.Print_Area" localSheetId="'.$index.'">'.$this->escape($this->quotedSheetName($name).'!$A$1:$U$46').'</definedName>';
            }
            $definedNames = '<definedNames>'.$definedNames.'</definedNames>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>'.$sheets.'</sheets>
'.$definedNames.'
</workbook>';
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
<fonts count="9">
<font><sz val="12"/><color rgb="FF000000"/><name val="Arial"/></font>
<font><b/><sz val="16"/><color rgb="FF000000"/><name val="Times New Roman"/></font>
<font><b/><sz val="14"/><color rgb="FF000000"/><name val="Tahoma"/></font>
<font><sz val="12"/><color rgb="FF000000"/><name val="Tahoma"/></font>
<font><b/><sz val="18"/><color rgb="FF000000"/><name val="Tahoma"/></font>
<font><b/><sz val="12"/><color rgb="FF000000"/><name val="Arial"/></font>
<font><b/><u/><sz val="22"/><color rgb="FF000000"/><name val="Arial"/></font>
<font><sz val="12"/><color rgb="FF000000"/><name val="Arial"/></font>
<font><b/><u/><sz val="12"/><color rgb="FF000000"/><name val="Arial"/></font>
</fonts>
<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
<borders count="7">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left/><right/><top/><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border>
<border><left/><right/><top style="medium"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border>
<border><left/><right/><top/><bottom style="dotted"><color rgb="FF000000"/></bottom><diagonal/></border>
<border><left/><right/><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border>
<border><left/><right/><top style="thin"><color rgb="FF000000"/></top><bottom/><diagonal/></border>
<border><left/><right/><top style="hair"><color rgb="FF000000"/></top><bottom style="hair"><color rgb="FF000000"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="19">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="2" xfId="0" applyBorder="1"/>
<xf numFmtId="0" fontId="6" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="5" fillId="0" borderId="4" xfId="0" applyFont="1" applyBorder="1"/>
<xf numFmtId="0" fontId="7" fillId="0" borderId="4" xfId="0" applyFont="1" applyBorder="1"/>
<xf numFmtId="0" fontId="0" fillId="0" borderId="5" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>
<xf numFmtId="0" fontId="8" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="6" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="justify" vertical="top" wrapText="1"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>Export Kuitansi Perjadin</dc:title>
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

    private function sheetName(PerjadinEntry $entry, int $sequence): string
    {
        $name = trim($entry->executor_name ?: 'Kuitansi '.$sequence);

        return $sequence.' '.$name;
    }

    private function uniqueSheetName(string $name, array $usedNames): string
    {
        $base = trim(preg_replace('/[\[\]\:\*\?\/\\\\]+/', ' ', $name) ?? 'Kuitansi');
        $base = mb_substr(preg_replace('/\s+/', ' ', $base) ?? $base, 0, 31);
        $base = $base !== '' ? $base : 'Kuitansi';
        $candidate = $base;
        $suffix = 2;

        while (in_array($candidate, $usedNames, true)) {
            $marker = ' '.$suffix++;
            $candidate = mb_substr($base, 0, 31 - mb_strlen($marker)).$marker;
        }

        return $candidate;
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
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function quotedSheetName(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
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
}
