<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once 'config.php';

$nombre   = trim($_POST['nombre']   ?? '');
$empresa  = trim($_POST['empresa']  ?? '');
$email    = trim($_POST['email']    ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$interes  = trim($_POST['interes']  ?? '');
$volumen  = trim($_POST['volumen']  ?? '');
$mensaje  = trim($_POST['mensaje']  ?? '');

if (empty($nombre) || empty($empresa) || empty($email) || empty($telefono) || empty($interes)) {
    echo json_encode(['success' => false, 'message' => 'Por favor completa todos los campos requeridos']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El email ingresado no es válido']);
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare(
        'INSERT INTO contactos (nombre, empresa, email, telefono, interes, volumen, mensaje)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$nombre, $empresa, $email, $telefono, $interes, $volumen, $mensaje]);

    // Notificación por correo al admin
    $asunto  = '=?UTF-8?B?' . base64_encode('Nueva solicitud de ' . $nombre . ' — Copy Hub') . '?=';
    $wa_link = 'https://wa.me/52' . preg_replace('/\D/', '', $telefono)
             . '?text=' . rawurlencode('Hola ' . $nombre . ', te contactamos de Copy Hub respecto a tu solicitud.');
    $cuerpo  = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:Inter,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
  <!-- Header -->
  <tr><td style="background:linear-gradient(135deg,#1e40af,#2563eb);padding:32px 40px;text-align:center;">
    <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">Copy Hub</h1>
    <p style="margin:6px 0 0;color:#bfdbfe;font-size:14px;">Nueva solicitud de diagnóstico gratuito</p>
  </td></tr>
  <!-- Alert badge -->
  <tr><td style="padding:28px 40px 0;text-align:center;">
    <span style="display:inline-block;background:#fef3c7;color:#92400e;font-size:13px;font-weight:600;padding:6px 16px;border-radius:20px;">🔔 Tienes una nueva solicitud</span>
  </td></tr>
  <!-- Data -->
  <tr><td style="padding:24px 40px;">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr><td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
        <span style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Nombre</span><br>
        <span style="color:#0f172a;font-size:16px;font-weight:600;">' . htmlspecialchars($nombre) . '</span>
      </td></tr>
      <tr><td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
        <span style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Empresa</span><br>
        <span style="color:#0f172a;font-size:16px;">' . htmlspecialchars($empresa) . '</span>
      </td></tr>
      <tr><td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
        <span style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Email</span><br>
        <a href="mailto:' . htmlspecialchars($email) . '" style="color:#2563eb;font-size:16px;text-decoration:none;">' . htmlspecialchars($email) . '</a>
      </td></tr>
      <tr><td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
        <span style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Teléfono</span><br>
        <a href="tel:' . htmlspecialchars($telefono) . '" style="color:#2563eb;font-size:16px;text-decoration:none;">' . htmlspecialchars($telefono) . '</a>
      </td></tr>
      <tr><td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
        <span style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Interés</span><br>
        <span style="color:#0f172a;font-size:16px;">' . htmlspecialchars($interes) . '</span>
      </td></tr>'
      . ($volumen ? '<tr><td style="padding:10px 0;border-bottom:1px solid #f1f5f9;">
        <span style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Volumen mensual</span><br>
        <span style="color:#0f172a;font-size:16px;">' . htmlspecialchars($volumen) . '</span>
      </td></tr>' : '')
      . ($mensaje ? '<tr><td style="padding:10px 0;">
        <span style="color:#64748b;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Mensaje</span><br>
        <span style="color:#0f172a;font-size:15px;line-height:1.6;">' . nl2br(htmlspecialchars($mensaje)) . '</span>
      </td></tr>' : '') . '
    </table>
  </td></tr>
  <!-- CTA buttons -->
  <tr><td style="padding:8px 40px 32px;text-align:center;">
    <a href="mailto:' . htmlspecialchars($email) . '?subject=Solicitud%20Copy%20Hub&body=Hola%20' . rawurlencode($nombre) . '%2C"
       style="display:inline-block;background:#2563eb;color:#ffffff;font-size:14px;font-weight:700;padding:12px 24px;border-radius:8px;text-decoration:none;margin:4px;">
      ✉ Responder por Email
    </a>
    <a href="' . $wa_link . '"
       style="display:inline-block;background:#16a34a;color:#ffffff;font-size:14px;font-weight:700;padding:12px 24px;border-radius:8px;text-decoration:none;margin:4px;">
      💬 Escribir por WhatsApp
    </a>
  </td></tr>
  <!-- Footer -->
  <tr><td style="background:#f8fafc;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0;">
    <p style="margin:0;color:#94a3b8;font-size:12px;">Copy Hub · Panel admin: <a href="https://copyhub.mx/admin/" style="color:#2563eb;">copyhub.mx/admin</a></p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Copy Hub <noreply@copyhub.mx>\r\n";
    $headers .= "Reply-To: " . $nombre . " <" . $email . ">\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    @mail(ADMIN_EMAIL, $asunto, $cuerpo, $headers);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('[CopyHub] submit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la solicitud. Intenta de nuevo.']);
}
