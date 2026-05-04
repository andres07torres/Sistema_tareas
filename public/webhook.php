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
    $query = "SELECT titulo, materia FROM tareas WHERE estado = 'pendiente' AND fecha_entrega = CURRENT_DATE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($tareas) > 0) {
        $mensaje = "📅 *Tareas para hoy:*\n\n";
        foreach ($tareas as $t) {
            $materia = str_replace(['_', '*', '`'], ' ', $t['materia'] ?? 'Tarea');
            $titulo = str_replace(['_', '*', '`'], ' ', $t['titulo']);
            $mensaje .= "📘 *{$materia}*\n📝 {$titulo}\n\n";
        }
    } else {
        $mensaje = "☕ ¡Relax! No tienes tareas para hoy.";
    }
}
elseif ($text == "/semana") {
    $query = "SELECT titulo, materia, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes 
              FROM tareas 
              WHERE estado = 'pendiente' 
              AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7 
              ORDER BY fecha_entrega ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($tareas) > 0) {
        $mensaje = "🗓 *Tareas de la semana:*\n\n";
        foreach ($tareas as $t) {
            $materia = str_replace(['_', '*', '`'], ' ', $t['materia'] ?? 'Tarea');
            $titulo = str_replace(['_', '*', '`'], ' ', $t['titulo']);
            $dias = $t['dias_restantes'];
            $texto_vence = ($dias == 0) ? "¡VENCE HOY!" : "vence en $dias día(s)";
            
            $mensaje .= "📘 *{$materia}*\n📝 {$titulo}\n⏳ $texto_vence ({$t['fecha_entrega']})\n\n";
        }
    } else {
        $mensaje = "✅ Todo al día para esta semana.";
    }
}
elseif ($text == "/tareas") {
    $query = "SELECT titulo, materia, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes 
              FROM tareas 
              WHERE estado = 'pendiente' 
              ORDER BY fecha_entrega ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($tareas) > 0) {
        $mensaje = "📋 *Todas tus tareas pendientes:*\n\n";
        foreach ($tareas as $t) {
            $materia = str_replace(['_', '*', '`'], ' ', $t['materia'] ?? 'Tarea');
            $titulo = str_replace(['_', '*', '`'], ' ', $t['titulo']);
            $dias = $t['dias_restantes'];
            
            if ($dias < 0) {
                $texto_vence = "atrasada por " . abs($dias) . " día(s)";
            } elseif ($dias == 0) {
                $texto_vence = "¡VENCE HOY!";
            } else {
                $texto_vence = "vence en $dias día(s)";
            }

            $mensaje .= "📘 *{$materia}*\n📝 {$titulo}\n⏳ $texto_vence ({$t['fecha_entrega']})\n\n";
        }
    } else {
        $mensaje = "🎉 ¡Felicidades! No tienes tareas pendientes.";
    }
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
