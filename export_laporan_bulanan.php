<?php
require 'config.php';
require_role('admin_keuangan');

$bulan = max(1, min(12, (int) ($_GET['bulan'] ?? date('n'))));
$tahun = max(2020, min(2100, (int) ($_GET['tahun'] ?? date('Y'))));
$petugasId = max(0, (int) ($_GET['petugas_id'] ?? 0));
$filterPetugas = $petugasId > 0 ? ' AND p.petugas_id = ' . $petugasId : '';
$filterPetugasJoin = $petugasId > 0 ? ' AND u.id = ' . $petugasId : '';

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

$detailPembayaran = $conn->query(
    "SELECT p.nomor_kwitansi, p.tanggal_bayar, w.nomor_pelanggan,
            w.nama AS warga, tg.bulan, tg.tahun, u.nama AS petugas,
            p.nominal, p.metode
     FROM pembayaran p
     JOIN tagihan tg ON tg.id = p.tagihan_id
     JOIN warga w ON w.id = tg.warga_id
     JOIN users u ON u.id = p.petugas_id
     WHERE MONTH(p.tanggal_bayar) = $bulan
       AND YEAR(p.tanggal_bayar) = $tahun
       AND p.status = 'terverifikasi'
         $filterPetugas
     ORDER BY p.tanggal_bayar, p.id"
);

$filename = 'laporan-bulanan-' . $tahun . '-' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT)
    . ($petugasId > 0 ? '-petugas-' . $petugasId : '') . '.xls';
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
    <Worksheet ss:Name="Rekap Petugas">
        <Table>
            <Row>
                <?php foreach (['Periode', 'Petugas', 'Jumlah Transaksi', 'Total Setoran', 'Fee 10%', 'Pembayaran Terakhir'] as $heading): ?>
                    <Cell><Data ss:Type="String"><?= e($heading) ?></Data></Cell>
                <?php endforeach; ?>
            </Row>
            <?php while ($row = $rekapPetugas->fetch_assoc()): ?>
                <Row>
                    <Cell><Data ss:Type="String"><?= e(periode_label($bulan, $tahun)) ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?= e($row['petugas']) ?></Data></Cell>
                    <Cell><Data ss:Type="Number"><?= (int) $row['jumlah_transaksi'] ?></Data></Cell>
                    <Cell><Data ss:Type="Number"><?= (float) $row['total_setoran'] ?></Data></Cell>
                    <Cell><Data ss:Type="Number"><?= (float) $row['fee_petugas'] ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?= e($row['pembayaran_terakhir'] ?: '-') ?></Data></Cell>
                </Row>
            <?php endwhile; ?>
        </Table>
    </Worksheet>
    <Worksheet ss:Name="Detail Pembayaran">
        <Table>
            <Row>
                <?php foreach (['No Kwitansi', 'Tanggal Bayar', 'No Pelanggan', 'Warga', 'Periode Tagihan', 'Petugas', 'Nominal', 'Metode'] as $heading): ?>
                    <Cell><Data ss:Type="String"><?= e($heading) ?></Data></Cell>
                <?php endforeach; ?>
            </Row>
            <?php while ($row = $detailPembayaran->fetch_assoc()): ?>
                <Row>
                    <Cell><Data ss:Type="String"><?= e($row['nomor_kwitansi']) ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?= e($row['tanggal_bayar']) ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?= e(format_nomor_pelanggan($row['nomor_pelanggan'])) ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?= e($row['warga']) ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?= e(periode_label($row['bulan'], $row['tahun'])) ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?= e($row['petugas']) ?></Data></Cell>
                    <Cell><Data ss:Type="Number"><?= (float) $row['nominal'] ?></Data></Cell>
                    <Cell><Data ss:Type="String"><?= e($row['metode']) ?></Data></Cell>
                </Row>
            <?php endwhile; ?>
        </Table>
    </Worksheet>
</Workbook>