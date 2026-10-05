# Desain Impor Lengkap LRFK Perubahan dan Data Olahan

## Tujuan

Memasukkan seluruh data tabel dari sheet `DATA RAPAT` ke LRFK Perubahan dan dari sheet `Data OLAHAN` ke LRFK Data Olahan. Struktur baris, urutan, baris duplikat, baris rincian tanpa kode rekening, dan nilai pada setiap kolom harus dipertahankan. LRFK Lama dan hubungan Perjadin tetap tidak berubah.

## Sumber Data

- Workbook: `LRFK Dikbud 2026 (1).xlsx`
- LRFK Perubahan: sheet `DATA RAPAT`
- LRFK Data Olahan: sheet `Data OLAHAN`

Hasil impor yang diharapkan:

- LRFK Perubahan: 349 baris utama dan 3 baris rincian, total 352 baris fisik.
- LRFK Data Olahan: 364 baris utama dan 218 baris rincian, total 582 baris fisik.
- Baris duplikat tetap disimpan sebagai baris terpisah.

## Struktur Data

Baris yang memiliki Program, Kegiatan, Sub Kegiatan, atau kode rekening tetap berada di tabel `lrfk_entries`.

Baris dengan kolom A-D kosong tetapi memiliki data pada kolom berikutnya disimpan di tabel baru `lrfk_entry_details`. Setiap rincian ditautkan ke baris rekening terakhir di atasnya melalui `lrfk_entry_id`. Rincian tidak menjadi rekening baru dan tidak memiliki pagu sendiri.

Tabel rincian menyimpan:

- Urutan sumber dan nomor baris sumber.
- Nilai kontrak.
- Nomor/tanggal kontrak jika tersedia.
- Pelaksana.
- Keluaran.
- Volume dan satuan.
- Realisasi keuangan.
- Persentase keuangan dan fisik.
- Sisa pagu.
- Rencana kas Oktober, November, Desember, dan total triwulan.
- Lokasi.
- Keterangan.
- Selisih.

Kolom tambahan untuk sisa pagu, rencana kas, dan selisih juga ditambahkan ke `lrfk_entries` agar nilai pada baris utama dapat disimpan tanpa kehilangan data.

## Pemetaan Kolom

### DATA RAPAT

| Excel | Data aplikasi |
| --- | --- |
| A | Kode/jenis baris |
| B | Kode rekening |
| C | Program/kegiatan/sub kegiatan |
| D | Pagu anggaran |
| E | Nilai kontrak |
| F | Pelaksana |
| G | Keluaran |
| H | Volume |
| I | Satuan |
| J | Realisasi keuangan |
| K | Persentase keuangan |
| L | Persentase fisik |
| M | Sisa pagu anggaran |
| N | Rencana kas Oktober |
| O | Rencana kas November |
| P | Rencana kas Desember |
| Q | Total rencana kas triwulan |
| R | Lokasi |
| S | Keterangan |
| T | Selisih |

`contract_number_date` dikosongkan karena sheet ini tidak memiliki kolom Nomor/Tanggal.

### Data OLAHAN

| Excel | Data aplikasi |
| --- | --- |
| A | Kode/jenis baris |
| B | Kode rekening |
| C | Program/kegiatan/sub kegiatan |
| D | Pagu anggaran |
| E | Nilai kontrak |
| F | Nomor/tanggal kontrak |
| G | Pelaksana |
| H | Keluaran |
| I | Volume |
| J | Satuan |
| K | Realisasi keuangan |
| L | Persentase keuangan |
| M | Persentase fisik |
| N | Lokasi |
| O | Keterangan |
| P | Selisih |

Kolom rencana kas dan sisa pagu dikosongkan karena tidak tersedia pada sheet ini.

## Tampilan Web

Tabel LRFK menggunakan susunan kolom berdasarkan versi yang dipilih.

- LRFK Lama mempertahankan tampilan yang sekarang.
- LRFK Perubahan menampilkan kolom sesuai `DATA RAPAT`, termasuk sisa pagu dan rencana kas.
- LRFK Data Olahan menampilkan kolom sesuai `Data OLAHAN`, termasuk Nomor/Tanggal dan Selisih.

Setelah sebuah rekening, rincian terkait dirender sebagai baris berikutnya. Kolom A-D pada rincian tetap kosong agar struktur visualnya mengikuti Excel. Semua nilai rincian tetap sejajar pada kolom asalnya.

Ringkasan bagian atas memakai nilai baris Dinas dari sheet untuk pagu, kontrak, dan realisasi. Hal ini mencegah duplikat fisik menyebabkan total tingkat Dinas berubah.

## Sinkronisasi dan Keamanan Data

Migration baru melakukan hal berikut:

1. Menambah kolom baru pada `lrfk_entries`.
2. Membuat `lrfk_entry_details` dengan foreign key `cascadeOnDelete` karena rincian sepenuhnya dimiliki oleh rekening induknya.
3. Memperbarui baris utama Perubahan dan Data Olahan berdasarkan versi, urutan, identitas baris, dan kemunculan duplikat.
4. Memasukkan seluruh baris rincian sesuai rekening induknya.
5. Tidak mengubah atau menghapus baris LRFK Lama.
6. Tidak mengubah `lrfk_entry_id` pada Perjadin.
7. Menjaga ID baris utama yang sudah ada.

Untuk Perubahan dan Data Olahan, workbook menjadi sumber resmi bagi kolom yang diimpor. Kolom tersebut diperbarui sesuai workbook. Data audit seperti pembuat, pengubah, dan ID baris utama tidak diganti.

## Download Excel

Ekspor selalu mengambil seluruh dataset versi terpilih tanpa mengikuti filter pencarian.

- Ekspor LRFK Perubahan mengikuti susunan `DATA RAPAT` dan menyertakan baris rincian.
- Ekspor LRFK Data Olahan mengikuti susunan `Data OLAHAN` dan menyertakan baris rincian.
- Ekspor LRFK Lama tetap memakai format yang sekarang.

Nilai numerik diekspor sebagai angka, bukan teks, sehingga tetap dapat dihitung di Excel.

## Pengujian

Pengujian otomatis harus membuktikan:

- Jumlah baris utama dan rincian sesuai sumber.
- Setiap rincian terhubung ke rekening terakhir di atasnya.
- Baris duplikat tetap ada dengan urutan yang benar.
- Nilai Dinas untuk pagu, kontrak, realisasi, dan persentase sama dengan workbook.
- Nilai negatif pada sisa pagu dan selisih tersimpan dengan benar.
- Pemetaan kolom berbeda antara kedua sheet tidak tertukar.
- Filter versi, tampilan, dan ekspor tidak mencampur dataset.
- LRFK Lama dan hierarki Perjadin tetap tidak berubah.
- Migration aman dijalankan pada MySQL hosting yang sudah berisi dataset versi sebelumnya.

## Kriteria Selesai

- Seluruh 352 baris fisik DATA RAPAT tampil pada LRFK Perubahan.
- Seluruh 582 baris fisik Data OLAHAN tampil pada LRFK Data Olahan.
- Tidak ada nilai sumber yang hilang atau berpindah kolom.
- Tampilan dan ekspor mempertahankan urutan Excel.
- Semua pengujian, pemeriksaan sintaks, migration MySQL, dan kompilasi Blade berhasil.
