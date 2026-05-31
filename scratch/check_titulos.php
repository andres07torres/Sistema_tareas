<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = (new Database())->getConnection();
    echo "=== Tareas pendientes ===\n";
    $stmt = $db->query("SELECT id, materia, titulo, tipo, fecha_entrega FROM tareas WHERE estado = 'pendiente' ORDER BY materia, fecha_entrega");
    while ($t = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "[{$t['id']}] {$t['materia']} | '{$t['titulo']}' | {$t['tipo']} | {$t['fecha_entrega']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
