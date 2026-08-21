<?php
// bot_poll.php - Long polling daemon para Telegram
// Ejecutar con: php bot_poll.php (o como servicio)

date_default_timezone_set('America/Guayaquil');

$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

$telegramToken = getenv('TELEGRAM_TOKEN');
$offset = 0;

require_once __DIR__ . '/../config/database.php';

function sendTelegram($method, $data = []) {
    global $telegramToken;
    $url = "https://api.telegram.org/bot{$telegramToken}/" . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function enviarRespuesta($chatId, $mensaje) {
    sendTelegram('sendMessage', [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true,
    ]);
}

function enviarKeyboard($chatId, $mensaje, $botones) {
    sendTelegram('sendMessage', [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $botones], JSON_UNESCAPED_UNICODE),
    ]);
}

function registrarSuscriptor($chatId, $update, $db) {
    $tipo = $update["message"]["chat"]["type"] ?? "private";
    $nombre = ($tipo == "private")
        ? ($update["message"]["from"]["first_name"] ?? "Usuario")
        : ($update["message"]["chat"]["title"] ?? "Grupo");
    $stmt = $db->prepare("INSERT INTO suscriptores (chat_id, nombre, tipo_chat) VALUES (:id, :nom, :tipo) ON CONFLICT (chat_id) DO UPDATE SET nombre = EXCLUDED.nombre");
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
        $res .= "{$icono} *{$titulo}*\n";
        $limiteDrive = $t['limite_drive'] ?? null;
        if ($limiteDrive) $res .= "⌛ *Limite Drive:* {$limiteDrive}\n";
        $res .= "⌛ *Cierre:* {$f_entrega}{$vence}\n";
    }
    return $res;
}

echo "Bot polling iniciado...\n";

while (true) {
    try {
        $db = (new Database())->getConnection();
        $db->exec("UPDATE tareas SET estado = 'inactivo' WHERE estado = 'pendiente' AND fecha_entrega < CURRENT_DATE");

        $result = sendTelegram('getUpdates', [
            'offset' => $offset,
            'timeout' => 30,
            'allowed_updates' => json_encode(['message', 'callback_query']),
        ]);

        if (!$result || !isset($result['result'])) {
            sleep(2);
            continue;
        }

        foreach ($result['result'] as $update) {
            $offset = $update['update_id'] + 1;

            if (isset($update['callback_query'])) {
                $callback = $update['callback_query'];
                $chatId = $callback["message"]["chat"]["id"];
                $data = $callback["data"];

                sendTelegram('answerCallbackQuery', ['callback_query_id' => $callback['id']]);

                if (strpos($data, 'materia|') === 0) {
                    $materia = substr($data, 8);
                    $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega, limite_drive, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' AND materia = :materia ORDER BY fecha_entrega ASC");
                    $stmt->execute([':materia' => $materia]);
                    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $stmtDrive = $db->prepare("SELECT drive_link FROM materias WHERE nombre = ?");
                    $stmtDrive->execute([$materia]);
                    $driveLink = $stmtDrive->fetchColumn();

                    $respuesta = formatearTexto($tareas, "📚 ACTIVIDADES DE *" . strtoupper($materia) . "*");
                    if ($driveLink) $respuesta .= "\n📁 *Carpeta Drive:* [Abrir enlace]({$driveLink})";
                    enviarRespuesta($chatId, $respuesta);
                }
                continue;
            }

            if (!isset($update['message'])) continue;

            $chatId = $update["message"]["chat"]["id"];
            $text = trim($update["message"]["text"] ?? "");

            if (strpos($text, '/') === 0) {
                $parts = explode(' ', $text);
                $cmd = explode('@', $parts[0])[0];
                $text = $cmd . (isset($parts[1]) ? ' ' . implode(' ', array_slice($parts, 1)) : '');
            }

            registrarSuscriptor($chatId, $update, $db);

            if ($text == "/start" || $text == "/ayuda") {
                enviarRespuesta($chatId, "🤖 *Asistente UNEMI Activo*\n\n/hoy - Tareas de hoy\n/semana - Próximos 7 días\n/tareas - Todos los pendientes\n/materias - Ver por materia\n/motivacion - Frase motivacional");
            } elseif ($text == "/hoy") {
                $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega FROM tareas WHERE estado = 'pendiente' AND fecha_entrega = CURRENT_DATE ORDER BY materia ASC");
                $stmt->execute();
                enviarRespuesta($chatId, formatearTexto($stmt->fetchAll(PDO::FETCH_ASSOC), "📅 TAREAS PARA HOY"));
            } elseif ($text == "/semana") {
                $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7 ORDER BY materia ASC, fecha_entrega ASC");
                $stmt->execute();
                enviarRespuesta($chatId, formatearTexto($stmt->fetchAll(PDO::FETCH_ASSOC), "🗓 REPORTE DE LA SEMANA"));
            } elseif ($text == "/tareas") {
                $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' ORDER BY materia ASC, fecha_entrega ASC");
                $stmt->execute();
                enviarRespuesta($chatId, formatearTexto($stmt->fetchAll(PDO::FETCH_ASSOC), "📋 TODOS LOS PENDIENTES"));
            } elseif ($text == "/materias") {
                $stmt = $db->query("SELECT nombre, drive_link FROM materias ORDER BY nombre ASC");
                $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($materias)) {
                    enviarRespuesta($chatId, "☕ No hay materias registradas.");
                } else {
                    $botones = [];
                    foreach ($materias as $m) {
                        $icono = !empty($m['drive_link']) ? "📁" : "📘";
                        $botones[] = [['text' => "{$icono} {$m['nombre']}", 'callback_data' => "materia|{$m['nombre']}"]];
                    }
                    enviarKeyboard($chatId, "📚 *SELECCIONA UNA MATERIA*\n\nElige una materia para ver sus actividades y enlace Drive:", $botones);
                }
            } elseif ($text == "/motivacion") {
                $meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
                $dia = date('j');
                $mes = $meses[(int)date('m') - 1];
                $frases = [
                    "El éxito no es la clave de la felicidad. La felicidad es la clave del éxito. — Albert Schweitzer",
                    "El estudio es la llave que abre todas las puertas. — Anónimo",
                    "No estudies para saber más, estudia para ser más. — Anónimo",
                    "La educación es el arma más poderosa que puedes usar para cambiar el mundo. — Nelson Mandela",
                    "Hoy es $dia de $mes, un día perfecto para avanzar. ¡Tú puedes!",
                    "El mejor momento para empezar fue ayer. El segundo mejor momento es ahora. — Proverbio chino",
                    "La disciplina es el puente entre tus metas y tus logros. — Jim Rohn",
                    "No esperes el momento perfecto, toma el momento y hazlo perfecto. — Zoey Sayward",
                    "El secreto del éxito es empezar. — Mark Twain",
                    "La educación es el pasaporte hacia el futuro. — Malcolm X",
                    "No necesitas ser grande para empezar, pero necesitas empezar para ser grande. — Zig Ziglar",
                    "El éxito no es casualidad, es trabajo duro, perseverancia y aprendizaje. — Anónimo",
                ];
                $frase = $frases[array_rand($frases)];
                enviarRespuesta($chatId, "🌟 *FRASE DEL DÍA*\n📅 {$dia} de {$mes}\n\n_{$frase}_\n\n💪 ¡A darle con todo!");
            }
        }
    } catch (\Throwable $e) {
        error_log("POLL ERROR: " . $e->getMessage());
        sleep(5);
    }
}
