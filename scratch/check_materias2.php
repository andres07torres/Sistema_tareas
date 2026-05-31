<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = (new Database())->getConnection();
    echo "=== Materias en tabla materias ===\n";
    $stmt = $db->query("SELECT DISTINCT nombre FROM materias ORDER BY nombre");
    while ($m = $stmt->fetchColumn()) {
        echo "'$m'\n";
    }
    echo "\n=== Materias en tabla tareas ===\n";
    $stmt = $db->query("SELECT DISTINCT materia FROM tareas ORDER BY materia");
    while ($m = $stmt->fetchColumn()) {
        echo "'$m'\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
