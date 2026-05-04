<?php
// 1. Lógica "inteligente" para cargar configuración y capa de seguridad
$env_path = dirname(__DIR__) . '/.env';
if (file_exists($env_path)) {
    $env = parse_ini_file($env_path);
    $token_seguridad = $env['CRON_TOKEN'];
} else {
    $token_seguridad = getenv('CRON_TOKEN');
}

if (!isset($_GET['token']) || $_GET['token'] !== $token_seguridad) {
    die("Acceso denegado. Token inválido.");
}

require_once '../config/database.php';
$db = (new Database())->getConnection();

// 2. Buscar tareas pendientes que venzan entre hoy y los próximos 7 días
$query = "SELECT titulo, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes 
          FROM tareas 
          WHERE estado = 'pendiente' 
          AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7";

$stmt = $db->prepare($query);
$stmt->execute();
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Evaluar y enviar el mensaje
if (count($tareas) > 0) {
    $mensaje = "🔔 *Recordatorio de Entregas Universitarias* 🔔\n\n";
    
    foreach ($tareas as $t) {
        $dias = $t['dias_restantes'];
        $texto_dias = ($dias == 0) ? "¡VENCE HOY!" : "Vence en $dias día(s)";
        $mensaje .= "📚 *{$t['titulo']}*\n⏳ $texto_dias ({$t['fecha_entrega']})\n\n";
    }

    // Traer credenciales de Telegram (Local vs Nube)
    if (isset($env)) {
        $telegramToken = $env['TELEGRAM_TOKEN'];
        $chatId = $env['TELEGRAM_CHAT_ID'];
    } else {
        $telegramToken = getenv('TELEGRAM_TOKEN');
        $chatId = getenv('TELEGRAM_CHAT_ID');
    }
    $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_exec($ch);
    curl_close($ch);
    
    echo "Reporte enviado por Telegram con éxito.";
} else {
    echo "No hay tareas urgentes. Todo al día.";
}
?>