<?php
ob_start(); // Prevenir cualquier salida accidental

// 1. CONFIGURACIÓN Y SEGURIDAD
$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');
$token_seguridad = $_ENV['CRON_TOKEN'] ?? getenv('CRON_TOKEN');

// Carga local de variables de entorno si existen
if (!$telegramToken && file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $telegramToken = $env['TELEGRAM_TOKEN'] ?? '';
    $token_seguridad = $env['CRON_TOKEN'] ?? '';
}

// Validación de token
if (!isset($_GET['token']) || $_GET['token'] !== $token_seguridad) {
    header('HTTP/1.1 401 Unauthorized');
    ob_end_clean();
    die("Token de seguridad inválido.");
}

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// Función de logging simple
function logMsg($msg) {
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    file_put_contents($logDir . '/notificador.log', "[$date] [IP: $ip] $msg\n", FILE_APPEND);
}

// --- PREVENIR DUPLICADOS (BLOQUEO TEMPRANO) ---
// Intentamos insertar primero para "ganar" el derecho de envío
try {
    $stmtLock = $db->prepare("INSERT INTO control_envios (fecha) VALUES (CURRENT_DATE) ON CONFLICT (fecha) DO NOTHING");
    $stmtLock->execute();
    
    // Si no se insertó ninguna fila, significa que ya existía (conflicto ignorado)
    if ($stmtLock->rowCount() === 0) {
        logMsg("Intento de envío duplicado detectado. Abortando.");
        ob_end_clean();
        echo "Ya se envió el reporte hoy.";
        exit;
    }
} catch (Exception $e) {
    logMsg("Error al verificar/crear bloqueo de envío: " . $e->getMessage());
    ob_end_clean();
    die("Error interno de control.");
}

logMsg("Iniciando proceso de notificación...");
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
logMsg("User-Agent: $ua");

// --- CIERRE AUTOMÁTICO DE TAREAS VENCIDAS ---
$db->exec("UPDATE tareas SET estado = 'inactivo' WHERE estado = 'pendiente' AND fecha_entrega < CURRENT_DATE");

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
    logMsg("No hay tareas pendientes para hoy.");
    ob_end_clean();
    echo "No hay tareas hoy.";
    exit;
}

// 3. CONSTRUIR EL MENSAJE
$totalTareas = count($tareas);
$mensaje = "🔔 *REPORTE DE TAREAS* 🔔\n\n";
$mensaje .= "Tienes *{$totalTareas}* actividades por entregar:\n";

foreach ($tareas as $t) {
    $icono = ($t['tipo'] == 'test') ? "🎓" : "📝";
    $dias = $t['dias_restantes'];
    $plazo = ($dias == 0) ? "*¡VENCE HOY!*" : "vence en $dias días";
    $materia = str_replace(['_', '*', '`'], ' ', $t['materia']);
    
    $mensaje .= "\n📘 *{$materia}*\n";
    $mensaje .= "{$icono} *{$t['titulo']}*\n";
    $mensaje .= "📅 *Inicio:* {$t['fecha_apertura']}\n";
    $mensaje .= "⌛ *Cierre:* {$t['fecha_entrega']} ({$plazo})\n";
}

$mensaje .= "\n🚀 _¡A estudiar se ha dicho!_";

// 4. ENVIAR A SUSCRIPTORES
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Timeout de conexión
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);        // Timeout total

    $rawResponse = curl_exec($ch);
    $res = json_decode($rawResponse, true);
    $error = curl_error($ch);
    curl_close($ch);

    if ($res && isset($res['ok']) && $res['ok']) {
        $enviados++;
        logMsg("Mensaje enviado con éxito a: {$sub['nombre']} ({$sub['chat_id']})");
    } else {
        $errorMsg = $res['description'] ?? $error ?? 'Error desconocido';
        logMsg("Fallo al enviar a {$sub['nombre']} ({$sub['chat_id']}): $errorMsg");
    }
}

logMsg("Proceso finalizado. Total enviados: $enviados.");

// RESPUESTA FINAL LIGERA PARA EL CRON-JOB
ob_end_clean();
header('Content-Type: text/plain');
echo "OK";
exit;