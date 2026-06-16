<?php
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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpCode !== 200) { die("Error refresh: $httpCode - $response\n"); }
$data = json_decode($response, true);
$accessToken = $data['access_token'];
echo "Token OK\n";

$mimeFilter = "mimeType='application/pdf' or mimeType='application/msword' or mimeType='application/vnd.openxmlformats-officedocument.wordprocessingml.document'";
$url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
    'q' => "'18vzwd1rhT6MAS8l5mPXaP1HwHn18TVTP' in parents and ($mimeFilter) and trashed=false",
    'fields' => 'files(id,name,mimeType,createdTime)',
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP $httpCode\n";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT) . "\n";

// Also search recursively in all subfolders
echo "\n--- Searching recursively ---\n";
$url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
    'q' => "($mimeFilter) and trashed=false",
    'fields' => 'files(id,name,mimeType,parents,createdTime)',
]);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$data = json_decode($response, true);
echo "Total PDF/DOC files across whole Drive: " . count($data['files'] ?? []) . "\n";
foreach ($data['files'] ?? [] as $f) {
    echo "  - {$f['name']} (parent: {$f['parents'][0]})\n";
}
