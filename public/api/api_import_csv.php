<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
    echo json_encode(['success' => false, 'error' => 'No se recibió el archivo o hubo un error en la carga']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $file = $_FILES['csv_file']['tmp_name'];
    
    // Detectar delimitador
    $f = fopen($file, 'r');
    $firstLine = fgets($f);
    fclose($f);
    $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
    
    $handle = fopen($file, "r");
    $header = fgetcsv($handle, 1000, $delimiter, "\"", "\\"); // Saltar cabecera
    
    $count = 0;
    $errors = [];
    $lineNum = 1;

    $query = "INSERT INTO tareas (titulo, descripcion, fecha_entrega, estado, materia, tipo, fecha_apertura) 
              VALUES (:titulo, :descripcion, :fecha_entrega, :estado, :materia, :tipo, :fecha_apertura)";
    $stmt = $db->prepare($query);

    while (($data = fgetcsv($handle, 1000, $delimiter, "\"", "\\")) !== FALSE) {
        $lineNum++;
        if (empty(array_filter($data))) continue;

        if (count($data) >= 7) {
            try {
                $descripcion = trim($data[1]);
                if ($descripcion === "" || strtoupper($descripcion) === "EMPTY") {
                    $descripcion = null;
                }

                $stmt->execute([
                    ':titulo'         => trim($data[0]),
                    ':descripcion'    => $descripcion,
                    ':fecha_entrega'  => trim($data[2]),
                    ':estado'         => trim($data[3]) ?: 'pendiente',
                    ':materia'        => trim($data[4]),
                    ':tipo'           => trim($data[5]),
                    ':fecha_apertura' => trim($data[6])
                ]);
                $count++;
            } catch (Exception $e) {
                $errors[] = "Línea $lineNum: " . $e->getMessage();
            }
        } else {
            $errors[] = "Línea $lineNum: Columnas insuficientes.";
        }
    }
    fclose($handle);
    
    if (empty($errors)) {
        echo json_encode(['success' => true, 'message' => "¡Éxito! Se importaron $count tareas."]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => "Importación finalizada con " . count($errors) . " errores.",
            'details' => $errors,
            'count' => $count
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
