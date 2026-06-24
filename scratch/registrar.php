<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->prepare("INSERT INTO suscriptores (chat_id, nombre, tipo_chat) VALUES (8380935990, 'Andres', 'private') ON CONFLICT (chat_id) DO UPDATE SET nombre = 'Andres'");
$stmt->execute();
echo "Registrado: " . $stmt->rowCount() . " filas.\n";
