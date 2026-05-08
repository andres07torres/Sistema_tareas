<?php
// 1. CONFIGURACIÓN Y SEGURIDAD
$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');
$token_seguridad = $_ENV['CRON_TOKEN'] ?? getenv('CRON_TOKEN');

// Carga local si falla lo anterior
if (!$telegramToken && file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $telegramToken = $env['TELEGRAM_TOKEN'];
    $token_seguridad = $env['CRON_TOKEN'];
}

if (!isset($_GET['token']) || $_GET['token'] !== $token_seguridad) {
    header('HTTP/1.1 401 Unauthorized');
    die("Token de seguridad inválido.");
}

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// 2. BUSCAR TAREAS PRÓXIMAS (0-7 días)
$queryTareas = "SELECT titulo, materia, tipo, fecha_apertura, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes 
                FROM tareas 
                WHERE estado = 'pendiente' 
                AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7
                ORDER BY materia ASC, fecha_entrega ASC";

$stmt = $db->prepare($queryTareas);
$stmt->execute();
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($tareas) === 0) {
    die("No hay tareas urgentes hoy.");
}

// 3. CONSTRUIR EL MENSAJE (Versión ultraligera para evitar errores de tamaño)
$totalTareas = count($tareas);
$appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL');
if (!$appUrl && file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $appUrl = $env['APP_URL'] ?? '';
}

$mensaje = "🔔 *REPORTE DE TAREAS* 🔔\n\n";
$mensaje .= "Tienes *{$totalTareas}* actividades por entregar en los próximos 7 días:\n\n";

foreach ($tareas as $t) {
    $icono = ($t['tipo'] == 'test') ? "🎓" : "📝";
    $dias = $t['dias_restantes'];
    $plazo = ($dias == 0) ? "*¡HOY!*" : ($dias == 1 ? "mañana" : "en $dias días");
    $mensaje .= "{$icono} *{$t['titulo']}* - {$plazo}\n";
}

$mensaje .= "\n🔗 *Panel de Control:* \n[Abrir para más detalles]($appUrl/vencimientos.php)";
$mensaje .= "\n\n🚀 _¡A estudiar se ha dicho!_";

// 4. ENVIAR A TODOS LOS SUSCRIPTORES
$stmtSubs = $db->prepare("SELECT chat_id, nombre FROM suscriptores");
$stmtSubs->execute();
$suscriptores = $stmtSubs->fetchAll(PDO::FETCH_ASSOC);

$enviados = 0;
$errores = 0;

foreach ($suscriptores as $sub) {
    $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
    $data = [
        'chat_id' => $sub['chat_id'],
        'text' => $mensaje,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if ($res && $res['ok']) {
        $enviados++;
    } else {
        $errores++;
    }
}

echo "Proceso completado.\n✅ Enviados: $enviados\n❌ Errores: $errores";
?>