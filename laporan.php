<?php
require 'config.php';
require_role('admin_keuangan');

$page_title = 'Laporan';
$bulan = max(1, min(12, (int) ($_GET['bulan'] ?? date('n'))));
$tahun = max(2020, min(2100, (int) ($_GET['tahun'] ?? date('Y'))));
$petugasId = max(0, (int) ($_GET['petugas_id'] ?? 0));
$filterPetugas = $petugasId > 0 ? ' AND p.petugas_id = ' . $petugasId : '';
$filterPetugasJoin = $petugasId > 0 ? ' AND u.id = ' . $petugasId : '';

$daftarPetugas = $conn->query(
    "SELECT id, nama FROM users WHERE role = 'petugas' ORDER BY nama"
);

$pendapatan = $conn->query(
    "SELECT COALESCE(SUM(nominal), 0) AS total
         FROM pembayaran p
         WHERE MONTH(p.tanggal_bayar) = $bulan
             AND YEAR(p.tanggal_bayar) = $tahun
             AND p.status = 'terverifikasi'" . $filterPetugas
)->fetch_assoc()['total'] ?? 0;

$feePetugas = (float) $pendapatan * 0.10;

$rekapPetugas = $conn->query(
        "SELECT u.nama AS petugas,
                        COUNT(p.id) AS jumlah_transaksi,
                        COALESCE(SUM(p.nominal), 0) AS total_setoran,
                        COALESCE(SUM(p.nominal), 0) * 0.10 AS fee_petugas,
                        MAX(p.tanggal_bayar) AS pembayaran_terakhir
         FROM users u
         LEFT JOIN pembayaran p ON p.petugas_id = u.id
                AND MONTH(p.tanggal_bayar) = $bulan
                AND YEAR(p.tanggal_bayar) = $tahun
                AND p.status = 'terverifikasi'
         WHERE u.role = 'petugas'
             $filterPetugasJoin
         GROUP BY u.id, u.nama
         ORDER BY u.nama"
);

$riwayatPembayaran = $conn->query(
    "SELECT p.*, tg.bulan, tg.tahun, w.nomor_pelanggan, w.nama, u.nama AS petugas
     FROM pembayaran p
     JOIN tagihan tg ON tg.id = p.tagihan_id
     JOIN warga w ON w.id = tg.warga_id
     JOIN users u ON u.id = p.petugas_id
     WHERE MONTH(p.tanggal_bayar) = $bulan
       AND YEAR(p.tanggal_bayar) = $tahun
       AND p.status = 'terverifikasi'
     $filterPetugas
     ORDER BY p.id DESC"
);

include 'partials/header.php';
?>

<div class="page-title">
    <div>
        <h2>Laporan Bulanan</h2>
        <p class="muted"><?= periode_label($bulan, $tahun) ?></p>
    </div>
    <a class="btn" href="export_laporan_bulanan.php?bulan=<?= $bulan ?>&amp;tahun=<?= $tahun ?>&amp;petugas_id=<?= $petugasId ?>">⬇ Export Excel</a>
</div>

<div class="card">
    <form class="search">
        <select name="bulan">
            <?php
            $namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            for ($i = 1; $i <= 12; $i++):
            ?>
                <option value="<?= $i ?>" <?= $i == $bulan ? 'selected' : '' ?>>
                    <?= $namaBulan[$i] ?>
                </option>
            <?php endfor; ?>
        </select>

        <input type="number" name="tahun" value="<?= $tahun ?>">
        <select name="petugas_id">
            <option value="0">Semua Petugas</option>
            <?php while ($petugas = $daftarPetugas->fetch_assoc()): ?>
                <option value="<?= (int) $petugas['id'] ?>" <?= $petugasId === (int) $petugas['id'] ? 'selected' : '' ?>>
                    <?= e($petugas['nama']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button class="btn" type="submit">Tampilkan</button>
    </form>
</div>

<div class="cards">
    <div class="card stat">
        <span>Pemasukan</span>
        <strong><?= rupiah($pendapatan) ?></strong>
    </div>

    <div class="card stat">
        <span>Fee Petugas (10%)</span>
        <strong><?= rupiah($feePetugas) ?></strong>
    </div>

    <div class="card stat">
        <span>Saldo</span>
        <strong><?= rupiah($pendapatan - $feePetugas) ?></strong>
    </div>
</div>

<div class="card tablewrap">
    <h3>Rekap Setoran Petugas</h3>
    <table>
        <tr>
            <th>Petugas</th>
            <th>Jumlah Transaksi</th>
            <th>Total Setoran</th>
            <th>Fee 10%</th>
            <th>Pembayaran Terakhir</th>
        </tr>

        <?php while ($row = $rekapPetugas->fetch_assoc()): ?>
            <tr>
                <td><?= e($row['petugas']) ?></td>
                <td><?= (int) $row['jumlah_transaksi'] ?></td>
                <td><?= rupiah($row['total_setoran']) ?></td>
                <td><?= rupiah($row['fee_petugas']) ?></td>
                <td><?= e($row['pembayaran_terakhir'] ?: '-') ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="card tablewrap">
    <h3>Detail Pembayaran Terverifikasi</h3>
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
                <td><?= e($row['nama']) ?></td>
                <td><?= e($row['petugas']) ?></td>
                <td><?= rupiah($row['nominal']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php include 'partials/footer.php'; ?>