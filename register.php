<?php
require 'config.php';
if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$err = '';
$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    $role = $_POST['role'] ?? 'petugas';
    $rolesValid = ['admin_keuangan', 'petugas'];

    if ($nama === '' || $username === '' || $password === '') {
        $err = 'Semua data wajib diisi.';
    } elseif (!in_array($role, $rolesValid, true)) {
        $err = 'Status pengguna tidak valid.';
    } elseif (strlen($password) < 6) {
        $err = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $err = 'Konfirmasi password tidak cocok.';
    } else {
        $cek = $conn->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
        $cek->bind_param('s', $username);
        $cek->execute();
        if ($cek->get_result()->num_rows) {
            $err = 'Username sudah digunakan.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $s = $conn->prepare(
                'INSERT INTO users(nama, username, password, role, status) VALUES (?, ?, ?, ?, ?)'
            );
            $status = 'aktif';
            $s->bind_param('sssss', $nama, $username, $hash, $role, $status);
            $s->execute();
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Daftar Pengguna | Iuran Sampah</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login">
    <div class="loginbox">
        <div class="brand">♻️</div>
        <h1>Daftar Pengguna</h1>
        <p>Buat akun admin keuangan atau petugas iuran.</p>
        <?php if ($err): ?><div class="alert danger"><?= e($err) ?></div><?php endif; ?>
        <?php if ($ok): ?><div class="alert success"><?= e($ok) ?></div><?php endif; ?>

        <form method="post">
            <label>Nama lengkap</label>
            <input name="nama" value="<?= e($_POST['nama'] ?? '') ?>" required autofocus>

            <label>Username</label>
            <input name="username" value="<?= e($_POST['username'] ?? '') ?>" required>

            <label>Status pengguna</label>
            <select name="role" required>
                <option value="petugas" <?= ($_POST['role'] ?? 'petugas') === 'petugas' ? 'selected' : '' ?>>Petugas Iuran</option>
                <option value="admin_keuangan" <?= ($_POST['role'] ?? '') === 'admin_keuangan' ? 'selected' : '' ?>>Admin Keuangan</option>
            </select>

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Konfirmasi password</label>
            <input type="password" name="konfirmasi" required>

            <button class="btn primary full">Daftar</button>
        </form>
        <p class="hint"><a href="login.php">Sudah punya akun? Masuk</a></p>
    </div>
</body>
</html>