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

if (!$update || !isset($update["message"])) {
    die("Sin mensaje.");
}

// --- FUNCIONES ---

function enviarRespuesta($chatId, $token, $mensaje) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Registra o actualiza un suscriptor en la base de datos
 */
function registrarSuscriptor($chatId, $update, $db) {
    $nombre = "Desconocido";
    $tipo = $update["message"]["chat"]["type"] ?? "private";

    if ($tipo == "private") {
        $nombre = $update["message"]["from"]["first_name"] ?? "Usuario";
    } else {
        $nombre = $update["message"]["chat"]["title"] ?? "Grupo";
    }

    $stmt = $db->prepare("INSERT INTO suscriptores (chat_id, nombre, tipo_chat) 
                          VALUES (:id, :nom, :tipo) 
                          ON CONFLICT (chat_id) DO UPDATE SET nombre = EXCLUDED.nombre");
    $stmt->execute([':id' => $chatId, ':nom' => $nombre, ':tipo' => $tipo]);
}

function formatearTexto($tareas, $titulo_seccion) {
    if (count($tareas) == 0) return "☕ No hay tareas pendientes.";
    
    $res = "{$titulo_seccion}\n";
    $materiaActual = "";
    
    foreach ($tareas as $t) {
        $materia = $t['materia'] ?? 'General';
        $titulo = $t['titulo'];
        $f_entrega = $t['fecha_entrega'] ?? 'N/A';
        $dias = $t['dias_restantes'] ?? null;
        $tipo = $t['tipo'] ?? 'tarea';
        
        if ($materia !== $materiaActual) {
            $res .= "\n📘 *{$materia}*\n";
            $materiaActual = $materia;
        }
        
        $icono = ($tipo == 'test') ? "🎓" : "📝";
        $vence = "";
        if ($dias !== null) {
            if ($dias < 0) $vence = " (atrasada " . abs($dias) . "d)";
            elseif ($dias == 0) $vence = " (¡HOY!)";
            else $vence = " (vence en {$dias}d)";
        }
        
        $res .= "{$icono} *{$titulo}*\n⌛ *Cierre:* {$f_entrega}{$vence}\n";
    }
    return $res;
}

// 3. EJECUCIÓN
$chatId = $update["message"]["chat"]["id"];
$text = trim($update["message"]["text"] ?? "");

// Limpieza para grupos
if (strpos($text, '/') === 0) {
    $parts = explode(' ', $text);
    $cmd = explode('@', $parts[0])[0];
    $text = $cmd . (isset($parts[1]) ? ' ' . implode(' ', array_slice($parts, 1)) : '');
}

try {
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();

    // --- CIERRE AUTOMÁTICO DE TAREAS VENCIDAS ---
    $db->exec("UPDATE tareas SET estado = 'inactivo' WHERE estado = 'pendiente' AND fecha_entrega < CURRENT_DATE");

    // REGISTRO AUTOMÁTICO DE SUSCRIPTOR
    registrarSuscriptor($chatId, $update, $db);

    if ($text == "/start" || $text == "/ayuda") {
        enviarRespuesta($chatId, $telegramToken, "🤖 *Asistente UNEMI Activo*\n\n/hoy - Tareas de hoy\n/semana - Próximos 7 días\n/tareas - Todos los pendientes");
    }
    elseif ($text == "/hoy") {
        $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega FROM tareas WHERE estado = 'pendiente' AND fecha_entrega = CURRENT_DATE ORDER BY materia ASC");
        $stmt->execute();
        enviarRespuesta($chatId, $telegramToken, formatearTexto($stmt->fetchAll(PDO::FETCH_ASSOC), "📅 TAREAS PARA HOY"));
    }
    elseif ($text == "/semana") {
        $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7 ORDER BY materia ASC, fecha_entrega ASC");
        $stmt->execute();
        enviarRespuesta($chatId, $telegramToken, formatearTexto($stmt->fetchAll(PDO::FETCH_ASSOC), "🗓 REPORTE DE LA SEMANA"));
    }
    elseif ($text == "/tareas") {
        $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' ORDER BY materia ASC, fecha_entrega ASC");
        $stmt->execute();
        enviarRespuesta($chatId, $telegramToken, formatearTexto($stmt->fetchAll(PDO::FETCH_ASSOC), "📋 TODOS LOS PENDIENTES"));
    }

} catch (Exception $e) {
    enviarRespuesta($chatId, $telegramToken, "⚠️ Error: " . $e->getMessage());
}
