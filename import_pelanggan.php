<?php
require 'config.php';
require_login();

if (!in_array($_SESSION['user']['role'], ['admin_keuangan', 'petugas'], true)) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$imported = 0;

function excel_text($value)
{
    return trim((string) $value);
}

function read_xls_rows($path)
{
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($path);
    if ($xml === false) {
        throw new RuntimeException('File .xls harus berupa file Excel hasil Export Excel dari aplikasi.');
    }

    $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
    $rows = [];
    foreach ($xml->xpath('//ss:Worksheet/ss:Table/ss:Row') as $row) {
        $values = [];
        foreach ($row->xpath('./ss:Cell/ss:Data') as $cell) {
            $values[] = excel_text($cell);
        }
        if ($values) {
            $rows[] = $values;
        }
    }

    return $rows;
}

function read_xlsx_rows($path)
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Dukungan ZIP PHP belum aktif. Aktifkan extension=zip di php.ini untuk import .xlsx.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('File .xlsx tidak dapat dibaca atau rusak.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = simplexml_load_string($sharedXml);
        foreach ($shared->si as $item) {
            $sharedStrings[] = excel_text(implode('', (array) $item->t));
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) {
        throw new RuntimeException('Struktur worksheet pada file .xlsx tidak ditemukan.');
    }

    $sheet = simplexml_load_string($sheetXml);
    $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $rows = [];
    foreach ($sheet->xpath('//x:sheetData/x:row') as $row) {
        $values = [];
        foreach ($row->c as $cell) {
            $value = (string) $cell->v;
            if ((string) $cell['t'] === 's') {
                $value = $sharedStrings[(int) $value] ?? '';
            } elseif ((string) $cell['t'] === 'inlineStr') {
                $value = (string) $cell->is->t;
            }
            $values[] = excel_text($value);
        }
        if ($values) {
            $rows[] = $values;
        }
    }

    return $rows;
}

function read_excel_rows($path, $extension)
{
    return $extension === 'xlsx' ? read_xlsx_rows($path) : read_xls_rows($path);
}

if (isset($_POST['importir'])) {
    $file = $_FILES['file_excel'] ?? null;
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File Excel belum dipilih atau gagal diunggah.';
    } elseif (!in_array($extension, ['xls', 'xlsx'], true)) {
        $errors[] = 'Format file harus .xls atau .xlsx.';
    } else {
        try {
            $rows = read_excel_rows($file['tmp_name'], $extension);
        } catch (RuntimeException $exception) {
            $rows = [];
            $errors[] = $exception->getMessage();
        }

        if (!$errors && count($rows) < 2) {
            $errors[] = 'File tidak memiliki data pelanggan yang dapat diimpor. Gunakan file hasil Export Excel dan jangan hapus baris judul.';
        }

        if (!$errors) {
            $headers = array_map('strtolower', array_map('excel_text', array_shift($rows)));
            $hasAmountColumn = in_array('jumlah iuran', $headers, true);
            $tarifByName = [];
            $tarifResult = $conn->query('SELECT id, nama FROM tarif WHERE aktif = 1');
            while ($tarif = $tarifResult->fetch_assoc()) {
                $tarifByName[strtolower(trim($tarif['nama']))] = (int) $tarif['id'];
            }

            $conn->begin_transaction();
            try {
                foreach ($rows as $rowNumber => $row) {
                    $row = array_pad($row, $hasAmountColumn ? 8 : 7, '');
                    [$nomor, $nama, $alamat, $hp, $wilayah, $tarif] = array_map('excel_text', array_slice($row, 0, 6));
                    $status = excel_text($row[$hasAmountColumn ? 7 : 6]);
                    $tarifId = ctype_digit($tarif) ? (int) $tarif : ($tarifByName[strtolower($tarif)] ?? 0);
                    $status = in_array($status, ['aktif', 'nonaktif'], true) ? $status : 'aktif';

                    if ($nomor === '' || $nama === '' || $alamat === '' || $tarifId < 1) {
                        throw new RuntimeException('Baris ' . ($rowNumber + 2) . ' harus memiliki nomor, nama, alamat, dan tarif yang valid.');
                    }

                    $nomor = format_nomor_pelanggan($nomor);

                    $check = $conn->prepare('SELECT id FROM warga WHERE nomor_pelanggan = ?');
                    $check->bind_param('s', $nomor);
                    $check->execute();
                    $existing = $check->get_result()->fetch_assoc();

                    if ($existing) {
                        $statement = $conn->prepare(
                            'UPDATE warga SET nama=?, alamat=?, no_hp=?, wilayah=?, tarif_id=?, status=? WHERE id=?'
                        );
                        $statement->bind_param('ssssisi', $nama, $alamat, $hp, $wilayah, $tarifId, $status, $existing['id']);
                        $statement->execute();
                        log_action('IMPORT_WARGA', 'warga', $existing['id'], $nomor);
                    } else {
                        $petugasId = (int) $_SESSION['user']['id'];
                        $statement = $conn->prepare(
                            'INSERT INTO warga(nomor_pelanggan, nama, alamat, no_hp, wilayah, tarif_id, petugas_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $statement->bind_param('sssssiis', $nomor, $nama, $alamat, $hp, $wilayah, $tarifId, $petugasId, $status);
                        $statement->execute();
                        log_action('IMPORT_WARGA', 'warga', $statement->insert_id, $nomor);
                    }
                    $imported++;
                }
                $conn->commit();
            } catch (Throwable $exception) {
                $conn->rollback();
                $imported = 0;
                $errors[] = $exception->getMessage();
            }
        }
    }
}

$page_title = 'Import Pelanggan';
include 'partials/header.php';
?>
<div class="page-title">
    <div>
        <h2>Import Data Pelanggan</h2>
        <p class="muted">Gunakan file Excel hasil export atau template dengan tujuh kolom pelanggan.</p>
    </div>
</div>

<?php if ($imported): ?><div class="card">Berhasil mengimpor <?= $imported ?> data pelanggan.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="card" style="color:#a12626"><?= e($error) ?></div><?php endforeach; ?>

<div class="card">
    <form method="post" enctype="multipart/form-data" class="formgrid">
        <input type="file" name="file_excel" accept=".xls,.xlsx" required>
        <button type="submit" name="importir" class="btn primary">Import Excel</button>
    </form>
    <p class="muted">Urutan kolom: No Pelanggan, Nama, Alamat, No HP, Wilayah, Tarif, Status.</p>
</div>
<?php include 'partials/footer.php'; ?>