<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Perjadin</title>
    <style>
        @page {
            margin: 18mm 18mm 18mm 18mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.65;
            margin: 0;
        }

        .sheet {
            min-height: 255mm;
        }

        .office-name {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .office-subname {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .office-caption {
            text-align: center;
            font-size: 10px;
            color: #475569;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .header-table td {
            vertical-align: top;
        }

        .title-cell {
            width: 100%;
            text-align: center;
        }

        .title-main {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 5px;
            margin: 0;
        }

        .title-sub {
            font-size: 10px;
            margin-top: 6px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .number-box {
            width: 210px;
            border: 1px solid #0f172a;
            padding: 8px 10px;
            font-size: 10px;
            text-align: center;
        }

        .rule {
            border-top: 2px solid #0f172a;
            margin: 10px 0 16px;
        }

        .rule-thin {
            border-top: 1px solid #334155;
            margin: -12px 0 18px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .meta td {
            vertical-align: top;
            padding: 3px 0;
        }

        .meta td:first-child {
            width: 150px;
        }

        .meta td:nth-child(2) {
            width: 16px;
        }

        .amount-band {
            margin: 18px 0 12px;
            border-top: 1.3px solid #0f172a;
            border-bottom: 1.3px solid #0f172a;
            padding: 12px 0;
        }

        .amount-table {
            width: 100%;
            border-collapse: collapse;
        }

        .amount-table td {
            vertical-align: top;
        }

        .amount-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #475569;
        }

        .amount-main {
            font-size: 22px;
            font-weight: bold;
            margin-top: 4px;
        }

        .terbilang-box {
            border: 1px solid #94a3b8;
            padding: 10px 12px;
            margin-top: 12px;
            font-style: italic;
        }

        .body-copy {
            margin-top: 18px;
            text-align: justify;
        }

        .signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 70px;
        }

        .signature td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .signature-sub {
            font-size: 10px;
            color: #475569;
        }

        .signature-space {
            height: 78px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .manual-line {
            margin: 0 auto 6px;
            width: 180px;
            text-align: center;
            letter-spacing: 0.16em;
            color: #334155;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="office-name">Dokumen Kwitansi Perjalanan Dinas</div>
        <div class="office-subname">{{ strtoupper($entry->skpd_name) }}</div>
        <div class="office-caption">Bukti penerimaan uang perjalanan dinas</div>

        <table class="header-table">
            <tr>
                <td class="title-cell">
                    <p class="title-main">KWITANSI</p>
                    <div class="title-sub">Bukti Penerimaan Uang</div>
                </td>
                <td style="width: 220px; text-align: right;">
                    <div class="number-box">
                        <div>No. Kwitansi</div>
                        <div style="margin-top: 4px; font-weight: bold;">{{ $receiptNumber }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="rule"></div>
        <div class="rule-thin"></div>

        <table class="meta">
            <tr>
                <td>Sudah terima dari</td>
                <td>:</td>
                <td>{{ $receivedFrom }}</td>
            </tr>
            <tr>
                <td>Untuk pembayaran</td>
                <td>:</td>
                <td>{{ $paymentPurpose }}</td>
            </tr>
            <tr>
                <td>Kategori Perjadin</td>
                <td>:</td>
                <td>{{ $entry->category }}</td>
            </tr>
            <tr>
                <td>Nama Pelaksana</td>
                <td>:</td>
                <td>{{ $entry->executor_name }}</td>
            </tr>
            <tr>
                <td>Tujuan</td>
                <td>:</td>
                <td>{{ $entry->destination_city }}</td>
            </tr>
            <tr>
                <td>No. Surat Tugas</td>
                <td>:</td>
                <td>{{ $entry->assignment_number }}</td>
            </tr>
        </table>

        <div class="amount-band">
            <table class="amount-table">
                <tr>
                    <td style="width: 220px;">
                        <div class="amount-label">Banyaknya Uang</div>
                        <div class="amount-main">{{ $grandTotalLabel }}</div>
                    </td>
                    <td>
                        <div class="terbilang-box">
                            Terbilang: {{ $grandTotalWords }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="body-copy">
            Telah diterima uang sejumlah tersebut di atas untuk keperluan perjalanan dinas sesuai rincian SPPD
            pada data perjadin yang tersimpan dalam sistem.
        </div>

        <table class="signature">
            <tr>
                <td>
                    <div class="signature-label">Mengetahui,</div>
                    <div class="signature-sub">{{ $entry->skpd_name }}</div>
                    <div class="signature-space"></div>
                    <div class="manual-line">........................................</div>
                    <div class="signature-sub">Pejabat yang berwenang</div>
                </td>
                <td>
                    <div class="signature-label">{{ $receiptPlace }}, {{ \Carbon\Carbon::parse($receiptDate)->translatedFormat('d F Y') }}</div>
                    <div class="signature-sub">Yang menerima,</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $recipientName }}</div>
                    <div class="signature-sub">{{ $recipientPosition }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
