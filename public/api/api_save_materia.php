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
$driveLink = isset($data['drive_link']) ? trim($data['drive_link']) : null;
$driveLink = $driveLink === '' ? null : $driveLink;
if ($driveLink !== null && !preg_match('/^https?:\/\//i', $driveLink)) {
    $driveLink = 'https://' . $driveLink;
}
$id = isset($data['id']) && $data['id'] !== '' ? (int)$data['id'] : null;

try {
    if ($id) {
        $stmt = $db->prepare("UPDATE materias SET nombre = :nombre, drive_link = :drive_link WHERE id = :id");
        $stmt->execute([':nombre' => $nombre, ':drive_link' => $driveLink, ':id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO materias (nombre, drive_link) VALUES (:nombre, :drive_link)");
        $stmt->execute([':nombre' => $nombre, ':drive_link' => $driveLink]);
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar la materia. Quizás el nombre ya exista.']);
}
