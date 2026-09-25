<?php
require 'config.php';
require_role('admin_keuangan');

$page_title = 'Laporan Tahunan';
$tahun = max(2020, min(2100, (int) ($_GET['tahun'] ?? date('Y'))));

$pendapatanTahunan = $conn->query(
    "SELECT COALESCE(SUM(nominal), 0) AS total
     FROM pembayaran
     WHERE YEAR(tanggal_bayar) = $tahun
       AND status = 'terverifikasi'"
)->fetch_assoc()['total'] ?? 0;

$feeTahunan = (float) $pendapatanTahunan * 0.10;

$riwayatPembayaran = $conn->query(
    "SELECT p.*, w.nama AS warga, u.nama AS petugas
     FROM pembayaran p
     JOIN tagihan tg ON tg.id = p.tagihan_id
     JOIN warga w ON w.id = tg.warga_id
     JOIN users u ON u.id = p.petugas_id
     WHERE YEAR(p.tanggal_bayar) = $tahun
       AND p.status = 'terverifikasi'
     ORDER BY p.tanggal_bayar DESC, p.id DESC"
);

include 'partials/header.php';
?>

<div class="page-title">
    <div>
        <h2>Laporan Tahunan</h2>
        <p class="muted">Tahun <?= $tahun ?></p>
    </div>
</div>

<div class="card">
    <form class="search">
        <input type="number" name="tahun" value="<?= $tahun ?>" min="2020" max="2100">
        <button class="btn" type="submit">Tampilkan</button>
    </form>
</div>

<div class="cards">
    <div class="card stat">
        <span>Pemasukan Tahun Ini</span>
        <strong><?= rupiah($pendapatanTahunan) ?></strong>
    </div>

    <div class="card stat">
        <span>Fee Petugas Tahun Ini (10%)</span>
        <strong><?= rupiah($feeTahunan) ?></strong>
    </div>

    <div class="card stat">
        <span>Saldo Tahun Ini</span>
        <strong><?= rupiah($pendapatanTahunan - $feeTahunan) ?></strong>
    </div>
</div>

<div class="card tablewrap">
    <table>
        <tr>
            <th>Kwitansi</th>
            <th>Tanggal</th>
            <th>Warga</th>
            <th>Petugas</th>
            <th>Nominal</th>
        </tr>

        <?php while ($row = $riwayatPembayaran->fetch_assoc()): ?>
            <tr>
                <td><?= $row['nomor_kwitansi'] ?></td>
                <td><?= $row['tanggal_bayar'] ?></td>
                <td><?= e($row['warga']) ?></td>
                <td><?= e($row['petugas']) ?></td>
                <td><?= rupiah($row['nominal']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php include 'partials/footer.php'; ?>
