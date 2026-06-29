<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// Get SISTEMAS DISTRIBUIDOS folder ID
$stmt = $db->query("SELECT id, nombre, drive_link FROM materias WHERE nombre = 'SISTEMAS DISTRIBUIDOS'");
$materia = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Materia: " . json_encode($materia, JSON_UNESCAPED_UNICODE) . "\n";

// Get access token
$env = parse_ini_file(__DIR__ . '/../.env');
$clientId = $env['GOOGLE_CLIENT_ID'];
$clientSecret = $env['GOOGLE_CLIENT_SECRET'];
$refreshToken = $env['GOOGLE_REFRESH_TOKEN'];

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
$res = curl_exec($ch);
$tokenData = json_decode($res, true);
$accessToken = $tokenData['access_token'] ?? null;
curl_close($ch);

if (!$accessToken) {
    die("No access token\n");
}
echo "Access token obtenido\n";

// Extract folder ID
$folderId = null;
$url = $materia['drive_link'];
$patterns = ['/\/drive\/folders\/([a-zA-Z0-9_-]+)/', '/\/open\?id=([a-zA-Z0-9_-]+)/'];
foreach ($patterns as $p) {
    if (preg_match($p, $url, $m)) { $folderId = $m[1]; break; }
}
echo "Folder ID: $folderId\n\n";

// List files recursively
function listFiles($token, $folderId, $depth = 0) {
    $pageToken = null;
    $all = [];
    do {
        $params = [
            'q' => "'$folderId' in parents and trashed=false",
            'fields' => 'files(id,name,mimeType,createdTime),nextPageToken',
            'pageSize' => 100,
        ];
        if ($pageToken) $params['pageToken'] = $pageToken;
        
        $url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query($params);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);
        
        foreach ($data['files'] ?? [] as $f) {
            $prefix = str_repeat("  ", $depth);
            $isFolder = $f['mimeType'] === 'application/vnd.google-apps.folder';
            $type = $isFolder ? "[DIR]" : "[" . $f['mimeType'] . "]";
            echo "$prefix$type {$f['name']} ({$f['id']})\n";
            
            if ($isFolder) {
                $sub = listFiles($token, $f['id'], $depth + 1);
                $all = array_merge($all, $sub);
            } else {
                $f['_depth'] = $depth;
                $all[] = $f;
            }
        }
        
        $pageToken = $data['nextPageToken'] ?? null;
    } while ($pageToken);
    return $all;
}

echo "=== ARCHIVOS EN SISTEMAS DISTRIBUIDOS ===\n";
$allFiles = listFiles($accessToken, $folderId);

echo "\n=== SOLO PDF/WORD ===\n";
foreach ($allFiles as $f) {
    $mime = $f['mimeType'];
    if (strpos($mime, 'pdf') !== false || $mime === 'application/msword' || $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        echo "- {$f['name']} (depth: {$f['_depth']})\n";
    }
}

echo "\n=== DOCUMENTOS EN DB ===\n";
$stmt = $db->query("SELECT archivo_id, nombre, tipo, notificado FROM documentos_drive WHERE materia_id = {$materia['id']}");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row) . "\n";
}
