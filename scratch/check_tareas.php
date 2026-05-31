<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = (new Database())->getConnection();
    echo "=== Tareas pendientes por materia ===\n";
    $stmt = $db->query("SELECT materia, COUNT(*) as total FROM tareas WHERE estado = 'pendiente' GROUP BY materia ORDER BY materia");
    while ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "'{$m['materia']}' -> {$m['total']} tareas pendientes\n";
    }
    echo "\n=== Tareas inactivas por materia ===\n";
    $stmt = $db->query("SELECT materia, COUNT(*) as total FROM tareas WHERE estado = 'inactivo' GROUP BY materia ORDER BY materia");
    while ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "'{$m['materia']}' -> {$m['total']} tareas inactivas\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
