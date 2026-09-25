<?php
$host = '127.0.0.1';
$port = 3306;
$db = 'iuran_sampah';
$user = 'root';
$pass = '';

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
} catch (mysqli_sql_exception $exception) {
    http_response_code(503);
    exit('Koneksi database gagal. Pastikan MySQL XAMPP aktif, portnya 3306, dan database iuran_sampah sudah diimpor.');
}

$conn->set_charset('utf8mb4');
date_default_timezone_set('Asia/Makassar');
session_start();

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function format_nomor_pelanggan($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('/\s+/', '', $value);
    if (preg_match('/^(.*?)(\d+)$/', $value, $matches)) {
        $prefix = strtoupper(trim($matches[1]));
        $number = (int) $matches[2];
        if ($prefix === '') {
            return str_pad((string) $number, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    return strtoupper($value);
}
function rupiah($value)
{
    $nilai = (float) $value;
    return 'Rp ' . number_format(round($nilai), 0, ',', '.');
}

function require_login()
{
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function require_role($role)
{
    require_login();

    if ($_SESSION['user']['role'] !== $role) {
        header('Location: dashboard.php');
        exit;
    }
}

function log_action($aksi, $table = null, $id = null, $ket = null)
{
    global $conn;

    $uid = $_SESSION['user']['id'] ?? null;
    $statement = $conn->prepare(
        'INSERT INTO audit_log(user_id,aksi,tabel_data,data_id,keterangan) VALUES(?,?,?,?,?)'
    );
    $statement->bind_param('issis', $uid, $aksi, $table, $id, $ket);
    $statement->execute();
}

function periode_label($bulan, $tahun)
{
    $namaBulan = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    return $namaBulan[(int) $bulan] . ' ' . $tahun;
}
