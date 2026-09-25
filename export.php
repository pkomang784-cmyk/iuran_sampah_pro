<?php
require 'config.php';
require_role('admin_keuangan');

$bulan = max(1, min(12, (int) ($_GET['bulan'] ?? date('n'))));
$tahun = max(2020, min(2100, (int) ($_GET['tahun'] ?? date('Y'))));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=laporan-' . $tahun . '-' . $bulan . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, [
	'No Kwitansi', 'Tanggal', 'No Pelanggan', 'Nama', 'Periode',
	'Petugas', 'Nominal', 'Metode',
]);

$pembayaran = $conn->query(
	"SELECT p.*, tg.bulan, tg.tahun, w.nomor_pelanggan, w.nama,
			u.nama AS petugas
	 FROM pembayaran p
	 JOIN tagihan tg ON tg.id = p.tagihan_id
	 JOIN warga w ON w.id = tg.warga_id
	 JOIN users u ON u.id = p.petugas_id
	 WHERE MONTH(p.tanggal_bayar) = $bulan
	   AND YEAR(p.tanggal_bayar) = $tahun
	   AND p.status = 'terverifikasi'
	 ORDER BY p.id"
);

while ($row = $pembayaran->fetch_assoc()) {
	fputcsv($output, [
		$row['nomor_kwitansi'],
		$row['tanggal_bayar'],
		$row['nomor_pelanggan'],
		$row['nama'],
		periode_label($row['bulan'], $row['tahun']),
		$row['petugas'],
		$row['nominal'],
		$row['metode'],
	]);
}

fclose($output);