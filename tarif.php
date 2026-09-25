<?php
require 'config.php';
require_role('admin_keuangan');

$page_title = 'Tarif';
$kategoriTarif = [
    'Rumah Tangga',
    'Usaha Kecil',
    'Usaha Menengah',
    'Usaha Besar',
];

$defaultTarif = [
    'Rumah Tangga' => 20000,
    'Usaha Kecil' => 50000,
    'Usaha Menengah' => 100000,
    'Usaha Besar' => 200000,
];

foreach ($defaultTarif as $kategori => $nilaiDefault) {
    $cek = $conn->query(
        "SELECT id FROM tarif WHERE nama = '" . $conn->real_escape_string($kategori) . "' LIMIT 1"
    );

    if ($cek->num_rows === 0) {
        $stmt = $conn->prepare('INSERT INTO tarif (nama, nominal, keterangan) VALUES (?, ?, ?)');
        $keterangan = 'Tarif default';
        $stmt->bind_param('sds', $kategori, $nilaiDefault, $keterangan);
        $stmt->execute();
    }
}

$edit = null;
$edit_id = (int) ($_GET['edit'] ?? 0);
if ($edit_id) {
    $edit = $conn->query("SELECT * FROM tarif WHERE id = $edit_id")->fetch_assoc();
}

if (isset($_POST['simpan'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $nominal = (float) ($_POST['nominal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($nama !== '' && $nominal > 0) {
        if ($id) {
            $stmt = $conn->prepare('UPDATE tarif SET nama = ?, nominal = ?, keterangan = ? WHERE id = ?');
            $stmt->bind_param('sdsi', $nama, $nominal, $keterangan, $id);
            $stmt->execute();
            log_action('EDIT_TARIF', 'tarif', $id, $nama);
        } else {
            $stmt = $conn->prepare('INSERT INTO tarif (nama, nominal, keterangan) VALUES (?, ?, ?)');
            $stmt->bind_param('sds', $nama, $nominal, $keterangan);
            $stmt->execute();
            log_action('TAMBAH_TARIF', 'tarif', $stmt->insert_id, $nama);
        }
    }

    header('Location: tarif.php');
    exit;
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $dipakai = $conn->query("SELECT COUNT(*) AS c FROM warga WHERE tarif_id = $id")->fetch_assoc()['c'];

    if (!$dipakai) {
        $conn->query("DELETE FROM tarif WHERE id = $id");
        log_action('HAPUS_TARIF', 'tarif', $id);
    }

    header('Location: tarif.php');
    exit;
}

$res = $conn->query(
    'SELECT t.*, (SELECT COUNT(*) FROM warga w WHERE w.tarif_id = t.id) AS jumlah_warga
     FROM tarif t
     ORDER BY FIELD(t.nama, "Rumah Tangga", "Usaha Kecil", "Usaha Menengah", "Usaha Besar"), t.id DESC'
);

include 'partials/header.php';
?>

<div class="page-title">
    <div>
        <h2>Pengaturan Tarif</h2>
        <p class="muted">Atur nominal sesuai kategori pelanggan.</p>
    </div>
</div>

<div class="card">
    <h3><?= $edit ? 'Edit Tarif' : 'Tambah Tarif' ?></h3>
    <form method="post" class="formgrid">
        <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <?php endif; ?>

        <select name="nama" required>
            <option value="">Pilih kategori</option>
            <?php foreach ($kategoriTarif as $kategori): ?>
                <option value="<?= e($kategori) ?>" <?= ($edit['nama'] ?? '') === $kategori ? 'selected' : '' ?>>
                    <?= e($kategori) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input
            type="number"
            name="nominal"
            value="<?= e($edit['nominal'] ?? '') ?>"
            min="1"
            step="1"
            inputmode="numeric"
            placeholder="Nominal"
            required
        >

        <input
            type="text"
            name="keterangan"
            value="<?= e($edit['keterangan'] ?? '') ?>"
            placeholder="Keterangan"
        >

        <button type="submit" name="simpan" class="btn primary">
            <?= $edit ? 'Perbarui Tarif' : 'Tambah Tarif' ?>
        </button>
    </form>
</div>

<div class="card tablewrap">
    <table>
        <tr>
            <th>Nama Tarif</th>
            <th>Nominal</th>
            <th>Keterangan</th>
            <th>Pelanggan</th>
            <th>Aksi</th>
        </tr>

        <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= e($row['nama']) ?></td>
                <td><?= rupiah($row['nominal']) ?></td>
                <td><?= e($row['keterangan']) ?></td>
                <td><?= $row['jumlah_warga'] ?></td>
                <td>
                    <a class="btn small" href="?edit=<?= $row['id'] ?>">Edit</a>
                    <a
                        class="btn small"
                        href="?hapus=<?= $row['id'] ?>"
                        onclick="return confirm('Hapus tarif ini?')"
                    >
                        Hapus
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php include 'partials/footer.php'; ?>