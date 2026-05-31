<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

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

    $id = isset($data['id']) && !empty($data['id']) ? $data['id'] : null;

    if ($id) {
        $query = "UPDATE tareas SET 
                    titulo = :titulo, 
                    descripcion = :descripcion, 
                    fecha_entrega = :fecha_entrega, 
                    fecha_apertura = :fecha_apertura, 
                    materia = :materia, 
                    tipo = :tipo,
                    limite_drive = :limite_drive
                  WHERE id = :id";
        $params = [
            ':titulo' => trim($data['titulo']),
            ':descripcion' => $descripcion,
            ':fecha_entrega' => trim($data['fecha_entrega']),
            ':fecha_apertura' => isset($data['fecha_apertura']) ? trim($data['fecha_apertura']) : null,
            ':materia' => trim($data['materia']),
            ':tipo' => trim($data['tipo']),
            ':limite_drive' => isset($data['limite_drive']) && $data['limite_drive'] !== '' ? trim($data['limite_drive']) : null,
            ':id' => $id
        ];
    } else {
        $query = "INSERT INTO tareas (titulo, descripcion, fecha_entrega, fecha_apertura, materia, tipo, limite_drive) 
                  VALUES (:titulo, :descripcion, :fecha_entrega, :fecha_apertura, :materia, :tipo, :limite_drive)";
        $params = [
            ':titulo' => trim($data['titulo']),
            ':descripcion' => $descripcion,
            ':fecha_entrega' => trim($data['fecha_entrega']),
            ':fecha_apertura' => isset($data['fecha_apertura']) ? trim($data['fecha_apertura']) : null,
            ':materia' => trim($data['materia']),
            ':tipo' => trim($data['tipo']),
            ':limite_drive' => isset($data['limite_drive']) && $data['limite_drive'] !== '' ? trim($data['limite_drive']) : null
        ];
    }
              
    $stmt = $db->prepare($query);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode(['success' => true, 'message' => $id ? 'Tarea actualizada correctamente' : 'Tarea creada correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo procesar la tarea']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
