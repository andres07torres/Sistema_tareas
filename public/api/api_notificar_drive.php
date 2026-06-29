<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$db = (new Database())->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

$stmt = $db->prepare("
    SELECT d.*, m.nombre AS materia_nombre
    FROM documentos_drive d
    JOIN materias m ON m.id = d.materia_id
    WHERE d.id = :id
");
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    echo json_encode(['success' => false, 'error' => 'Documento no encontrado']);
    exit;
}

$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
    $telegramToken = $env['TELEGRAM_TOKEN'] ?? '';
} else {
    $telegramToken = getenv('TELEGRAM_TOKEN');
}

if (empty($telegramToken)) {
    echo json_encode(['success' => false, 'error' => 'Token de Telegram no configurado']);
    exit;
}

    $icono = $doc['tipo'] === 'PDF' ? '📄' : '📃';
    $mensaje = "📁 DOCUMENTO EN DRIVE 📁\n\n";
    $mensaje .= "📘 Materia: {$doc['materia_nombre']}\n";
    $mensaje .= "{$icono} *{$doc['nombre']}*\n";
    $mensaje .= "🔗 [Abrir en Drive]({$doc['enlace']})\n\n";
    $mensaje .= "💡 Revisa el material disponible";

$stmt = $db->query("SELECT chat_id FROM suscriptores");
$suscriptores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$exitos = 0;
$fallos = 0;

foreach ($suscriptores as $sub) {
    $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
    $data = [
        'chat_id' => $sub['chat_id'],
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => false,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $raw = curl_exec($ch);
    $res = json_decode($raw, true);

    if ($res && isset($res['ok']) && $res['ok']) {
        $exitos++;
    } else {
        $fallos++;
    }
}

$update = $db->prepare("UPDATE documentos_drive SET notificado = TRUE WHERE id = :id");
$update->execute([':id' => $id]);

echo json_encode([
    'success' => true,
    'message' => "Notificación enviada a $exitos suscriptor(es)." . ($fallos > 0 ? " ($fallos fallos)" : '')
]);
