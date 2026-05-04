<?php
// Forzar la lectura de variables de entorno
$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');

// Leer el JSON que envía Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update["message"])) {
    die("No hay datos de Telegram.");
}

$chatId = $update["message"]["chat"]["id"];
$text = trim($update["message"]["text"]);

// Soporte para grupos: Quitar el nombre del bot si viene en el comando (ej: /hoy@TareasUNEMI_bot -> /hoy)
$text = explode('@', $text)[0];

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$mensaje = "";

// Función para formatear listas agrupadas por materia
function formatearListaTareas($tareas, $titulo_seccion) {
    if (count($tareas) == 0) return "";
    
    $mensaje = "{$titulo_seccion}\n\n";
    $materiaActual = "";
    
    foreach ($tareas as $t) {
        $materia = str_replace(['_', '*', '`'], ' ', $t['materia'] ?? 'General');
        $titulo = str_replace(['_', '*', '`'], ' ', $t['titulo']);
        $dias = $t['dias_restantes'] ?? null;
        
        // Si la materia cambia, ponemos un nuevo encabezado
        if ($materia !== $materiaActual) {
            $mensaje .= "\n📘 *{$materia}*\n";
            $materiaActual = $materia;
        }
        
        // Formatear el tiempo restante
        $texto_vence = "";
        if ($dias !== null) {
            if ($dias < 0) $texto_vence = " (atrasada " . abs($dias) . "d)";
            elseif ($dias == 0) $texto_vence = " (¡HOY!)";
            else $texto_vence = " (vence en {$dias}d)";
        }

        $mensaje .= "📝 {$titulo}{$texto_vence}\n";
    }
    return $mensaje;
}

// Lógica de Comandos
if ($text == "/start" || $text == "/ayuda") {
    $mensaje = "🤖 *Asistente de Tareas UNEMI* 📚\n\n";
    $mensaje .= "Aquí tienes los comandos disponibles:\n";
    $mensaje .= "/hoy - Tareas que vencen hoy\n";
    $mensaje .= "/semana - Resumen de los próximos 7 días\n";
    $mensaje .= "/tareas - Ver todos los pendientes\n";
    $mensaje .= "/ayuda - Guía de uso y comandos";
} 
elseif ($text == "/hoy") {
    $query = "SELECT titulo, materia FROM tareas WHERE estado = 'pendiente' AND fecha_entrega = CURRENT_DATE ORDER BY materia ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $res = formatearListaTareas($tareas, "📅 *Tareas para hoy:*");
    $mensaje = ($res !== "") ? $res : "☕ ¡Relax! No tienes tareas para hoy.";
}
elseif ($text == "/semana") {
    $query = "SELECT titulo, materia, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes 
              FROM tareas WHERE estado = 'pendiente' AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7 
              ORDER BY materia ASC, fecha_entrega ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $res = formatearListaTareas($tareas, "🗓 *Tareas de la semana:*");
    $mensaje = ($res !== "") ? $res : "✅ Todo al día para esta semana.";
}
elseif ($text == "/tareas") {
    $query = "SELECT titulo, materia, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes 
              FROM tareas WHERE estado = 'pendiente' ORDER BY materia ASC, fecha_entrega ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $res = formatearListaTareas($tareas, "📋 *Todas tus tareas pendientes:*");
    $mensaje = ($res !== "") ? $res : "🎉 ¡Felicidades! No tienes tareas pendientes.";
}

// Enviar respuesta a Telegram
if ($mensaje !== "") {
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
}
