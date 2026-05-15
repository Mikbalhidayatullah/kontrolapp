<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Perjadin</title>
    <style>
        @page {
            margin: 16mm 16mm 16mm 16mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 10.5px;
            line-height: 1.6;
            margin: 0;
        }

        .section {
            margin-bottom: 18px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 8px;
        }

        .summary-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            width: 25%;
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            vertical-align: top;
            background: #f8fafc;
        }

        .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 4px;
        }

        .value {
            font-size: 12px;
            font-weight: bold;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 9px;
            vertical-align: top;
            text-align: left;
        }

        .data-table th {
            background: #e2e8f0;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="section">
        <table class="summary-table">
            <tr>
                <td>
                    <div class="label">Kategori</div>
                    <div class="value">{{ $entry->category }}</div>
                </td>
                <td>
                    <div class="label">Pelaksana</div>
                    <div class="value">{{ $entry->executor_name }}</div>
                </td>
                <td>
                    <div class="label">Periode</div>
                    <div class="value">{{ $periodLabel }}</div>
                </td>
                <td>
                    <div class="label">Grand Total</div>
                    <div class="value">Rp {{ number_format($entry->grand_total, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">01 Informasi Umum</div>
        <table class="data-table">
            <tr><th>Field</th><th>Nilai</th></tr>
            <tr><td>Nama SKPD</td><td>{{ $entry->skpd_name }}</td></tr>
            <tr><td>Nama Pelaksana</td><td>{{ $entry->executor_name }}</td></tr>
            <tr><td>Jabatan</td><td>{{ $entry->position_name }}</td></tr>
            <tr><td>Golongan</td><td>{{ $entry->grade }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">02 Jangka Waktu Surat Perintah Tugas</div>
        <table class="data-table">
            <tr><th>Field</th><th>Nilai</th></tr>
            <tr><td>Dari</td><td>{{ optional($entry->start_date)->translatedFormat('d M Y') }}</td></tr>
            <tr><td>Sampai</td><td>{{ optional($entry->end_date)->translatedFormat('d M Y') }}</td></tr>
            <tr><td>No Surat Tugas</td><td>{{ $entry->assignment_number }}</td></tr>
            <tr><td>Tanggal Surat Tugas</td><td>{{ optional($entry->assignment_date)->translatedFormat('d M Y') }}</td></tr>
            <tr><td>Lokasi TTD</td><td>{{ $entry->signature_location ?: '-' }}</td></tr>
            <tr><td>Kota / Kab Tujuan</td><td>{{ $entry->destination_city }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">03 Bukti Sesuai SPPD</div>
        @foreach ($costGroups as $group)
            <table class="data-table" style="margin-bottom: 12px;">
                <tr>
                    <th colspan="2">{{ $group['title'] }} — {{ $group['enabled'] ? 'Aktif' : 'Tidak digunakan' }}</th>
                </tr>
                @if ($group['enabled'])
                    @foreach ($group['rows'] as $row)
                        <tr>
                            <td style="width: 38%;">{{ $row['label'] }}</td>
                            <td>{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" class="muted">Tidak ada input untuk bagian ini.</td>
                    </tr>
                @endif
            </table>
        @endforeach
    </div>

    <div class="section">
        <div class="section-title">04 Dokumentasi</div>
        <table class="data-table">
            <tr><th>Dokumen</th><th>Status</th></tr>
            <tr>
                <td>Kegiatan</td>
                <td>{{ $entry->activity_file_original_name ?: 'Belum ada file kegiatan' }}</td>
            </tr>
            <tr>
                <td>Bukti Nota / Tiket</td>
                <td>{{ $entry->receipt_file_original_name ?: 'Belum ada file nota / tiket' }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
