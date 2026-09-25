DROP DATABASE IF EXISTS iuran_sampah;
CREATE DATABASE iuran_sampah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE iuran_sampah;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin_keuangan', 'petugas') NOT NULL,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE tarif (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nama VARCHAR(100) NOT NULL,
 nominal DECIMAL(12,2) NOT NULL,
 keterangan VARCHAR(255),
 aktif TINYINT(1) DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE warga (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nomor_pelanggan VARCHAR(30) NOT NULL UNIQUE,
 nama VARCHAR(100) NOT NULL,
 alamat TEXT NOT NULL,
 no_hp VARCHAR(30),
 wilayah VARCHAR(100),
 tarif_id INT NOT NULL,
 petugas_id INT NULL,
 status ENUM('aktif','nonaktif') DEFAULT 'aktif',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(tarif_id) REFERENCES tarif(id),
 FOREIGN KEY(petugas_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE tagihan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warga_id INT NOT NULL,
    bulan TINYINT NOT NULL,
    tahun SMALLINT NOT NULL,
    nominal DECIMAL(12,2) NOT NULL,
    jatuh_tempo DATE NOT NULL,
    status ENUM('belum_lunas','lunas','terlambat','batal') DEFAULT 'belum_lunas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tagihan (warga_id,bulan,tahun),
    FOREIGN KEY(warga_id) REFERENCES warga(id)
);

CREATE TABLE pembayaran (
 id INT AUTO_INCREMENT PRIMARY KEY,
 tagihan_id INT NOT NULL,
 petugas_id INT NOT NULL,
 tanggal_bayar DATE NOT NULL,
 nominal DECIMAL(12,2) NOT NULL,
 metode ENUM('tunai','transfer','qris') DEFAULT 'tunai',
 status ENUM('menunggu','terverifikasi','ditolak') DEFAULT 'terverifikasi',
 nomor_kwitansi VARCHAR(40) NOT NULL UNIQUE,
 keterangan VARCHAR(255),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(tagihan_id) REFERENCES tagihan(id),
 FOREIGN KEY(petugas_id) REFERENCES users(id)
);

CREATE TABLE pengeluaran (
 id INT AUTO_INCREMENT PRIMARY KEY,
 tanggal DATE NOT NULL,
 kategori VARCHAR(100) NOT NULL,
 keterangan VARCHAR(255) NOT NULL,
 nominal DECIMAL(12,2) NOT NULL,
 bukti VARCHAR(255),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE audit_log (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NULL,
 aksi VARCHAR(100) NOT NULL,
 tabel_data VARCHAR(100),
 data_id INT NULL,
 keterangan TEXT,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO users (nama,username,password,role) VALUES
('Administrator Keuangan','admin','$2y$12$LyvPtHduPHHSnsQXaRCpje9ji7YYhmNCg478G54ApSylGejOsmJKK','admin_keuangan'),
('Petugas Iuran','petugas','$2y$12$KkLpjycb7E5n0cNtorTxT.RAbtYxMS8qAzea2LS/xZ3asX6lzDcdu','petugas');

INSERT INTO tarif(nama,nominal,keterangan) VALUES
('Iuran Bulanan',10000,'Tarif standar bulanan');

INSERT INTO warga(nomor_pelanggan,nama,alamat,no_hp,wilayah,tarif_id) VALUES
('WS-0001','I Made Gunastra','Jl. Buana Putra, Br. Buana','081234567890','Br. Buana',1),
('WS-0002','Komang Sandana Putra','Jl. Contoh No. 10','081298765432','Br. Buana',1),
('WS-0003','Ni Made Sari','Jl. Contoh No. 20','082111223344','Br. Buana',1);
