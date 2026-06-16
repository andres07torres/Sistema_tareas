<?php
require_once 'config/database.php';

$env = parse_ini_file('.env');
$token = $env['TELEGRAM_TOKEN'];

// Test group
$chatId = -5104094446;
$url = "https://api.telegram.org/bot{$token}/sendMessage";
$data = [
    'chat_id' => $chatId,
    'text' => "🧪 *Prueba* - Mensaje de prueba del bot.",
    'parse_mode' => 'Markdown',
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$raw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP: $httpCode\n";
echo "Response: $raw\n";
