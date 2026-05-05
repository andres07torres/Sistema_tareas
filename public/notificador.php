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

// 3. CONSTRUIR EL MENSAJE
$mensaje = "🔔 *RECORDATORIO DIARIO* 🔔\n\n";
$materiaActual = "";

foreach ($tareas as $t) {
    $materia = str_replace(['_', '*', '`'], ' ', $t['materia'] ?? 'General');
    $titulo = str_replace(['_', '*', '`'], ' ', $t['titulo']);
    $dias = $t['dias_restantes'];
    
    if ($materia !== $materiaActual) {
        $mensaje .= "\n📘 *{$materia}*\n";
        $materiaActual = $materia;
    }

    $icono = ($t['tipo'] == 'test') ? "🎓" : "📝";
    $texto_dias = ($dias == 0) ? "¡VENCE HOY!" : "vence en $dias día(s)";
    $mensaje .= "{$icono} {$titulo}\n⌛ *Cierre:* {$t['fecha_entrega']} ({$texto_dias})\n";
}
$mensaje .= "\n🚀 _¡A estudiar se ha dicho!_";

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