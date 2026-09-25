<?php
require 'config.php';
require_login();
$page_title = 'Data Warga';
$admin = $_SESSION['user']['role'] === 'admin_keuangan';
$petugas = $_SESSION['user']['role'] === 'petugas';
$edit = null;
$edit_id = (int)($_GET['edit'] ?? 0);
$bl = (int)($_GET['bulan'] ?? date('n'));
$th = (int)($_GET['tahun'] ?? date('Y'));

$petugasCheck = $conn->query("SHOW COLUMNS FROM warga LIKE 'petugas_id'");
if ($petugasCheck->num_rows === 0) {
    $conn->query('ALTER TABLE warga ADD COLUMN petugas_id INT NULL AFTER tarif_id');
}

if ($edit_id) {
    $edit = $conn->query("SELECT * FROM warga WHERE id=$edit_id")->fetch_assoc();
}

if (isset($_POST['simpan'])) {
    $id = (int)($_POST['id'] ?? 0);
    $nomor = format_nomor_pelanggan($_POST['nomor'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $hp = trim($_POST['hp'] ?? '');
    $wilayah = trim($_POST['wilayah'] ?? '');
    $tarif_id = (int)($_POST['tarif_id'] ?? 0);
    $petugas_id = (int)($_SESSION['user']['id'] ?? 0);

    if ($id) {
        $s = $conn->prepare('UPDATE warga SET nomor_pelanggan=?,nama=?,alamat=?,no_hp=?,wilayah=?,tarif_id=? WHERE id=?');
        $s->bind_param('sssssii', $nomor, $nama, $alamat, $hp, $wilayah, $tarif_id, $id);
        $s->execute();
        log_action('EDIT_WARGA', 'warga', $id, $nama);
    } else {
        $s = $conn->prepare('INSERT INTO warga(nomor_pelanggan,nama,alamat,no_hp,wilayah,tarif_id,petugas_id) VALUES(?,?,?,?,?,?,?)');
        $s->bind_param('sssssii', $nomor, $nama, $alamat, $hp, $wilayah, $tarif_id, $petugas_id);
        $s->execute();
        log_action('TAMBAH_WARGA', 'warga', $s->insert_id, $nama);
    }
    header('Location: warga.php');
    exit;
}

if ($admin && isset($_POST['reset_pembayaran'])) {
    $resetBulan = (int) ($_POST['bulan'] ?? date('n'));
    $resetTahun = (int) ($_POST['tahun'] ?? date('Y'));
    $statement = $conn->prepare(
        "UPDATE tagihan SET status='belum_lunas' WHERE bulan=? AND tahun=? AND status='lunas'"
    );
    $statement->bind_param('ii', $resetBulan, $resetTahun);
    $statement->execute();

    log_action(
        'RESET_PEMBAYARAN',
        'tagihan',
        null,
        periode_label($resetBulan, $resetTahun) . ' - ' . $statement->affected_rows . ' tagihan'
    );
    header('Location: warga.php?bulan=' . $resetBulan . '&tahun=' . $resetTahun);
    exit;
}

if ($admin && isset($_POST['hapus_semua_warga'])) {
    $jumlahWarga = (int) $conn->query('SELECT COUNT(*) AS jumlah FROM warga')->fetch_assoc()['jumlah'];

    try {
        $conn->begin_transaction();

        if (!$conn->query('DELETE p FROM pembayaran p INNER JOIN tagihan tg ON tg.id=p.tagihan_id')) {
            throw new RuntimeException('Gagal menghapus riwayat pembayaran.');
        }
        if (!$conn->query('DELETE FROM tagihan')) {
            throw new RuntimeException('Gagal menghapus tagihan.');
        }
        if (!$conn->query('DELETE FROM warga')) {
            throw new RuntimeException('Gagal menghapus data warga.');
        }

        $conn->commit();
        log_action('HAPUS_SEMUA_WARGA', 'warga', null, $jumlahWarga . ' data warga');
        header('Location: warga.php?dihapus=' . $jumlahWarga);
        exit;
    } catch (Throwable $exception) {
        $conn->rollback();
        header('Location: warga.php?gagal_hapus=1');
        exit;
    }
}

if ($admin && isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $jumlah = $conn->query("SELECT COUNT(*) c FROM tagihan WHERE warga_id=$id")->fetch_assoc()['c'];
    if (!$jumlah) {
        $conn->query("DELETE FROM warga WHERE id=$id");
        log_action('HAPUS_WARGA', 'warga', $id);
    }
    header('Location: warga.php');
    exit;
}

$tarif = $conn->query('SELECT * FROM tarif ORDER BY nama');
$petugasFilter = (int)($_GET['petugas'] ?? 0);
$petugasList = $conn->query('SELECT id, nama FROM users WHERE role = "petugas" ORDER BY nama');
$q = $conn->real_escape_string(trim($_GET['q'] ?? ''));

$where = "WHERE (w.nama LIKE '%$q%' OR w.nomor_pelanggan LIKE '%$q%' OR w.alamat LIKE '%$q%')";
if ($admin && $petugasFilter > 0) {
    $where .= " AND w.petugas_id = $petugasFilter";
} elseif ($petugas) {
    $where .= ' AND w.petugas_id = ' . (int) $_SESSION['user']['id'];
}

$res = $conn->query(
    "SELECT w.*, t.nominal tarif_nominal, tg.status tagihan_status, u.nama petugas_input
     FROM warga w
     JOIN tarif t ON t.id=w.tarif_id
     LEFT JOIN tagihan tg ON tg.warga_id=w.id AND tg.bulan=$bl AND tg.tahun=$th
     LEFT JOIN users u ON u.id=w.petugas_id
     $where
    ORDER BY w.nomor_pelanggan ASC"
);
include 'partials/header.php';
?>
<div class="page-title"><div><h2>Data Warga</h2><p class="muted">Status pembayaran <?= periode_label($bl, $th) ?>.</p></div><?php if ($admin || $petugas): ?><div><a class="btn" href="export_pelanggan.php">Export Excel</a> <a class="btn primary" href="import_pelanggan.php">Import Excel</a><?php if ($admin): ?><form method="post" style="display:inline" onsubmit="return confirm('Reset status pembayaran semua pelanggan pada periode ini? Riwayat pembayaran tetap tersimpan.')"><input type="hidden" name="bulan" value="<?= $bl ?>"><input type="hidden" name="tahun" value="<?= $th ?>"><button type="submit" name="reset_pembayaran" class="btn small">Reset Status Pembayaran</button></form><?php endif; ?></div><?php endif; ?></div>
<?php if (isset($_GET['dihapus'])): ?><div class="alert success"><?= (int) $_GET['dihapus'] ?> data warga berhasil dihapus.</div><?php endif; ?>
<?php if (isset($_GET['gagal_hapus'])): ?><div class="alert danger">Penghapusan data warga gagal. Tidak ada data yang diubah.</div><?php endif; ?>
<?php if ($admin): ?><div class="card"><form method="post" onsubmit="return confirm('PERINGATAN: hapus SEMUA data warga, tagihan, dan riwayat pembayaran? Tindakan ini tidak dapat dibatalkan.')"><button type="submit" name="hapus_semua_warga" class="btn small">Hapus Semua Data Warga</button></form></div><?php endif; ?>
<?php if ($admin || $petugas): ?><div class="card"><h3><?= $edit ? 'Edit Warga' : 'Tambah Warga' ?></h3><form method="post" class="formgrid"><?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?><input name="nomor" value="<?= e(format_nomor_pelanggan($edit['nomor_pelanggan'] ?? '')) ?>" placeholder="Nomor pelanggan" required><input name="nama" value="<?= e($edit['nama'] ?? '') ?>" placeholder="Nama lengkap" required><input name="hp" value="<?= e($edit['no_hp'] ?? '') ?>" placeholder="No. HP"><input name="wilayah" value="<?= e($edit['wilayah'] ?? '') ?>" placeholder="Wilayah"><textarea name="alamat" placeholder="Alamat" required><?= e($edit['alamat'] ?? '') ?></textarea><select name="tarif_id" required><?php while ($t = $tarif->fetch_assoc()): ?><option value="<?= $t['id'] ?>" <?= isset($edit['tarif_id']) && (int)$edit['tarif_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['nama']) ?> - <?= rupiah($t['nominal']) ?></option><?php endwhile; ?></select><button name="simpan" class="btn primary"><?= $edit ? 'Perbarui Warga' : 'Simpan Warga' ?></button><?php if ($edit): ?><a class="btn" href="warga.php">Batal</a><?php endif; ?></form></div><?php endif; ?>
<div class="card"><form class="search"><select name="bulan"><?php for ($i=1; $i<=12; $i++): ?><option value="<?= $i ?>" <?= $i === $bl ? 'selected' : '' ?>><?= periode_label($i, $th) ?></option><?php endfor; ?></select><input name="tahun" type="number" value="<?= $th ?>"><input name="q" value="<?= e($q) ?>" placeholder="Cari nomor / nama / alamat"><?php if ($admin): ?><select name="petugas"><option value="0">Semua petugas</option><?php while ($p = $petugasList->fetch_assoc()): ?><option value="<?= $p['id'] ?>" <?= (int)$petugasFilter === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['nama']) ?></option><?php endwhile; ?></select><?php endif; ?><button class="btn">Tampilkan</button></form><div class="tablewrap"><table><tr><th>No</th><th>No. Pelanggan</th><th>Nama</th><th>Alamat</th><th>Tarif</th><th>Petugas Input</th><th>Pembayaran</th><th>Aksi</th></tr><?php $n=1; while ($r=$res->fetch_assoc()): $sudah = $r['tagihan_status'] === 'lunas'; ?><tr><td><?= $n++ ?></td><td><?= e(format_nomor_pelanggan($r['nomor_pelanggan'])) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['alamat']) ?></td><td><?= rupiah($r['tarif_nominal']) ?></td><td><?= e($r['petugas_input'] ?? 'Tidak diketahui') ?></td><td><span class="badge <?= $sudah ? 'success' : 'warn' ?>"><?= $sudah ? 'Sudah Bayar' : 'Belum Bayar' ?></span></td><td><?php if ($admin): ?><a class="btn small" href="?edit=<?= $r['id'] ?>">Edit</a> <a class="btn small" href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus data warga ini?')">Hapus</a><?php else: ?><div class="status-toggle"><a class="btn-status <?= $sudah ? 'success active' : 'success' ?>" href="tagihan.php?bulan=<?= $bl ?>&tahun=<?= $th ?>" title="Sudah Bayar" aria-label="Sudah Bayar">✓</a><a class="btn-status <?= $sudah ? 'danger' : 'danger active' ?>" href="tagihan.php?bulan=<?= $bl ?>&tahun=<?= $th ?>" title="Belum Bayar" aria-label="Belum Bayar">✕</a></div><?php endif; ?></td></tr><?php endwhile; ?></table></div></div>
<?php include 'partials/footer.php'; ?>
