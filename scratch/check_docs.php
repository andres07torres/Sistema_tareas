<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT d.id, d.nombre, d.notificado, to_char(d.detectado_en, 'YYYY-MM-DD HH24:MI') as detectado, m.nombre AS materia FROM documentos_drive d JOIN materias m ON m.id = d.materia_id ORDER BY d.detectado_en DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total documentos: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "id={$r['id']} materia={$r['materia']} nombre={$r['nombre']} notificado={$r['notificado']} detectado={$r['detectado']}\n";
}
