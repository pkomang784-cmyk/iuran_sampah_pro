<?php
require 'config.php';
require_login();

$page_title = 'Dashboard';
$bulan = (int) date('n');
$tahun = (int) date('Y');

$wargaAktif = $conn->query(
	"SELECT COUNT(*) AS total FROM warga WHERE status = 'aktif'"
)->fetch_assoc()['total'];

$tagihanBelumLunas = $conn->query(
	"SELECT COUNT(*) AS total
	 FROM tagihan
	 WHERE bulan = $bulan
	   AND tahun = $tahun
	   AND status IN ('belum_lunas', 'terlambat')"
)->fetch_assoc()['total'];

$pemasukan = $conn->query(
	"SELECT COALESCE(SUM(nominal), 0) AS total
	 FROM pembayaran
	 WHERE MONTH(tanggal_bayar) = $bulan
	   AND YEAR(tanggal_bayar) = $tahun
	   AND status = 'terverifikasi'"
)->fetch_assoc()['total'];

$feePetugas = (float) $pemasukan * 0.10;
$chart = [];

for ($bulanGrafik = 1; $bulanGrafik <= 12; $bulanGrafik++) {
	$totalBulan = $conn->query(
		"SELECT COALESCE(SUM(nominal), 0) AS total
		 FROM pembayaran
		 WHERE MONTH(tanggal_bayar) = $bulanGrafik
		   AND YEAR(tanggal_bayar) = $tahun
		   AND status = 'terverifikasi'"
	)->fetch_assoc()['total'];

	$chart[] = (float) $totalBulan;
}

include 'partials/header.php';
?>

<div class="page-title">
	<div>
		<h2>Dashboard</h2>
		<p class="muted">Ringkasan pengelolaan iuran bulan <?= periode_label($bulan, $tahun) ?></p>
	</div>
</div>

<div class="cards">
	<div class="card stat">
		<span>Warga Aktif</span>
		<strong><?= $wargaAktif ?></strong>
	</div>
	<div class="card stat">
		<span>Tagihan Belum Lunas</span>
		<strong><?= $tagihanBelumLunas ?></strong>
	</div>
	<div class="card stat">
		<span>Pemasukan Bulan Ini</span>
		<strong><?= rupiah($pemasukan) ?></strong>
	</div>
	<div class="card stat">
		<span>Fee Petugas (10%)</span>
		<strong><?= rupiah($feePetugas) ?></strong>
	</div>
</div>

<div class="grid2">
	<div class="card">
		<h3>Grafik Pemasukan <?= $tahun ?></h3>
		<canvas id="income"></canvas>
	</div>
	<div class="card">
		<h3>Ringkasan</h3>
		<div class="summary">
			<p>Total pemasukan <b><?= rupiah($pemasukan) ?></b></p>
			<p>Fee petugas (10%) <b><?= rupiah($feePetugas) ?></b></p>
			<p>Saldo berjalan <b><?= rupiah($pemasukan - $feePetugas) ?></b></p>
			<a class="btn primary" href="laporan.php">Buka Laporan</a>
		</div>
	</div>
</div>

<script>
	new Chart(document.getElementById('income'), {
		type: 'bar',
		data: {
			labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
			datasets: [{
				label: 'Pemasukan',
				data: <?= json_encode($chart) ?>
			}]
		},
		options: {
			responsive: true,
			plugins: { legend: { display: false } }
		}
	});
</script>

<?php include 'partials/footer.php'; ?>