<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['nombre']) || trim($data['nombre']) === '') {
    echo json_encode(['success' => false, 'error' => 'El nombre de la materia es requerido']);
    exit;
}

$nombre = trim($data['nombre']);
$id = isset($data['id']) && $data['id'] !== '' ? (int)$data['id'] : null;

try {
    if ($id) {
        $stmt = $db->prepare("UPDATE materias SET nombre = :nombre WHERE id = :id");
        $stmt->execute([':nombre' => $nombre, ':id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO materias (nombre) VALUES (:nombre)");
        $stmt->execute([':nombre' => $nombre]);
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar la materia. Quizás el nombre ya exista.']);
}
