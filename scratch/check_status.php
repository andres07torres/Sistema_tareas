<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

try {
    $stmt = $db->query("SELECT to_regclass('documentos_drive')");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "documentos_drive table exists: " . ($result['to_regclass'] ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "documentos_drive table error: " . $e->getMessage() . "\n";
}

$stmt = $db->query("SELECT id, nombre, drive_link FROM materias WHERE drive_link IS NOT NULL AND drive_link != ''");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Materias with drive_link: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "  - {$r['nombre']}: {$r['drive_link']}\n";
}
