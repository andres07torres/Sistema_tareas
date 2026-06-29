<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

echo "=== COLUMNAS de documentos_drive ===\n";
$stmt = $db->query("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'documentos_drive' ORDER BY ordinal_position");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
