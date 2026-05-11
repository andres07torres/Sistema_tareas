<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $nuevoEstado = $input['estado'] ?? null;

    if (!$id || !$nuevoEstado) {
        echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
        exit;
    }

    // Validar estado permitido
    $estadosPermitidos = ['pendiente', 'inactivo'];
    if (!in_array($nuevoEstado, $estadosPermitidos)) {
        echo json_encode(['success' => false, 'error' => 'Estado no válido']);
        exit;
    }

    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE tareas SET estado = :estado WHERE id = :id");
        $success = $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);

        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
