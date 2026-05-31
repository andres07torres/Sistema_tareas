<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$db = (new Database())->getConnection();

if (!isset($_GET['materia']) || trim($_GET['materia']) === '') {
    echo json_encode(['success' => false, 'error' => 'Nombre de materia requerido']);
    exit;
}

$materia = trim($_GET['materia']);
$stmt = $db->prepare("SELECT drive_link FROM materias WHERE nombre = ?");
$stmt->execute([$materia]);
$link = $stmt->fetchColumn();

if ($link) {
    echo json_encode(['success' => true, 'drive_link' => $link]);
} else {
    echo json_encode(['success' => false, 'error' => 'No hay enlace Drive para esta materia.']);
}
