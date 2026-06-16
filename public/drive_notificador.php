<?php
ob_start();

// ─── CONFIGURACIÓN ───
$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');
$tokenSeguridad = $_ENV['CRON_TOKEN'] ?? getenv('CRON_TOKEN');
$googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID');
$googleClientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET');
$googleRefreshToken = $_ENV['GOOGLE_REFRESH_TOKEN'] ?? getenv('GOOGLE_REFRESH_TOKEN');

// Carga local de .env si es necesario
if ((!$telegramToken || !$googleClientId) && file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $telegramToken = $env['TELEGRAM_TOKEN'] ?? $telegramToken;
    $tokenSeguridad = $env['CRON_TOKEN'] ?? $tokenSeguridad;
    $googleClientId = $env['GOOGLE_CLIENT_ID'] ?? $googleClientId;
    $googleClientSecret = $env['GOOGLE_CLIENT_SECRET'] ?? $googleClientSecret;
    $googleRefreshToken = $env['GOOGLE_REFRESH_TOKEN'] ?? $googleRefreshToken;
}

// Validación de token de seguridad
if (!isset($_GET['token']) || $_GET['token'] !== $tokenSeguridad) {
    header('HTTP/1.1 401 Unauthorized');
    ob_end_clean();
    die("Token de seguridad inválido.");
}

// Validar que Google OAuth esté configurado
if (empty($googleClientId) || empty($googleClientSecret) || empty($googleRefreshToken)) {
    header('HTTP/1.1 500 Internal Server Error');
    ob_end_clean();
    die("Error: Google Drive OAuth no configurado. Ejecuta setup_google_oauth.php primero.");
}

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// ─── LOGGING ───
function logMsg($msg) {
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CRON';
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    file_put_contents($logDir . '/drive_notificador.log', "[$date] [IP: $ip] $msg\n", FILE_APPEND);
}

logMsg("=== INICIO ESCANEO DRIVE ===");

// ─── OBTENER ACCESS TOKEN ───
logMsg("Solicitando access token...");
$accessToken = obtenerAccessToken($googleClientId, $googleClientSecret, $googleRefreshToken);
if (!$accessToken) {
    logMsg("FATAL: No se pudo obtener access token de Google.");
    ob_end_clean();
    die("Error de autenticación Google.");
}
logMsg("Access token obtenido.");

// ─── OBTENER MATERIAS CON DRIVE LINK ───
$stmt = $db->query("SELECT id, nombre, drive_link FROM materias WHERE drive_link IS NOT NULL AND drive_link != ''");
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($materias)) {
    logMsg("No hay materias con enlace Drive configurado.");
    ob_end_clean();
    echo "Sin materias con Drive.";
    exit;
}

logMsg("Materias con Drive: " . count($materias));

$totalNuevos = 0;
$totalNotificados = 0;

foreach ($materias as $materia) {
    $folderId = extraerFolderId($materia['drive_link']);
    if (!$folderId) {
        logMsg("No se pudo extraer folder ID de: {$materia['nombre']} ({$materia['drive_link']})");
        continue;
    }

    logMsg("Escaneando: {$materia['nombre']} (folder: $folderId)");

    $archivos = listarArchivosRecursivo($accessToken, $folderId, 1);
    if ($archivos === false) {
        logMsg("Error al listar archivos de: {$materia['nombre']}");
        continue;
    }

    logMsg("  Archivos encontrados: " . count($archivos));

    foreach ($archivos as $archivo) {
        $archivoId = $archivo['id'];
        $nombre = $archivo['name'];
        $mimeType = $archivo['mimeType'];
        $tipo = obtenerTipoDocumento($mimeType);
        $folderParent = $archivo['parentFolder'] ?? $folderId;
        $enlace = "https://drive.google.com/drive/folders/{$folderParent}";
        $creadoEn = $archivo['createdTime'] ?? date('c');

        // Verificar si ya existe registrado
        $check = $db->prepare("SELECT id, notificado FROM documentos_drive WHERE materia_id = :mid AND archivo_id = :aid");
        $check->execute([':mid' => $materia['id'], ':aid' => $archivoId]);
        $existente = $check->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            continue; // Ya registrado
        }

        // Insertar nuevo documento
        $insert = $db->prepare("INSERT INTO documentos_drive (materia_id, archivo_id, nombre, tipo, enlace, detectado_en, notificado)
                                VALUES (:mid, :aid, :nom, :tip, :enl, NOW(), FALSE)");
        $insert->execute([
            ':mid' => $materia['id'],
            ':aid' => $archivoId,
            ':nom' => $nombre,
            ':tip' => $tipo,
            ':enl' => $enlace,
        ]);

        $totalNuevos++;
        logMsg("  Nuevo documento: $nombre ($tipo) - {$materia['nombre']}");

        // Notificar si fue creado en los últimos 3 días
        $creadoTimestamp = strtotime($creadoEn);
        $limite = strtotime('-3 days');
        if ($creadoTimestamp >= $limite) {
            if (enviarNotificacion($db, $telegramToken, $materia['nombre'], $nombre, $tipo, $enlace)) {
                $db->prepare("UPDATE documentos_drive SET notificado = TRUE WHERE materia_id = :mid AND archivo_id = :aid")
                   ->execute([':mid' => $materia['id'], ':aid' => $archivoId]);
                $totalNotificados++;
                logMsg("  Notificación enviada para: $nombre");
            } else {
                logMsg("  Fallo notificación para: $nombre");
            }
        } else {
            // Archivo viejo, marcar como notificado sin enviar
            $db->prepare("UPDATE documentos_drive SET notificado = TRUE WHERE materia_id = :mid AND archivo_id = :aid")
               ->execute([':mid' => $materia['id'], ':aid' => $archivoId]);
            logMsg("  Omitido (archivo viejo): $nombre ({$creadoEn})");
        }
    }
}

logMsg("=== FIN ESCANEO: $totalNuevos nuevos, $totalNotificados notificados ===");

ob_end_clean();
header('Content-Type: text/plain');
echo "OK: $totalNuevos nuevos, $totalNotificados notificados.";
exit;

// ═══════════════════════════════════════════════════
// FUNCIONES
// ═══════════════════════════════════════════════════

function obtenerAccessToken($clientId, $clientSecret, $refreshToken) {
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token',
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        logMsg("Error refresh token: HTTP $httpCode - $response");
        return null;
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

function extraerFolderId($url) {
    $patterns = [
        '/\/drive\/folders\/([a-zA-Z0-9_-]+)/',
        '/\/open\?id=([a-zA-Z0-9_-]+)/',
        '/\/file\/d\/([a-zA-Z0-9_-]+)/',
        '/[?&]id=([a-zA-Z0-9_-]+)/',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function listarArchivosRecursivo($accessToken, $folderId, $maxDepth = -1) {
    $allFiles = [];

    $queue = [['id' => $folderId, 'depth' => 0]];
    $visited = [];

    while (!empty($queue)) {
        $item = array_shift($queue);
        $currentFolder = $item['id'];
        $depth = $item['depth'];

        if (in_array($currentFolder, $visited)) continue;
        $visited[] = $currentFolder;

        $pageToken = null;
        do {
            $params = [
                'q' => "'$currentFolder' in parents and trashed=false",
                'fields' => 'files(id,name,mimeType,createdTime),nextPageToken',
                'pageSize' => 100,
            ];
            if ($pageToken) $params['pageToken'] = $pageToken;

            $url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query($params);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            if ($httpCode !== 200) {
                logMsg("Error Drive API folder $currentFolder: HTTP $httpCode - " . ($response ?: $error));
                return false;
            }

            $data = json_decode($response, true);

            if (isset($data['files'])) {
                foreach ($data['files'] as $file) {
                    if ($file['mimeType'] === 'application/vnd.google-apps.folder') {
                        if ($maxDepth === -1 || $depth < $maxDepth) {
                            $queue[] = ['id' => $file['id'], 'depth' => $depth + 1];
                        }
                    } elseif (strpos($file['mimeType'], 'application/pdf') === 0 ||
                              $file['mimeType'] === 'application/msword' ||
                              $file['mimeType'] === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                        $file['parentFolder'] = $currentFolder;
                        $allFiles[] = $file;
                    }
                }
            }

            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);
    }

    return $allFiles;
}

function obtenerTipoDocumento($mimeType) {
    $map = [
        'application/pdf' => 'PDF',
        'application/msword' => 'DOC',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
    ];
    return $map[$mimeType] ?? 'OTRO';
}

function enviarNotificacion($db, $telegramToken, $materiaNombre, $docNombre, $tipo, $enlace) {
    $icono = ($tipo === 'PDF') ? '📄' : '📝';
    $mensaje = "📁 ACTIVIDADES CARGADAS EN DRIVE 📁\n\n";
    $mensaje .= "📘 Materia: {$materiaNombre}\n";
    $mensaje .= "🔗 [Abrir en Drive]({$enlace})\n\n";
    $mensaje .= "💡 Revisa el material disponible";

    $stmt = $db->query("SELECT chat_id FROM suscriptores");
    $suscriptores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $exitos = 0;
    foreach ($suscriptores as $sub) {
        $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
        $data = [
            'chat_id' => $sub['chat_id'],
            'text' => $mensaje,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => false,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $raw = curl_exec($ch);
        $res = json_decode($raw, true);
        $err = curl_error($ch);
        curl_close($ch);

        if ($res && isset($res['ok']) && $res['ok']) {
            $exitos++;
        } else {
            $errMsg = $res['description'] ?? $err ?? 'Error desconocido';
            logMsg("Fallo Telegram a {$sub['chat_id']}: $errMsg");
        }
    }

    return $exitos > 0;
}
