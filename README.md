# SISTEM PENGELOLAAN IURAN SAMPAH - PROFESSIONAL
PHP 8+ / MySQL 5.7+ / Bootstrap-style custom CSS / Chart.js CDN

## Modul
- Login role-based: Admin Keuangan & Petugas Iuran
- Dashboard statistik dan grafik
- Master warga/pelanggan
- Tarif iuran yang dapat diatur admin
- Tagihan bulanan otomatis dibuat dari warga aktif
- Pembayaran dan verifikasi
- Status lunas / belum lunas / terlambat
- Tunggakan dan filter periode
- Cetak kwitansi
- Laporan pemasukan, pengeluaran, dan saldo
- Export CSV
- Manajemen pengguna
- Export dan import data pelanggan melalui Excel (.xls/.xlsx)
- Profil
- Audit log sederhana
- Responsive

## Instalasi XAMPP
1. Extract folder ke C:\xampp\htdocs\
2. Start Apache + MySQL.
3. phpMyAdmin -> Import `database.sql`
4. Buka http://localhost/iuran_sampah_professional/
5. Login:
   Admin: admin / admin123
   Petugas: petugas / petugas123

## Catatan
Project menggunakan password_hash/password_verify dan prepared statements untuk input utama.
Untuk produksi, aktifkan HTTPS, CSRF token, backup database, pembatasan upload, dan konfigurasi database yang aman.

## Export dan import pelanggan
Admin dapat membuka menu Data Warga lalu memilih Export Excel atau Import Excel.
Kolom import harus berurutan: No Pelanggan, Nama, Alamat, No HP, Wilayah, Tarif, Jumlah Iuran, Status.
Kolom Jumlah Iuran boleh dibiarkan kosong saat import karena nominal mengikuti Tarif.
Tarif dapat diisi dengan nama tarif atau ID tarif. Nomor pelanggan yang sudah ada akan diperbarui;
nomor baru akan ditambahkan sebagai pelanggan baru.
