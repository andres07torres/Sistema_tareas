<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

echo "=== DOCUMENTOS DRIVE ===\n";
$stmt = $db->query("SELECT d.*, m.nombre as materia_nombre FROM documentos_drive d JOIN materias m ON m.id = d.materia_id ORDER BY d.detectado_en DESC LIMIT 20");
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total: " . count($docs) . "\n";
foreach ($docs as $d) {
    print_r($d);
}

echo "\n=== MATERIAS ===\n";
$stmt = $db->query("SELECT id, nombre, drive_link FROM materias WHERE drive_link IS NOT NULL ORDER BY nombre");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
