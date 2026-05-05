<?php
// 1. CARGAR CONFIGURACIÓN
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}

$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');

// 2. RECIBIR DATOS
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    die("Sin datos.");
}

// --- FUNCIONES AUXILIARES ---

function enviarMensaje($chatId, $token, $text, $keyboard = null) {
    $url = "https://api.telegram.org/bot" . trim($token) . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];
    if ($keyboard) $data['reply_markup'] = json_encode($keyboard);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

function responderCallback($callbackId, $token) {
    $url = "https://api.telegram.org/bot" . trim($token) . "/answerCallbackQuery";
    $data = ['callback_query_id' => $callbackId];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

function editarMensaje($chatId, $messageId, $token, $text) {
    $url = "https://api.telegram.org/bot" . trim($token) . "/editMessageText";
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

function formatearReporte($tareas, $titulo) {
    if (count($tareas) == 0) return [["text" => "☕ No hay tareas pendientes.", "keyboard" => null]];
    
    $mensajes = [];
    $materiaActual = "";
    $bloque = "{$titulo}\n";
    
    foreach ($tareas as $t) {
        $materia = $t['materia'] ?? 'General';
        if ($materia !== $materiaActual) {
            $bloque .= "\n📘 *{$materia}*\n";
            $materiaActual = $materia;
        }
        
        $id = $t['id'];
        $icono = ($t['tipo'] == 'test') ? "🎓" : "📝";
        $vence = isset($t['dias_restantes']) ? " (vence en {$t['dias_restantes']}d)" : "";
        
        $txt = "{$icono} *{$t['titulo']}*\n⌛ *Cierre:* {$t['fecha_entrega']}{$vence}\n";
        
        $keyboard = [
            'inline_keyboard' => [[
                ['text' => "✅ Completar", 'callback_data' => "completar_{$id}"]
            ]]
        ];
        
        $mensajes[] = ["text" => $bloque . $txt, "keyboard" => $keyboard];
        $bloque = ""; // Reset para enviar uno por uno con sus botones
    }
    return $mensajes;
}

// 3. MANEJO DE CALLBACKS
if (isset($update["callback_query"])) {
    $cb = $update["callback_query"];
    $callbackId = $cb["id"];
    $chatId = $cb["message"]["chat"]["id"];
    $messageId = $cb["message"]["message_id"];
    $data = $cb["data"];

    responderCallback($callbackId, $telegramToken);

    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();

    if (strpos($data, "completar_") === 0) {
        $id = str_replace("completar_", "", $data);
        $stmt = $db->prepare("UPDATE tareas SET estado = 'completada' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        editarMensaje($chatId, $messageId, $telegramToken, "✅ ¡Tarea marcada como completada!");
    }
    exit;
}

// 4. LÓGICA DE COMANDOS
if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = trim($update["message"]["text"] ?? "");

    if (strpos($text, '/') === 0) {
        $parts = explode(' ', $text);
        $cmd = explode('@', $parts[0])[0];
        $text = $cmd . (isset($parts[1]) ? ' ' . implode(' ', array_slice($parts, 1)) : '');
    }

    try {
        require_once __DIR__ . '/../config/database.php';
        $db = (new Database())->getConnection();

        if ($text == "/start" || $text == "/ayuda") {
            enviarMensaje($chatId, $telegramToken, "🤖 *Asistente UNEMI Activo*\n(VERSIÓN: RAMA PRUEBAS)\n\nUsa /tareas para ver tus pendientes.");
        }
        elseif ($text == "/hoy") {
            $stmt = $db->prepare("SELECT id, titulo, materia, tipo, fecha_entrega FROM tareas WHERE estado = 'pendiente' AND fecha_entrega = CURRENT_DATE ORDER BY materia ASC");
            $stmt->execute();
            $reportes = formatearReporte($stmt->fetchAll(PDO::FETCH_ASSOC), "📅 HOY");
            foreach ($reportes as $r) enviarMensaje($chatId, $telegramToken, $r['text'], $r['keyboard']);
        }
        elseif ($text == "/semana") {
            $stmt = $db->prepare("SELECT id, titulo, materia, tipo, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7 ORDER BY materia ASC, fecha_entrega ASC");
            $stmt->execute();
            $reportes = formatearReporte($stmt->fetchAll(PDO::FETCH_ASSOC), "🗓 SEMANA");
            foreach ($reportes as $r) enviarMensaje($chatId, $telegramToken, $r['text'], $r['keyboard']);
        }
        elseif ($text == "/tareas") {
            $stmt = $db->prepare("SELECT id, titulo, materia, tipo, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' ORDER BY fecha_entrega ASC");
            $stmt->execute();
            $reportes = formatearReporte($stmt->fetchAll(PDO::FETCH_ASSOC), "📋 TOTAL");
            foreach ($reportes as $r) enviarMensaje($chatId, $telegramToken, $r['text'], $r['keyboard']);
        }
    } catch (Exception $e) {
        enviarMensaje($chatId, $telegramToken, "⚠️ Error: " . $e->getMessage());
    }
}
