<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuitansi Perjadin</title>
    <style>
        @page {
            margin: 16mm 18mm 18mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.18;
            margin: 0;
        }

        .sheet {
            min-height: 257mm;
        }

        .kop {
            margin-bottom: 6px;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kop-logo-cell {
            width: 78px;
            vertical-align: middle;
            text-align: left;
        }

        .kop-text-cell {
            vertical-align: middle;
            text-align: center;
            padding-right: 32px;
        }

        .kop-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            display: block;
        }

        .kop-line-1 {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1.05;
        }

        .kop-line-2 {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.05;
            margin-top: 0;
        }

        .kop-line-3 {
            font-size: 13px;
            line-height: 1.05;
            margin-top: 0;
        }
        
        .kop-line-4 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.38em;
            line-height: 1.05;
            margin-top: 1px;
            padding-left: 0.38em;
        }

        .divider {
            border-top: 2px solid #111827;
            border-bottom: 1px solid #111827;
            height: 2px;
            margin: 6px 0 12px;
        }

        .receipt-head-meta,
        .meta-table,
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-head-meta {
            margin-bottom: 10px;
            font-size: 12px;
        }

        .receipt-head-left {
            width: 50%;
            vertical-align: top;
        }

        .receipt-head-right {
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .receipt-head-inner {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-head-inner td {
            padding: 0;
            vertical-align: top;
        }

        .receipt-head-inner td:first-child {
            width: 96px;
        }

        .receipt-head-inner td:nth-child(2) {
            width: 14px;
        }

        .receipt-head-inner-right {
            margin-left: auto;
            width: 220px;
        }

        .receipt-head-inner-right td:first-child {
            width: 126px;
        }

        .receipt-head-label,
        .receipt-head-label-year,
        .receipt-head-colon {
            white-space: nowrap;
        }

        .title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-decoration: underline;
            margin: 0 0 10px;
        }

        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .meta-table td:first-child {
            width: 130px;
        }

        .meta-table td:nth-child(2) {
            width: 14px;
        }

        .meta-italic {
            font-style: italic;
        }

        .meta-line {
            border-bottom: 1px solid #111827;
            line-height: 1.12;
            min-height: 13px;
            padding-left: 2px;
        }

        .meta-line-multi {
            min-height: 42px;
            background-image: repeating-linear-gradient(
                to bottom,
                transparent 0,
                transparent 12px,
                #111827 12px,
                #111827 13px
            );
        }

        .label-rincian {
            width: 100%;
            display: table;
            margin-top: 8px;
            margin-bottom: 4px;
            font-weight: 400;
        }

        .label-rincian-row {
            display: table-row;
        }

        .label-rincian-spacer,
        .label-rincian-colon,
        .label-rincian-text {
            display: table-cell;
            vertical-align: top;
        }

        .label-rincian-spacer {
            width: 130px;
        }

        .label-rincian-colon {
            width: 14px;
        }

        .detail-list-wrap {
            margin-left: 144px;
            width: calc(100% - 144px);
        }

        .detail-list {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .detail-list td {
            padding: 1px 0;
            vertical-align: top;
            line-height: 1.18;
        }

        .detail-list-no {
            width: 18px;
        }

        .detail-list-title {
            width: 250px;
        }

        .detail-list-colon {
            width: 14px;
            text-align: center;
        }

        .receipt-date {
            width: 160px;
            margin-top: 12px;
            margin-left: auto;
            margin-right: 8mm;
            text-align: center;
        }

        .recipient-block {
            width: 160px;
            margin-left: auto;
            margin-right: 8mm;
            margin-top: 4px;
            text-align: center;
        }

        .stamp {
            margin-top: 24px;
            font-size: 11px;
        }

        .signature-name {
            margin-top: 16px;
            font-weight: 700;
            text-decoration: underline;
            white-space: nowrap;
        }

        .signature-nip {
            margin-top: 4px;
        }

        .approval-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .approval-grid td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }

        .approval-block {
            text-align: center;
        }

        .approval-block-left {
            width: 230px;
            margin-left: 8mm;
            margin-right: auto;
        }

        .approval-block-right {
            width: 160px;
            margin-left: auto;
            margin-right: 8mm;
        }

        .approval-title {
            font-weight: 700;
        }

        .approval-subtitle {
            margin-top: 2px;
        }

        .approval-subtitle-plain {
            margin-top: 29px;
        }

        .approval-space {
            height: 52px;
        }

        .approval-name {
            font-weight: 700;
            text-decoration: underline;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    @php
        $receiptLogoPath = public_path('images/logos/maluku_utara.png');
        $receiptLogoDataUri = null;

        if (file_exists($receiptLogoPath)) {
            $receiptLogoMime = mime_content_type($receiptLogoPath) ?: 'image/png';
            $receiptLogoDataUri = 'data:'.$receiptLogoMime.';base64,'.base64_encode(file_get_contents($receiptLogoPath));
        }
    @endphp
    <div class="sheet">
        <div class="kop">
            <table class="kop-table">
                <tr>
                    <td class="kop-logo-cell">
                        @if ($receiptLogoDataUri)
                            <img src="{{ $receiptLogoDataUri }}" alt="Logo Provinsi Maluku Utara" class="kop-logo">
                        @endif
                    </td>
                    <td class="kop-text-cell">
                        <div class="kop-line-1">PEMERINTAH PROVINSI MALUKU UTARA</div>
                        <div class="kop-line-2">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                        <div class="kop-line-3">Jln. Raya Sultan Nuku, Kota Tidore Kepulauan</div>
                        <div class="kop-line-4">SOFIFI</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <table class="receipt-head-meta">
            <tr>
                <td class="receipt-head-left">
                    <table class="receipt-head-inner">
                        <tr>
                            <td class="receipt-head-label">No. kuitansi</td>
                            <td class="receipt-head-colon">:</td>
                            <td>{{ $receiptNumber }}</td>
                        </tr>
                        <tr>
                            <td class="receipt-head-label">Lembaran</td>
                            <td class="receipt-head-colon">:</td>
                            <td>{{ $sheetTitle }}</td>
                        </tr>
                    </table>
                </td>
                <td class="receipt-head-right">
                    <table class="receipt-head-inner receipt-head-inner-right">
                        <tr>
                            <td class="receipt-head-label-year">Tahun Anggaran</td>
                            <td class="receipt-head-colon">:</td>
                            <td>{{ $budgetYear }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="title">KUITANSI</div>

        <table class="meta-table">
            <tr>
                <td>Sudah terima dari</td>
                <td>:</td>
                <td class="meta-line">{{ $receivedFrom }}</td>
            </tr>
            <tr>
                <td>Sebesar</td>
                <td>:</td>
                <td class="meta-line">{{ $grandTotalLabel }}</td>
            </tr>
            <tr>
                <td>Terbilang</td>
                <td>:</td>
                <td class="meta-line"><span class="meta-italic">{{ $grandTotalWords }}</span></td>
            </tr>
            <tr>
                <td>Untuk pengeluaran</td>
                <td>:</td>
                <td class="meta-line meta-line-multi">{{ $paymentPurpose }}</td>
            </tr>
        </table>

        <div class="label-rincian">
            <div class="label-rincian-row">
                <span class="label-rincian-spacer"></span>
                <span class="label-rincian-colon"></span>
                <span class="label-rincian-text">dengan rincian :</span>
            </div>
        </div>

        <div class="detail-list-wrap">
            <table class="detail-list">
                <tbody>
                    @forelse ($receiptBreakdown as $index => $item)
                        <tr>
                            <td class="detail-list-no">{{ $index + 1 }}.</td>
                            <td class="detail-list-title">{{ $item['title'] ?? $item['description'] }}</td>
                            <td class="detail-list-colon">:</td>
                            <td>{{ $item['calculation_label'] ?? $item['total_label'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="detail-list-no">1.</td>
                            <td class="detail-list-title">Biaya perjalanan dinas sesuai rincian SPPD</td>
                            <td class="detail-list-colon">:</td>
                            <td>{{ $grandTotalLabel }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="receipt-date">{{ $receiptPlace }}, {{ \Carbon\Carbon::parse($receiptDate)->translatedFormat('d F Y') }}</div>

        <div class="recipient-block">
            <div>Penerima</div>
            <div class="stamp">Materai 10rb</div>
            <div class="signature-name">{{ $recipientName }}</div>
            <div class="signature-nip">NIP. {{ $recipientNip }}</div>
        </div>

        <table class="approval-grid">
            <tr>
                <td>
                    <div class="approval-block approval-block-left">
                        <div class="approval-title">Mengetahui dan Menyetujui :</div>
                        <div class="approval-subtitle">Kepala Dinas Pendidikan Dan Kebudayaan</div>
                        <div>Provinsi Maluku Utara</div>
                        <div class="approval-space"></div>
                        <div class="approval-name">{{ $approverName }}</div>
                        <div>NIP. {{ $approverNip }}</div>
                    </div>
                </td>
                <td>
                    <div class="approval-block approval-block-right">
                        <div class="approval-subtitle approval-subtitle-plain">Bendahara Pengeluaran,</div>
                        <div class="approval-space"></div>
                        <div class="approval-name">{{ $treasurerName }}</div>
                        <div>NIP. {{ $treasurerNip }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
