<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de materia requerido']);
    exit;
}

$id = (int)$data['id'];

try {
    // Check if there are tasks with this materia
    // Since we don't have an exact materia_id in tareas (we use string name), we might just delete or warn.
    // To be safe, we fetch the materia name and check if it's used in tareas.
    $stmt = $db->prepare("SELECT nombre FROM materias WHERE id = ?");
    $stmt->execute([$id]);
    $materia = $stmt->fetchColumn();
    
    if ($materia) {
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM tareas WHERE materia = ?");
        $checkStmt->execute([$materia]);
        $count = $checkStmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'error' => 'No se puede eliminar la materia porque hay ' . $count . ' tarea(s) asociada(s) a ella.']);
            exit;
        }
        
        $delStmt = $db->prepare("DELETE FROM materias WHERE id = ?");
        $delStmt->execute([$id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Materia no encontrada.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
}
