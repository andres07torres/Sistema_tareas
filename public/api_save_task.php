<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Limpiar descripción y convertir a NULL si está vacía
    $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : null;
    if ($descripcion === "" || strtoupper($descripcion) === "EMPTY") {
        $descripcion = null;
    }

    $query = "UPDATE tareas SET 
                titulo = :titulo, 
                descripcion = :descripcion, 
                fecha_entrega = :fecha_entrega, 
                fecha_apertura = :fecha_apertura, 
                materia = :materia, 
                tipo = :tipo 
              WHERE id = :id";
              
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        ':titulo' => trim($data['titulo']),
        ':descripcion' => $descripcion,
        ':fecha_entrega' => trim($data['fecha_entrega']),
        ':fecha_apertura' => isset($data['fecha_apertura']) ? trim($data['fecha_apertura']) : null,
        ':materia' => trim($data['materia']),
        ':tipo' => trim($data['tipo']),
        ':id' => $data['id']
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Tarea actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo actualizar la tarea']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
