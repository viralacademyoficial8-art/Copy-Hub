<?php
session_start();

if (isset($_SESSION['admin_logged'])) {
    header('Location: index.php');
    exit;
}

require_once '../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['username'] === ADMIN_USERNAME && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Copy Hub - Acceso Admin</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body class="login-body">
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-brand">
        <img src="../images/copy-hub-logo.jpeg" alt="Copy Hub" class="login-logo-img">
        <p>Panel de Administración</p>
      </div>
      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" class="login-form" autocomplete="off">
        <div class="field-group">
          <label>Usuario</label>
          <input type="text" name="username" placeholder="admin" required autofocus>
        </div>
        <div class="field-group">
          <label>Contraseña</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-login">Ingresar al panel</button>
      </form>
    </div>
  </div>
</body>
</html>
