<?php
require_once 'config/database.php';
try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("SELECT * FROM tareas LIMIT 1");
    $columns = array_keys($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
    echo "Columnas: " . implode(", ", $columns) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
