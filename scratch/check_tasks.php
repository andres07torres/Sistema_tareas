<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

echo "=== TAREAS CON FECHA ENTREGA CERCA 22-24 Jun ===\n";
$stmt = $db->query("SELECT id, titulo, materia, fecha_entrega, estado FROM tareas WHERE estado = 'pendiente' AND fecha_entrega BETWEEN '2026-06-20' AND '2026-06-27' ORDER BY fecha_entrega");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n=== TAREAS RECIENTES CERRADAS ===\n";
$stmt = $db->query("SELECT id, titulo, materia, fecha_entrega, estado FROM tareas WHERE estado = 'inactivo' AND fecha_entrega BETWEEN '2026-06-20' AND '2026-06-27' ORDER BY fecha_entrega");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n=== ULTIMOS control_envios ===\n";
$stmt = $db->query("SELECT * FROM control_envios ORDER BY fecha DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
