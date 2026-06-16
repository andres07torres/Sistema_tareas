<?php
$isCLI = (php_sapi_name() === 'cli');

if ($isCLI) {
    echo "\n╔══════════════════════════════════════════════════╗\n";
    echo "║   CONFIGURACIÓN GOOGLE DRIVE OAUTH 2.0         ║\n";
    echo "╚══════════════════════════════════════════════════╝\n\n";
}

$envPath = __DIR__ . '/.env';
$credentialsPath = __DIR__ . '/credentials.json';

// ─── Cargar .env ───
$envVars = [];
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $envVars[trim($name)] = trim($value);
        }
    }
}

// ─── Leer credentials.json ───
$clientId = $envVars['GOOGLE_CLIENT_ID'] ?? '';
$clientSecret = $envVars['GOOGLE_CLIENT_SECRET'] ?? '';

if (file_exists($credentialsPath)) {
    $json = json_decode(file_get_contents($credentialsPath), true);
    if ($json && isset($json['web']['client_id'])) {
        $clientId = $json['web']['client_id'];
        $clientSecret = $json['web']['client_secret'];
        $redirectUris = $json['web']['redirect_uris'] ?? [];
        if ($isCLI) echo "✓ credentials.json cargado.\n";
    } elseif ($json && isset($json['installed']['client_id'])) {
        $clientId = $json['installed']['client_id'];
        $clientSecret = $json['installed']['client_secret'];
        $redirectUris = $json['installed']['redirect_uris'] ?? [];
        if ($isCLI) echo "✓ credentials.json cargado.\n";
    } else {
        if ($isCLI) echo "✗ credentials.json con formato incorrecto.\n";
        $redirectUris = [];
    }
} else {
    $redirectUris = [];
}

if (empty($clientId) || empty($clientSecret)) {
    die("Error: No se encontraron credenciales. Coloca credentials.json en la raíz.\n");
}

// ─── Guardar en .env ───
actualizarEnv($envPath, 'GOOGLE_CLIENT_ID', $clientId);
actualizarEnv($envPath, 'GOOGLE_CLIENT_SECRET', $clientSecret);
if ($isCLI) echo "✓ Credenciales guardadas en .env\n";

// ─── Verificar refresh token existente ───
$refreshToken = $envVars['GOOGLE_REFRESH_TOKEN'] ?? '';
if (!empty($refreshToken)) {
    if ($isCLI) {
        echo "\n✓ Ya hay GOOGLE_REFRESH_TOKEN en .env\n";
        echo "  ¿Renovar? (s/N): ";
        $resp = strtolower(trim(fgets(STDIN)));
        if ($resp !== 's') {
            echo "\nConfiguración completada.\n";
            echo "\nEjecuta este SQL en tu base de datos:\n\n" . mostrarSQL();
            exit;
        }
    } else {
        echo "Ya hay un refresh token configurado.\n";
        exit;
    }
}

// ─── Elegir redirect URI ───
$redirectUri = 'http://localhost';
if (!empty($redirectUris)) {
    $redirectUri = $redirectUris[0];
}

// Buscar puerto libre
$port = 80;
if (preg_match('/:(\d+)/', $redirectUri, $m)) {
    $port = (int)$m[1];
}

// ─── Iniciar servidor local para capturar callback ───
$tmpDir = sys_get_temp_dir();
$callbackFile = $tmpDir . '/oauth_cb_' . time() . '.txt';
$routerFile = $tmpDir . '/oauth_rtr_' . time() . '.php';

file_put_contents($routerFile, '<?php
$cbFile = \'' . addslashes($callbackFile) . '\';
if (isset($_GET["code"])) {
    file_put_contents($cbFile, $_GET["code"]);
    echo "<h2>✓ Autorización recibida</h2><p>Puedes cerrar esta pestaña.</p>";
} elseif (isset($_GET["error"])) {
    file_put_contents($cbFile, "ERROR:" . $_GET["error"]);
    echo "<h2>✗ Error: " . htmlspecialchars($_GET["error"]) . "</h2>";
} else {
    echo "<h2>Esperando autorización...</h2>";
}
');

$serverProc = null;
$usedPort = 0;

if ($isCLI) {
    // Intentar varios puertos
    for ($tryPort = $port; $tryPort < $port + 10; $tryPort++) {
        $test = @fsockopen('127.0.0.1', $tryPort, $en, $es, 0.3);
        if ($test) {
            fclose($test);
            continue;
        }
        $cmd = sprintf('php -S 127.0.0.1:%d "%s"', $tryPort, $routerFile);
        $serverProc = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $serverPipes);
        if ($serverProc) {
            $usedPort = $tryPort;
            usleep(300000);
            break;
        }
    }

    if (!$serverProc) {
        echo "\n⚠ No se pudo iniciar servidor local. Usando modo manual.\n";
        $redirectUri = 'urn:ietf:wg:oauth:2.0:oob';
        $usedPort = 0;
    } else {
        $redirectUri = "http://localhost:$usedPort/";
        echo "\n✓ Servidor local iniciado en $redirectUri\n";
    }
}

// ─── URL de autorización ───
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/drive.readonly',
    'access_type' => 'offline',
    'prompt' => 'consent',
]);

if ($isCLI) {
    echo "\n╔══════════════════════════════════════════════════╗\n";
    echo "║  AUTORIZACIÓN                                   ║\n";
    echo "╚══════════════════════════════════════════════════╝\n\n";

    if ($usedPort > 0) {
        // Flujo automático: iniciar navegador
        echo "Abriendo navegador para autorizar...\n\n";
        echo "Si no se abre automáticamente, abre este enlace:\n";
        echo "  $authUrl\n\n";

        // Intentar abrir navegador
        if (PHP_OS_FAMILY === 'Windows') {
            exec("start \"\" \"$authUrl\" 2>NUL");
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            exec("open \"$authUrl\"");
        } else {
            exec("xdg-open \"$authUrl\" 2>/dev/null &");
        }

        echo "Esperando autorización en el navegador...\n";

        // Esperar callback (hasta 5 minutos)
        $timeout = 300;
        $start = time();
        $authCode = '';
        while (time() - $start < $timeout) {
            if (file_exists($callbackFile)) {
                $authCode = trim(file_get_contents($callbackFile));
                break;
            }
            usleep(500000);
        }

        if (empty($authCode)) {
            echo "\n✗ Tiempo de espera agotado. No se recibió autorización.\n";
            limpiarTemp($serverProc, $routerFile, $callbackFile);
            exit(1);
        }

        if (strpos($authCode, 'ERROR:') === 0) {
            echo "\n✗ Error de autorización: " . substr($authCode, 6) . "\n";
            limpiarTemp($serverProc, $routerFile, $callbackFile);
            exit(1);
        }

        echo "✓ Código de autorización recibido.\n";

    } else {
        // Flujo manual (fallback)
        echo "IMPORTANTE: Asegúrate de haber agregado 'http://localhost' en\n";
        echo "Google Cloud Console → Credenciales → URIs de redireccionamiento\n\n";
        echo "1. Abre este enlace en tu navegador:\n";
        echo "   $authUrl\n\n";
        echo "2. Inicia sesión (@unemi.edu.ec) y acepta los permisos\n";
        echo "3. Serás redirigido a una página local (con el código en la URL)\n";
        echo "4. Copia el código que aparece después de ?code= en la URL\n";
        echo "5. Pégalo aquí:\n\n";
        echo "   Código de autorización: ";
        $authCode = trim(fgets(STDIN));
    }
} else {
    // Modo web
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"><title>Autorizar Google Drive</title>
    <style>
        body{font-family:sans-serif;max-width:600px;margin:2rem auto;padding:1rem;line-height:1.6}
        a{background:#1a73e8;color:#fff;padding:.7rem 1.5rem;border-radius:4px;text-decoration:none;display:inline-block;margin:.5rem 0}
        input{width:100%;padding:.5rem;margin:.5rem 0;box-sizing:border-box}
        .btn{background:#1a73e8;color:#fff;border:none;padding:.7rem 1.5rem;border-radius:4px;cursor:pointer}
    </style>
    </head>
    <body>
    <h2>Autorizar Google Drive</h2>
    <p><a href="<?php echo htmlspecialchars($authUrl); ?>" target="_blank">🔗 Autorizar con Google</a></p>
    <p>Después de autorizar, serás redirigido a una página local.<br>
    Copia el código de la URL y pégalo aquí:</p>
    <form method="post">
        <input type="text" name="auth_code" placeholder="Código de autorización">
        <button type="submit" class="btn">Guardar Token</button>
    </form>
    </body>
    </html>
    <?php
    $authCode = $_POST['auth_code'] ?? '';
    if (empty($authCode)) exit;
}

if (empty($authCode)) {
    limpiarTemp($serverProc ?? null, $routerFile ?? null, $callbackFile ?? null);
    die("Error: No se proporcionó código de autorización.\n");
}

// ─── Intercambiar código por tokens ───
if ($isCLI) echo "\n↻ Intercambiando código por tokens...\n";

$result = httpPost('https://oauth2.googleapis.com/token', [
    'code' => $authCode,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code',
]);

$tokenData = json_decode($result['response'], true);
$httpCode = $result['httpCode'];
$error = $result['error'];

if ($httpCode !== 200 || !isset($tokenData['refresh_token'])) {
    echo "\n✗ Error al obtener tokens:\n";
    echo "  HTTP $httpCode: " . ($tokenData['error_description'] ?? $tokenData['error'] ?? $result['response']) . "\n";
    if ($error) echo "  Error: $error\n";
    limpiarTemp($serverProc ?? null, $routerFile ?? null, $callbackFile ?? null);
    exit(1);
}

$refreshToken = $tokenData['refresh_token'];
$accessToken = $tokenData['access_token'];

if ($isCLI) echo "✓ Tokens obtenidos correctamente.\n";

actualizarEnv($envPath, 'GOOGLE_REFRESH_TOKEN', $refreshToken);
if ($isCLI) echo "✓ GOOGLE_REFRESH_TOKEN guardado en .env\n";

// ─── Probar Drive API ───
if ($isCLI) echo "\n↻ Probando conexión a Google Drive API...\n";

$driveResult = httpGet('https://www.googleapis.com/drive/v3/files?pageSize=1&fields=files(id,name)', [
    'Authorization: Bearer ' . $accessToken,
]);
$driveHttpCode = $driveResult['httpCode'];

if ($driveHttpCode === 200) {
    if ($isCLI) echo "✓ Conexión exitosa a Google Drive API.\n";
} else {
    if ($isCLI) echo "⚠ Advertencia: No se pudo conectar a Drive API (HTTP $driveHttpCode).\n";
}

limpiarTemp($serverProc ?? null, $routerFile ?? null, $callbackFile ?? null);

if ($isCLI) {
    echo "\n╔══════════════════════════════════════════════════╗\n";
    echo "║  CONFIGURACIÓN COMPLETADA                       ║\n";
    echo "╚══════════════════════════════════════════════════╝\n\n";
    echo "Ejecuta este SQL en tu base de datos:\n\n";
    echo mostrarSQL();
    echo "\n";
}

// ═══════════════════════════════════════
// FUNCIONES
// ═══════════════════════════════════════

function actualizarEnv($envPath, $key, $value) {
    if (!file_exists($envPath)) {
        file_put_contents($envPath, "$key=$value\n");
        return;
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES);
    $found = false;
    $newLines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match("/^$key=/", $trimmed)) {
            $newLines[] = "$key=$value";
            $found = true;
        } else {
            $newLines[] = $line;
        }
    }
    if (!$found) {
        $newLines[] = "$key=$value";
    }
    file_put_contents($envPath, implode("\n", $newLines) . "\n");
}

function limpiarTemp($proc, $routerFile, $callbackFile) {
    if ($proc) {
        $status = proc_get_status($proc);
        if ($status['running']) {
            if (PHP_OS_FAMILY === 'Windows') {
                exec('taskkill /F /PID ' . $status['pid'] . ' 2>NUL');
            } else {
                exec('kill ' . $status['pid'] . ' 2>/dev/null');
            }
        }
        proc_close($proc);
    }
    if ($routerFile && file_exists($routerFile)) @unlink($routerFile);
    if ($callbackFile && file_exists($callbackFile)) @unlink($callbackFile);
}

function httpPost($url, $data) {
    $postData = http_build_query($data);

    if (PHP_OS_FAMILY === 'Windows') {
        $tmpFile = sys_get_temp_dir() . '/resp_' . time() . '.txt';
        $dataFile = sys_get_temp_dir() . '/data_' . time() . '.txt';
        $batFile = sys_get_temp_dir() . '/curl_' . time() . '.bat';
        file_put_contents($dataFile, http_build_query($data));
        $batContent = '@echo off' . "\n";
        $batContent .= 'curl.exe -s -S -w "%%{http_code}" -o "' . $tmpFile . '" -X POST -d "@' . $dataFile . '" "' . $url . '"' . "\n";
        file_put_contents($batFile, $batContent);

        $httpCode = trim(shell_exec('"' . $batFile . '" 2>NUL'));
        $httpCode = (int)$httpCode;
        $response = '';
        if (file_exists($tmpFile)) {
            $response = file_get_contents($tmpFile);
            @unlink($tmpFile);
        }
        @unlink($dataFile);
        @unlink($batFile);
        $error = $httpCode >= 400 ? ($response ?: 'Error HTTP') : '';
        return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
    }

    // Linux/Render: file_get_contents con openssl
    $postData = http_build_query($data);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData,
            'timeout' => 30,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $response = @file_get_contents($url, false, $ctx);
    $httpCode = 0;
    $hdrs = function_exists('http_get_last_response_headers') ? @http_get_last_response_headers() : @$http_response_header;
    if (isset($hdrs[0])) {
        preg_match('/\s(\d{3})\s/', $hdrs[0], $m);
        $httpCode = (int)$m[1];
    }
    $error = $response === false ? (error_get_last()['message'] ?? 'Error de conexión') : '';
    return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
}

function httpGet($url, $headers = []) {
    if (PHP_OS_FAMILY === 'Windows') {
        $tmpFile = sys_get_temp_dir() . '/resp_' . time() . '.txt';
        // Escribir el comando en un .bat para evitar problemas de escaping en CMD
        $batFile = sys_get_temp_dir() . '/curl_' . time() . '.bat';
        $batContent = '@echo off' . "\n";
        $batContent .= 'curl.exe -s -S -w "%%{http_code}" -o "' . $tmpFile . '"';
        foreach ($headers as $h) {
            $h = str_replace('"', '', $h);
            $batContent .= ' -H "' . $h . '"';
        }
        $batContent .= ' "' . str_replace('"', '', $url) . '"' . "\n";
        file_put_contents($batFile, $batContent);

        $httpCode = trim(shell_exec('"' . $batFile . '" 2>NUL'));
        $httpCode = (int)$httpCode;
        $response = '';
        if (file_exists($tmpFile)) {
            $response = file_get_contents($tmpFile);
            @unlink($tmpFile);
        }
        @unlink($batFile);
        $error = $httpCode >= 400 ? ($response ?: 'Error HTTP') : '';
        return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
    }

    // Linux/Render: file_get_contents con openssl
    $headerStr = implode("\r\n", $headers);
    $ctx = stream_context_create([
        'http' => ['method' => 'GET', 'header' => $headerStr, 'timeout' => 15, 'ignore_errors' => true],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $response = @file_get_contents($url, false, $ctx);
    $httpCode = 0;
    $hdrs = function_exists('http_get_last_response_headers') ? @http_get_last_response_headers() : @$http_response_header;
    if (isset($hdrs[0])) {
        preg_match('/\s(\d{3})\s/', $hdrs[0], $m);
        $httpCode = (int)$m[1];
    }
    $error = $response === false ? (error_get_last()['message'] ?? 'Error de conexión') : '';
    return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
}

function mostrarSQL() {
    return "CREATE TABLE IF NOT EXISTS documentos_drive (
    id SERIAL PRIMARY KEY,
    materia_id INTEGER NOT NULL REFERENCES materias(id) ON DELETE CASCADE,
    archivo_id VARCHAR(500) NOT NULL,
    nombre VARCHAR(500) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    enlace TEXT NOT NULL,
    detectado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notificado BOOLEAN DEFAULT FALSE,
    UNIQUE(materia_id, archivo_id)
);

CREATE INDEX IF NOT EXISTS idx_docs_notificado ON documentos_drive(notificado);
CREATE INDEX IF NOT EXISTS idx_docs_materia ON documentos_drive(materia_id);\n";
}
