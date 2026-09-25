<?php
require 'config.php';
require_login();

$page_title = 'Tagihan';
$admin = $_SESSION['user']['role'] === 'admin_keuangan';
$petugas = $_SESSION['user']['role'] === 'petugas';
$bl = (int) ($_REQUEST['bulan'] ?? date('n'));
$th = (int) ($_REQUEST['tahun'] ?? date('Y'));
$msg = '';

if ($admin && isset($_POST['generate'])) {
    $nominal = round((float) ($_POST['nominal'] ?? 0));
    if ($nominal > 0) {
        $warga = $conn->query("SELECT id FROM warga WHERE status='aktif'");
        $jumlah = 0;
        $due = "$th-" . str_pad($bl, 2, '0', STR_PAD_LEFT) . '-' . date('t', strtotime("$th-$bl-01"));
        while ($row = $warga->fetch_assoc()) {
            $statement = $conn->prepare('INSERT IGNORE INTO tagihan(warga_id, bulan, tahun, nominal, jatuh_tempo) VALUES (?, ?, ?, ?, ?)');
            $statement->bind_param('iiids', $row['id'], $bl, $th, $nominal, $due);
            $statement->execute();
            $jumlah += $statement->affected_rows;
        }
        log_action('GENERATE_TAGIHAN', 'tagihan', null, "$bl/$th - " . rupiah($nominal));
        $msg = "$jumlah tagihan dibuat.";
    } else {
        $msg = 'Nominal harus lebih besar dari 0.';
    }
}

if ($petugas && isset($_POST['ubah_tagihan'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $nominal = round((float) ($_POST['nominal'] ?? 0));
    $status = $_POST['ubah_tagihan'] ?? ($_POST['status'] ?? 'belum_lunas');

    if (!in_array($status, ['belum_lunas', 'lunas'], true)) {
        $status = 'belum_lunas';
    }

    if ($id > 0 && $nominal > 0) {
        $statement = $conn->prepare('UPDATE tagihan SET nominal=?, status=? WHERE id=?');
        $statement->bind_param('dsi', $nominal, $status, $id);
        $statement->execute();

        if ($status === 'lunas') {
            $cek = $conn->prepare('SELECT id FROM pembayaran WHERE tagihan_id=? LIMIT 1');
            $cek->bind_param('i', $id);
            $cek->execute();
            $cekResult = $cek->get_result();

            if ($cekResult->num_rows === 0) {
                $tanggal = date('Y-m-d');
                $nomorKwitansi = 'KW-' . date('YmdHis') . '-' . random_int(100, 999);
                $metode = 'tunai';
                $keterangan = 'Pembayaran otomatis dari status tagihan';
                $petugasId = (int) ($_SESSION['user']['id'] ?? 0);
                $statusPembayaran = 'terverifikasi';

                $insertPembayaran = $conn->prepare(
                    'INSERT INTO pembayaran(tagihan_id, petugas_id, tanggal_bayar, nominal, metode, status, nomor_kwitansi, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insertPembayaran->bind_param(
                    'iisdssss',
                    $id,
                    $petugasId,
                    $tanggal,
                    $nominal,
                    $metode,
                    $statusPembayaran,
                    $nomorKwitansi,
                    $keterangan
                );
                $insertPembayaran->execute();
            }
        }

        log_action('EDIT_TAGIHAN', 'tagihan', $id, $status . ' - ' . rupiah($nominal));
        $msg = 'Tagihan berhasil diperbarui.';
    }
}

if ($petugas && isset($_POST['buat_tagihan'])) {
    $wargaId = (int) ($_POST['warga_id'] ?? 0);
    $nominal = round((float) ($_POST['nominal'] ?? 0));
    $due = "$th-" . str_pad($bl, 2, '0', STR_PAD_LEFT) . '-' . date('t', strtotime("$th-$bl-01"));
    if ($wargaId > 0 && $nominal > 0) {
        $statement = $conn->prepare('INSERT IGNORE INTO tagihan(warga_id, bulan, tahun, nominal, jatuh_tempo) VALUES (?, ?, ?, ?, ?)');
        $statement->bind_param('iiids', $wargaId, $bl, $th, $nominal, $due);
        $statement->execute();
        log_action('BUAT_TAGIHAN', 'tagihan', $statement->insert_id, "$bl/$th - " . rupiah($nominal));
        $msg = 'Tagihan warga berhasil dibuat.';
    }
}

$res = $conn->query(
    "SELECT tg.*, w.id AS warga_id, w.nomor_pelanggan, w.nama, w.no_hp,
            p.tanggal_bayar, u.nama AS petugas_nama
     FROM warga w
     LEFT JOIN tagihan tg ON tg.warga_id=w.id AND tg.bulan=$bl AND tg.tahun=$th
     LEFT JOIN pembayaran p ON p.tagihan_id=tg.id AND p.status='terverifikasi'
     LEFT JOIN users u ON u.id=p.petugas_id
     WHERE w.status='aktif'
     ORDER BY w.nomor_pelanggan ASC"
);

include 'partials/header.php';
?>
<div class="page-title"><div><h2>Tagihan Bulanan</h2><p class="muted"><?= periode_label($bl, $th) ?></p></div></div>
<?php if ($msg): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<div class="card">
    <form class="search">
        <select name="bulan">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>" <?= $i === $bl ? 'selected' : '' ?>><?= periode_label($i, $th) ?></option>
            <?php endfor; ?>
        </select>
        <input name="tahun" type="number" value="<?= $th ?>">
        <button class="btn">Tampilkan</button>
        <?php if ($admin): ?>
            <label class="currency-input"><span>Rp.</span><input name="nominal" type="number" min="1" step="1" placeholder="Nominal iuran"></label>
            <button name="generate" value="1" formmethod="post" class="btn primary">Generate Tagihan</button>
        <?php endif; ?>
    </form>
</div>
<div class="card tablewrap"><table>
    <tr><th>No. Pelanggan</th><th>Nama</th><th>Periode</th><th>Jatuh Tempo</th><th>Nominal</th><th>Status Pembayaran</th><th>Tanggal Bayar</th><th>Petugas</th><?php if ($petugas): ?><th>Aksi</th><?php endif; ?></tr>
    <?php while ($row = $res->fetch_assoc()): ?>
        <?php $status = $row['status'] ?? 'belum_lunas'; $sudahBayar = $status === 'lunas'; ?>
        <tr>
            <td><?= e(format_nomor_pelanggan($row['nomor_pelanggan'])) ?></td>
            <td><?= e($row['nama']) ?></td>
            <td><?= periode_label($bl, $th) ?></td>
            <td><?= e($row['jatuh_tempo'] ?? '-') ?></td>
            <td><?= $row['id'] ? rupiah($row['nominal']) : '-' ?></td>
            <td><span class="payment-status <?= $sudahBayar ? 'success' : 'pending' ?>" title="<?= $sudahBayar ? 'Sudah melakukan pembayaran' : 'Belum melakukan pembayaran' ?>" aria-label="<?= $sudahBayar ? 'Sudah melakukan pembayaran' : 'Belum melakukan pembayaran' ?>"><?= $sudahBayar ? '✓' : '✕' ?></span></td>
            <td><?= e($row['tanggal_bayar'] ?? '-') ?></td>
            <td><?= e($row['petugas_nama'] ?? '-') ?></td>
            <?php if ($petugas): ?><td>
                <?php if ($row['id']): ?>
                    <a class="btn small primary" href="pembayaran.php?tagihan=<?= $row['id'] ?>">Bayar</a>
                    <form method="post" class="formgrid status-form">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <label class="currency-input"><span>Rp.</span><input name="nominal" type="number" min="1" step="1" value="<?= round((float) $row['nominal']) ?>" required></label>
                        <div class="status-choice-group">
                            <button type="submit" name="ubah_tagihan" value="lunas" class="status-choice <?= $status === 'lunas' ? 'success active' : 'success' ?>" title="Sudah Bayar" aria-label="Sudah Bayar">
                                ✓
                            </button>
                            <button type="submit" name="ubah_tagihan" value="belum_lunas" class="status-choice <?= $status === 'belum_lunas' ? 'danger active' : 'danger' ?>" title="Belum Bayar" aria-label="Belum Bayar">
                                ✕
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <form method="post" class="formgrid">
                        <input type="hidden" name="warga_id" value="<?= $row['warga_id'] ?>">
                        <label class="currency-input"><span>Rp.</span><input name="nominal" type="number" min="1" step="1" placeholder="Nominal tagihan" required></label>
                        <button type="submit" name="buat_tagihan" class="btn small primary">Buat Tagihan</button>
                    </form>
                <?php endif; ?>
            </td><?php endif; ?>
        </tr>
    <?php endwhile; ?>
</table></div>
<?php include 'partials/footer.php'; ?>
