<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

echo "=== NOTIFICADOR LOG ===\n";
$log = @file_get_contents(__DIR__ . '/../logs/notificador.log');
echo $log ? $log : "No existe o vacio\n";

echo "\n=== MOTIVADOR LOG ===\n";
$log = @file_get_contents(__DIR__ . '/../logs/motivador.log');
echo $log ? $log : "No existe o vacio\n";

echo "\n=== Todos los suscriptores ===\n";
$stmt = $db->query("SELECT * FROM suscriptores");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n=== control_envios (22-24 Jun) ===\n";
$stmt = $db->query("SELECT * FROM control_envios WHERE fecha >= '2026-06-22' ORDER BY fecha");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n=== Test envio manual a 8380935990 ===\n";
$token = '8718430332:AAExacSXmodiamvKHaKPHidNSqDAwAKDDE8';
$chatId = '8380935990';
$url = "https://api.telegram.org/bot{$token}/sendMessage";
$data = ['chat_id' => $chatId, 'text' => 'Test diagnostico - bot funciona OK', 'parse_mode' => 'Markdown'];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $httpCode\nRespuesta: $res\n";
