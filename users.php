<?php
require 'config.php';
require_role('admin_keuangan');

$page_title = 'Pengguna';

if (isset($_POST['simpan'])) {
	$nama = trim($_POST['nama'] ?? '');
	$username = trim($_POST['username'] ?? '');
	$password = $_POST['password'] ?? '';
	$role = $_POST['role'] ?? 'petugas';
	$password_hash = password_hash($password, PASSWORD_DEFAULT);

	$s = $conn->prepare(
		'INSERT INTO users(nama, username, password, role) VALUES (?, ?, ?, ?)'
	);
	$s->bind_param('ssss', $nama, $username, $password_hash, $role);
	$s->execute();

	log_action('TAMBAH_USER', 'users', $s->insert_id, $username);
	header('Location: users.php');
	exit;
}

if (isset($_GET['nonaktif'])) {
	$id = (int) $_GET['nonaktif'];

	if ($id !== (int) $_SESSION['user']['id']) {
		$conn->query("UPDATE users SET status='nonaktif' WHERE id=$id");
	}

	header('Location: users.php');
	exit;
}

$res = $conn->query(
	'SELECT id, nama, username, role, status, created_at
	 FROM users
	 ORDER BY id DESC'
);

include 'partials/header.php';
?>
<div class="page-title">
	<div>
		<h2>Manajemen Pengguna</h2>
		<p class="muted">Kelola akun admin keuangan dan petugas iuran.</p>
	</div>
</div>

<div class="card">
	<form method="post" class="formgrid">
		<input name="nama" placeholder="Nama" required>
		<input name="username" placeholder="Username" required>
		<input type="password" name="password" placeholder="Password" required>
		<select name="role">
			<option value="petugas">Petugas Iuran</option>
			<option value="admin_keuangan">Admin Keuangan</option>
		</select>
		<button name="simpan" class="btn primary">Tambah Pengguna</button>
	</form>
</div>

<div class="card tablewrap">
	<table>
		<tr>
			<th>Nama</th>
			<th>Username</th>
			<th>Role</th>
			<th>Status</th>
			<th>Aksi</th>
		</tr>
		<?php while ($r = $res->fetch_assoc()): ?>
			<tr>
				<td><?= e($r['nama']) ?></td>
				<td><?= e($r['username']) ?></td>
				<td><?= e($r['role']) ?></td>
				<td><?= e($r['status']) ?></td>
				<td>
					<?php if ((int) $r['id'] !== (int) $_SESSION['user']['id'] && $r['status'] === 'aktif'): ?>
						<a class="btn small" href="?nonaktif=<?= (int) $r['id'] ?>">Nonaktif</a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endwhile; ?>
	</table>
</div>
<?php include 'partials/footer.php'; ?>