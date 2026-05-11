<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
        exit;
    }

    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("DELETE FROM tareas WHERE id = :id");
        $success = $stmt->execute([':id' => $id]);

        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
