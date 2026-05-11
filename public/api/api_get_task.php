<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT * FROM tareas WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
    $tarea = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tarea) {
        echo json_encode(['success' => true, 'data' => $tarea]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Tarea no encontrada']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
