<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$db = (new Database())->getConnection();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM materias WHERE id = ?");
    $stmt->execute([$id]);
    $materia = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($materia) {
        echo json_encode(['success' => true, 'data' => $materia]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Materia no encontrada']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
}
