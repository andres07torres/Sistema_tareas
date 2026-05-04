<?php
// Forzar la lectura de variables de entorno en Render
$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');
$chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? getenv('TELEGRAM_CHAT_ID');
$token_seguridad = $_ENV['CRON_TOKEN'] ?? getenv('CRON_TOKEN');

// Si no hay variables de entorno, intenta cargar el .env local (solo para tu PC)
if (!$telegramToken && file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $telegramToken = $env['TELEGRAM_TOKEN'];
    $chatId = $env['TELEGRAM_CHAT_ID'];
    $token_seguridad = $env['CRON_TOKEN'];
}

// Validación de seguridad
if (!isset($_GET['token']) || $_GET['token'] !== $token_seguridad) {
    header('HTTP/1.1 401 Unauthorized');
    die("Token de seguridad inválido.");
}

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// 2. Buscar tareas (Rango de 7 días para pruebas)
$query = "SELECT titulo, materia, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes 
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
        
        // Limpiar caracteres especiales que rompen el Markdown de Telegram
        $titulo_limpio = str_replace(['_', '*', '`'], [' ', ' ', ' '], $t['titulo']);
        $materia_limpia = str_replace(['_', '*', '`'], [' ', ' ', ' '], $t['materia'] ?? 'Tarea');

        $mensaje .= "📘 *{$materia_limpia}*\n📝 {$titulo_limpio}\n⏳ $texto_dias ({$t['fecha_entrega']})\n\n";
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
    
    $result = curl_exec($ch); // Capturamos la respuesta de Telegram
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "Error de conexión (CURL): " . $error;
    } else {
        // Esto nos dirá qué dice Telegram exactamente
        echo "Respuesta de Telegram: " . $result;
    }
} else {
    echo "No hay tareas urgentes. Todo al día.";
}
?>