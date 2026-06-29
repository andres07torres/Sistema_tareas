<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');
if (!$telegramToken && file_exists(__DIR__ . '/../../.env')) {
    $env = parse_ini_file(__DIR__ . '/../../.env');
    $telegramToken = $env['TELEGRAM_TOKEN'] ?? '';
}

if (empty($telegramToken)) {
    echo json_encode(['success' => false, 'error' => 'Token de Telegram no configurado']);
    exit;
}

$db = (new Database())->getConnection();

$stmt = $db->query("SELECT chat_id FROM suscriptores");
$suscriptores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($suscriptores)) {
    echo json_encode(['success' => false, 'error' => 'No hay suscriptores']);
    exit;
}

$mensaje = "🔧 *SISTEMA EN MANTENIMIENTO* 🔧\n\nEstamos en mantenimiento para mejorar cambios y así.\n\n⚠️ Algunas funciones pueden no estar disponibles temporalmente.\n\nGracias por tu paciencia.";

$enviados = 0;
foreach ($suscriptores as $sub) {
    $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
    $data = [
        'chat_id' => $sub['chat_id'],
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
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
    curl_close($ch);

    if ($res && isset($res['ok']) && $res['ok']) {
        $enviados++;
    }
}

echo json_encode(['success' => true, 'enviados' => $enviados]);
