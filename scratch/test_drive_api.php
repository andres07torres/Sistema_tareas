<?php
require_once 'config/database.php';

$env = parse_ini_file('.env');
$clientId = $env['GOOGLE_CLIENT_ID'];
$clientSecret = $env['GOOGLE_CLIENT_SECRET'];
$refreshToken = $env['GOOGLE_REFRESH_TOKEN'];

echo "Getting access token...\n";
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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Error refresh token: HTTP $httpCode - $response\n");
}

$data = json_decode($response, true);
$accessToken = $data['access_token'] ?? null;
echo "Access token obtained: " . substr($accessToken, 0, 30) . "...\n\n";

// Test Drive API - list files from first folder
$folderId = '18vzwd1rhT6MAS8l5mPXaP1HwHn18TVTP'; // SISTEMAS DISTRIBUIDOS
echo "Testing Drive API with folder: $folderId\n";

$url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
    'q' => "'$folderId' in parents and trashed=false",
    'fields' => 'files(id,name,mimeType,createdTime)',
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP $httpCode\n";
echo "Response: $response\n\n";

$data = json_decode($response, true);
if (isset($data['files'])) {
    echo "Files found: " . count($data['files']) . "\n";
    foreach ($data['files'] as $f) {
        echo "  - {$f['name']} ({$f['mimeType']})\n";
    }
} else {
    echo "No files or error.\n";
    print_r($data);
}
