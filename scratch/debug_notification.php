<?php
require_once 'config/database.php';
try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("SELECT id, titulo, fecha_entrega, estado, (fecha_entrega - CURRENT_DATE) as dias FROM tareas WHERE id = 53");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
