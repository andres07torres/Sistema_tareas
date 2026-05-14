<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

echo "--- Lista de Suscriptores ---\n";
$stmt = $db->query("SELECT * FROM suscriptores");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
