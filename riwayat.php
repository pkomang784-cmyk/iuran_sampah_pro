<?php
require 'config.php';
require_login();

if (!in_array($_SESSION['user']['role'], ['admin_keuangan', 'petugas'], true)) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Riwayat Pembayaran';
$where = '1=1';

if ($_SESSION['user']['role'] === 'petugas') {
    $where = 'p.petugas_id = ' . (int) $_SESSION['user']['id'];
}

$res = $conn->query(
    "SELECT p.*, tg.bulan, tg.tahun, w.nomor_pelanggan, w.nama
     FROM pembayaran p
     JOIN tagihan tg ON tg.id = p.tagihan_id
     JOIN warga w ON w.id = tg.warga_id
     WHERE $where
     ORDER BY p.id DESC
     LIMIT 200"
);

include 'partials/header.php';
?>

<div class="page-title">
    <div>
        <h2>Riwayat Pembayaran</h2>
        <p class="muted"><?= $_SESSION['user']['role'] === 'petugas' ? 'Transaksi yang dicatat oleh Anda.' : 'Semua transaksi pembayaran.' ?></p>
    </div>
</div>

<div class="card tablewrap">
    <table>
        <tr>
            <th>Kwitansi</th>
            <th>Tanggal</th>
            <th>No. Pelanggan</th>
            <th>Nama</th>
            <th>Periode</th>
            <th>Nominal</th>
            <th>Metode</th>
            <th>Aksi</th>
        </tr>

        <?php while ($r = $res->fetch_assoc()): ?>
            <tr>
                <td><?= e($r['nomor_kwitansi']) ?></td>
                <td><?= e($r['tanggal_bayar']) ?></td>
                <td><?= e(format_nomor_pelanggan($r['nomor_pelanggan'])) ?></td>
                <td><?= e($r['nama']) ?></td>
                <td><?= periode_label($r['bulan'], $r['tahun']) ?></td>
                <td><?= rupiah($r['nominal']) ?></td>
                <td><?= e($r['metode']) ?></td>
                <td><a class="btn small" href="kwitansi.php?no=<?= urlencode($r['nomor_kwitansi']) ?>">Cetak</a></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php include 'partials/footer.php'; ?>