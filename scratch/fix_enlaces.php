<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Obtener el folder parent de Libreto.pdf desde Drive API
$env = parse_ini_file('.env');
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
$response = curl_exec($ch);
$data = json_decode($response, true);
$accessToken = $data['access_token'];

// Consultar el archivo para obtener su parent
$stmt = $db->query("SELECT id, archivo_id, materia_id FROM documentos_drive");
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($docs as $doc) {
    $url = "https://www.googleapis.com/drive/v3/files/{$doc['archivo_id']}?fields=parents";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $fileData = json_decode($res, true);

    if (isset($fileData['parents'][0])) {
        $parentId = $fileData['parents'][0];
        $folderLink = "https://drive.google.com/drive/folders/{$parentId}";
        $update = $db->prepare("UPDATE documentos_drive SET enlace = :enlace WHERE id = :id");
        $update->execute([':enlace' => $folderLink, ':id' => $doc['id']]);
        echo "Actualizado documento id={$doc['id']} -> folder {$parentId}\n";
    } else {
        echo "No se pudo obtener parent de id={$doc['id']}: " . json_encode($fileData) . "\n";
    }
}
