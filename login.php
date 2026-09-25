<?php require 'config.php'; if(!empty($_SESSION['user'])){header('Location: dashboard.php');exit;} $err=''; $registered = isset($_GET['registered']);
if($_SERVER['REQUEST_METHOD']==='POST'){
 $u=trim($_POST['username']??'');$p=$_POST['password']??'';
 $s=$conn->prepare("SELECT * FROM users WHERE username=? AND status='aktif' LIMIT 1");$s->bind_param('s',$u);$s->execute();$x=$s->get_result()->fetch_assoc();
 if($x && password_verify($p,$x['password'])){$_SESSION['user']=['id'=>$x['id'],'nama'=>$x['nama'],'username'=>$x['username'],'role'=>$x['role']];log_action('LOGIN');header('Location: dashboard.php');exit;}
 $err='Username atau password salah.';
} ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | Iuran Sampah</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="login"><div class="loginbox"><div class="brand">♻️</div><h1>Iuran Sampah</h1><p>Sistem Pengelolaan Iuran Sampah</p><?php if($err):?><div class="alert danger"><?=e($err)?></div><?php endif;?><?php if($registered):?><div class="alert success">Pendaftaran berhasil. Silakan masuk.</div><?php endif;?>
<form method="post"><label>Username</label><input name="username" required autofocus><label>Password</label><input type="password" name="password" required><button class="btn primary full">Masuk</button></form><p class="hint"><a href="register.php">Belum terdaftar sebagai petugas? Daftar</a></p>
<div class="hint"><b>Akun demo</b><br>Admin: admin / admin123<br>Petugas: petugas / petugas123</div></div></body></html>