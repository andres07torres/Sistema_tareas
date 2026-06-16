<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT * FROM suscriptores");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
