<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Perjadin</title>
    <style>
        @page {
            margin: 16mm 18mm 18mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.38;
            margin: 0;
        }

        .sheet {
            min-height: 257mm;
        }

        .kop {
            text-align: center;
            margin-bottom: 6px;
        }

        .kop-line-1 {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .kop-line-2 {
            font-size: 14px;
            font-weight: 700;
            margin-top: 2px;
        }

        .kop-line-3 {
            font-size: 12px;
            margin-top: 2px;
        }

        .divider {
            border-top: 2px solid #111827;
            margin: 8px 0 12px;
        }

        .title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin: 0;
        }

        .receipt-number {
            text-align: center;
            font-size: 12px;
            margin-top: 4px;
            margin-bottom: 12px;
        }

        .meta-table,
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .meta-table td:first-child {
            width: 130px;
        }

        .meta-table td:nth-child(2) {
            width: 14px;
        }

        .terbilang-box {
            border: 1px solid #9ca3af;
            padding: 7px 10px;
            margin: 6px 0 10px;
            font-style: italic;
        }

        .label-rincian {
            margin-bottom: 6px;
            font-weight: 700;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
            vertical-align: top;
        }

        .detail-table th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: left;
        }

        .detail-table td:last-child,
        .detail-table th:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .detail-table td:first-child,
        .detail-table th:first-child {
            width: 34px;
            text-align: center;
        }

        .detail-total td {
            font-weight: 700;
            background: #f9fafb;
        }

        .receipt-date {
            margin-top: 14px;
            text-align: right;
        }

        .recipient-block {
            width: 240px;
            margin-left: auto;
            margin-top: 6px;
            text-align: center;
        }

        .stamp {
            margin-top: 26px;
            font-size: 11px;
        }

        .signature-name {
            margin-top: 18px;
            font-weight: 700;
            text-decoration: underline;
        }

        .signature-nip {
            margin-top: 4px;
        }

        .approval-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }

        .approval-grid td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }

        .approval-title {
            font-weight: 700;
        }

        .approval-subtitle {
            margin-top: 2px;
        }

        .approval-space {
            height: 54px;
        }

        .approval-name {
            font-weight: 700;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="kop">
            <div class="kop-line-1">PEMERINTAH PROVINSI MALUKU UTARA</div>
            <div class="kop-line-2">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
            <div class="kop-line-3">Jln. Raya Sultan Nuku, Sofifi</div>
        </div>

        <div class="divider"></div>

        <div class="title">KWITANSI</div>
        <div class="receipt-number">Nomor: {{ $receiptNumber }}</div>

        <table class="meta-table">
            <tr>
                <td>Sudah terima dari</td>
                <td>:</td>
                <td>{{ $receivedFrom }}</td>
            </tr>
            <tr>
                <td>Sebesar</td>
                <td>:</td>
                <td>{{ $grandTotalLabel }}</td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="terbilang-box">Terbilang rupiah: {{ $grandTotalWords }}</div>
                </td>
            </tr>
            <tr>
                <td>Untuk pengeluaran</td>
                <td>:</td>
                <td>{{ $paymentPurpose }}</td>
            </tr>
        </table>

        <div class="label-rincian">Dengan rincian :</div>

        <table class="detail-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Uraian</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($receiptBreakdown as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['description'] }}</td>
                        <td>{{ $item['total_label'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>1</td>
                        <td>Biaya perjalanan dinas sesuai rincian SPPD</td>
                        <td>{{ $grandTotalLabel }}</td>
                    </tr>
                @endforelse
                <tr class="detail-total">
                    <td colspan="2">Total Jumlah</td>
                    <td>{{ $grandTotalLabel }}</td>
                </tr>
            </tbody>
        </table>

        <div class="receipt-date">{{ $receiptPlace }}, {{ \Carbon\Carbon::parse($receiptDate)->translatedFormat('d F Y') }}</div>

        <div class="recipient-block">
            <div>Penerima,</div>
            <div class="stamp">Materai 10rb</div>
            <div class="signature-name">{{ $recipientName }}</div>
            <div class="signature-nip">NIP. {{ $recipientNip }}</div>
        </div>

        <table class="approval-grid">
            <tr>
                <td>
                    <div class="approval-title">Setuju Dibayar</div>
                    <div class="approval-subtitle">Kepala Badan</div>
                    <div class="approval-space"></div>
                    <div class="approval-name">{{ $approverName }}</div>
                    <div>NIP. {{ $approverNip }}</div>
                </td>
                <td>
                    <div class="approval-title">Lunas Dibayar</div>
                    <div class="approval-subtitle">Bendahara Pengeluaran</div>
                    <div class="approval-space"></div>
                    <div class="approval-name">{{ $treasurerName }}</div>
                    <div>NIP. {{ $treasurerNip }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
