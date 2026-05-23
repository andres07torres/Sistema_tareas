<?php
ob_start();

$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');
$token_seguridad = $_ENV['CRON_TOKEN'] ?? getenv('CRON_TOKEN');

if (!$telegramToken && file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $telegramToken = $env['TELEGRAM_TOKEN'] ?? '';
    $token_seguridad = $env['CRON_TOKEN'] ?? '';
}

if (!isset($_GET['token']) || $_GET['token'] !== $token_seguridad) {
    header('HTTP/1.1 401 Unauthorized');
    ob_end_clean();
    die("Token de seguridad inválido.");
}

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

function logMsg($msg) {
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    file_put_contents($logDir . '/motivador.log', "[$date] [IP: $ip] $msg\n", FILE_APPEND);
}

logMsg("Iniciando envío de motivación diaria...");

$meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
$dia = date('j');
$mes = $meses[(int)date('m') - 1];

$frases = [
    "El éxito no es la clave de la felicidad. La felicidad es la clave del éxito. — Albert Schweitzer",
    "La educación es el arma más poderosa para cambiar el mundo. — Nelson Mandela",
    "No te rindas, los principios son duros, pero el final es hermoso.",
    "El conocimiento te da poder, pero el carácter te da respeto.",
    "Los grandes logros requieren tiempo y perseverancia. ¡No te rindas!",
    "Hoy es {$dia} de {$mes}, un día perfecto para avanzar. ¡Tú puedes!",
    "El mejor momento para empezar fue ayer. El segundo mejor momento es ahora.",
    "La disciplina es el puente entre tus metas y tus logros. — Jim Rohn",
    "Cada día es una nueva oportunidad para mejorar.",
    "El éxito es la suma de pequeños esfuerzos repetidos día tras día. — Robert Collier",
    "Cree en ti y en tu capacidad para lograr lo que te propongas.",
    "Las dificultades preparan a personas comunes para destinos extraordinarios. — C.S. Lewis",
    "No cuentes los días, haz que los días cuenten. — Muhammad Ali",
    "El estudio profundo construye mentes brillantes. ¡Sigue adelante!",
    "No esperes el momento perfecto, toma el momento y hazlo perfecto.",
    "La constancia vence lo que la fuerza no puede.",
    "Si puedes soñarlo, puedes lograrlo. — Walt Disney",
    "Tu única competencia eres tú mismo. Sé mejor que ayer.",
    "La educación es la base sobre la cual construimos nuestro futuro.",
    "Cada hora de estudio es una inversión en tu futuro.",
    "La perseverancia no es una carrera larga, son muchas carreras cortas.",
    "No importa cuán lento vayas, mientras no te detengas. — Confucio"
];

$frase = $frases[array_rand($frases)];
$mensaje = "🌅 *BUENOS DÍAS* 🌅\n\n📅 {$dia} de {$mes}\n\n_“{$frase}”_\n\n💪 ¡Hoy será un gran día!";

$stmtSubs = $db->prepare("SELECT chat_id, nombre FROM suscriptores");
$stmtSubs->execute();
$suscriptores = $stmtSubs->fetchAll(PDO::FETCH_ASSOC);

$enviados = 0;
foreach ($suscriptores as $sub) {
    $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
    $data = ['chat_id' => $sub['chat_id'], 'text' => $mensaje, 'parse_mode' => 'Markdown'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $rawResponse = curl_exec($ch);
    $res = json_decode($rawResponse, true);
    $error = curl_error($ch);
    curl_close($ch);

    if ($res && isset($res['ok']) && $res['ok']) {
        $enviados++;
        logMsg("Motivación enviada a: {$sub['nombre']} ({$sub['chat_id']})");
    } else {
        $errorMsg = $res['description'] ?? $error ?? 'Error desconocido';
        logMsg("Fallo al enviar a {$sub['nombre']} ({$sub['chat_id']}): $errorMsg");
    }
}

logMsg("Motivación diaria finalizada. Total enviados: $enviados.");

ob_end_clean();
header('Content-Type: text/plain');
echo "OK";
exit;
