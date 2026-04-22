<?php
session_start();

if (!isset($_SESSION['admin_logged'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';

// CSV Export (before any HTML output)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS);
        $rows = $pdo->query('SELECT nombre, empresa, email, telefono, interes, volumen, mensaje, fecha FROM contactos ORDER BY fecha DESC')->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contactos_copyhub_'.date('Y-m-d').'.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['Nombre','Empresa','Email','Teléfono','Interés','Volumen','Mensaje','Fecha']);
        foreach ($rows as $row) fputcsv($out, $row);
        fclose($out);
    } catch (Exception $e) { echo 'Error al exportar'; }
    exit;
}

// Mark as read
if (isset($_GET['leido']) && is_numeric($_GET['leido'])) {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $pdo->prepare('UPDATE contactos SET leido=1 WHERE id=?')->execute([(int)$_GET['leido']]);
    } catch (Exception $e) {}
    header('Location: index.php');
    exit;
}

// Delete
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $pdo->prepare('DELETE FROM contactos WHERE id=?')->execute([(int)$_GET['eliminar']]);
    } catch (Exception $e) {}
    header('Location: index.php');
    exit;
}

// Fetch data
$contactos = [];
$total = $nuevos = $hoy = 0;
$dbError = null;
$search = trim($_GET['buscar'] ?? '');
$filter = $_GET['filtro'] ?? 'todos';
$verDetalle = isset($_GET['ver']) && is_numeric($_GET['ver']) ? (int)$_GET['ver'] : 0;

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $where = [];
    $params = [];
    if (!empty($search)) {
        $where[] = '(nombre LIKE ? OR empresa LIKE ? OR email LIKE ?)';
        $like = "%$search%";
        $params = [$like, $like, $like];
    }
    if ($filter === 'nuevos') $where[] = 'leido = 0';
    elseif ($filter === 'leidos') $where[] = 'leido = 1';

    $sql = 'SELECT * FROM contactos' . (!empty($where) ? ' WHERE '.implode(' AND ',$where) : '') . ' ORDER BY fecha DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contactos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total  = (int)$pdo->query('SELECT COUNT(*) FROM contactos')->fetchColumn();
    $nuevos = (int)$pdo->query('SELECT COUNT(*) FROM contactos WHERE leido=0')->fetchColumn();
    $hoy    = (int)$pdo->query("SELECT COUNT(*) FROM contactos WHERE DATE(fecha)=CURDATE()")->fetchColumn();

} catch (PDOException $e) {
    $dbError = 'No se pudo conectar a la base de datos. Verifica las credenciales en <strong>config.php</strong>.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Copy Hub - Panel de Administración</title>
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <header class="admin-header">
    <div class="admin-header__inner">
      <img src="../images/copy-hub-logo2.avif" alt="Copy Hub" class="admin-logo-img">
      <div class="admin-header__right">
        <span class="admin-user"><i class="fa-solid fa-circle-user"></i> <?= htmlspecialchars(ADMIN_USERNAME) ?></span>
        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
      </div>
    </div>
  </header>

  <div class="admin-main">

    <!-- Stats -->
    <div class="admin-stats">
      <div class="stat-card stat-card--blue">
        <i class="fa-solid fa-users"></i>
        <div>
          <span class="stat-num"><?= $total ?></span>
          <span class="stat-label">Total solicitudes</span>
        </div>
      </div>
      <div class="stat-card stat-card--orange">
        <i class="fa-solid fa-bell"></i>
        <div>
          <span class="stat-num"><?= $nuevos ?></span>
          <span class="stat-label">Sin leer</span>
        </div>
      </div>
      <div class="stat-card stat-card--green">
        <i class="fa-solid fa-calendar-day"></i>
        <div>
          <span class="stat-num"><?= $hoy ?></span>
          <span class="stat-label">Recibidas hoy</span>
        </div>
      </div>
    </div>

    <?php if ($dbError): ?>
      <div class="alert alert-error"><?= $dbError ?></div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="admin-toolbar">
      <form method="GET" class="search-form">
        <input type="text" name="buscar" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nombre, empresa o email…">
        <select name="filtro">
          <option value="todos"  <?= $filter==='todos'  ? 'selected':'' ?>>Todos</option>
          <option value="nuevos" <?= $filter==='nuevos' ? 'selected':'' ?>>Sin leer</option>
          <option value="leidos" <?= $filter==='leidos' ? 'selected':'' ?>>Leídos</option>
        </select>
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        <?php if ($search || $filter !== 'todos'): ?>
          <a href="index.php" class="btn-clear">Limpiar</a>
        <?php endif; ?>
      </form>
      <a href="?export=csv" class="btn-export"><i class="fa-solid fa-file-csv"></i> Exportar CSV</a>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <?php if (empty($contactos)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-inbox"></i>
          <p><?= $dbError ? 'Configura la base de datos para ver solicitudes.' : 'No hay solicitudes aún.' ?></p>
        </div>
      <?php else: ?>
        <table class="contacts-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Empresa</th>
              <th>Email</th>
              <th>Teléfono</th>
              <th>Interés</th>
              <th>Fecha</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($contactos as $c): ?>
              <tr class="<?= !$c['leido'] ? 'row-new' : '' ?>">
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['nombre']) ?></td>
                <td><?= htmlspecialchars($c['empresa']) ?></td>
                <td><a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a></td>
                <td><a href="tel:<?= htmlspecialchars($c['telefono']) ?>"><?= htmlspecialchars($c['telefono']) ?></a></td>
                <td><?= htmlspecialchars($c['interes']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($c['fecha'])) ?></td>
                <td>
                  <?php if (!$c['leido']): ?>
                    <span class="badge-new">Nuevo</span>
                  <?php else: ?>
                    <span class="badge-read">Leído</span>
                  <?php endif; ?>
                </td>
                <td class="actions">
                  <?php if (!$c['leido']): ?>
                    <a href="?leido=<?= $c['id'] ?>" class="btn-action btn-read" title="Marcar como leído"><i class="fa-solid fa-check"></i></a>
                  <?php endif; ?>
                  <a href="?ver=<?= $c['id'] ?><?= $search ? '&buscar='.urlencode($search) : '' ?><?= $filter !== 'todos' ? '&filtro='.$filter : '' ?>"
                     class="btn-action btn-view <?= $verDetalle === (int)$c['id'] ? 'active' : '' ?>"
                     title="Ver detalle"><i class="fa-solid fa-eye"></i></a>
                  <a href="?eliminar=<?= $c['id'] ?>" class="btn-action btn-delete" title="Eliminar"
                     onclick="return confirm('¿Eliminar esta solicitud? Esta acción no se puede deshacer.')"><i class="fa-solid fa-trash"></i></a>
                </td>
              </tr>

              <?php if ($verDetalle === (int)$c['id']): ?>
              <tr class="detail-row">
                <td colspan="9">
                  <div class="detail-card">
                    <h4><i class="fa-solid fa-user"></i> <?= htmlspecialchars($c['nombre']) ?> — <?= htmlspecialchars($c['empresa']) ?></h4>
                    <?php if ($c['volumen']): ?>
                      <p><strong>Volumen mensual:</strong> <?= htmlspecialchars($c['volumen']) ?></p>
                    <?php endif; ?>
                    <p><strong>Mensaje:</strong> <?= $c['mensaje'] ? nl2br(htmlspecialchars($c['mensaje'])) : '<em style="color:#94a3b8">Sin mensaje adicional</em>' ?></p>
                    <div class="detail-actions">
                      <a href="mailto:<?= htmlspecialchars($c['email']) ?>?subject=Solicitud%20Copy%20Hub&body=Hola%20<?= urlencode($c['nombre']) ?>%2C"
                         class="btn-contact"><i class="fa-solid fa-envelope"></i> Responder por email</a>
                      <a href="https://wa.me/52<?= preg_replace('/\D/','',$c['telefono']) ?>?text=Hola+<?= urlencode($c['nombre']) ?>%2C+te+contactamos+de+Copy+Hub+respecto+a+tu+solicitud."
                         target="_blank" class="btn-contact btn-wa"><i class="fa-brands fa-whatsapp"></i> Escribir por WhatsApp</a>
                    </div>
                  </div>
                </td>
              </tr>
              <?php endif; ?>

            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>
