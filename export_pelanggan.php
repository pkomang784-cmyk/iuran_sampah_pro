<?php
require 'config.php';
require_login();

if (!in_array($_SESSION['user']['role'], ['admin_keuangan', 'petugas'], true)) {
    header('Location: dashboard.php');
    exit;
}

$res = $conn->query(
        'SELECT w.nomor_pelanggan, w.nama, w.alamat, w.no_hp, w.wilayah,
            t.nama AS tarif, t.nominal AS jumlah_iuran, w.status
     FROM warga w
     JOIN tarif t ON t.id = w.tarif_id
     ORDER BY w.id'
);

$filename = 'data-pelanggan-' . date('Y-m-d') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

echo '<?xml version="1.0"?>';
?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <Worksheet ss:Name="Pelanggan">
        <Table>
            <Row>
                <?php foreach (['No Pelanggan', 'Nama', 'Alamat', 'No HP', 'Wilayah', 'Tarif', 'Jumlah Iuran', 'Status'] as $heading): ?>
                    <Cell><Data ss:Type="String"><?= e($heading) ?></Data></Cell>
                <?php endforeach; ?>
            </Row>
            <?php while ($row = $res->fetch_assoc()): ?>
                <Row>
                    <?php foreach ($row as $column => $value): ?>
                        <?php if ($column === 'jumlah_iuran'): ?>
                            <?php $value = 'Rp.' . number_format(round((float) $value), 0, ',', '.'); ?>
                        <?php endif; ?>
                        <Cell><Data ss:Type="String"><?= e($value) ?></Data></Cell>
                    <?php endforeach; ?>
                </Row>
            <?php endwhile; ?>
        </Table>
    </Worksheet>
</Workbook>