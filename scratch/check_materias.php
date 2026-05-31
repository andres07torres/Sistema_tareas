<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("SELECT id, nombre, LENGTH(nombre) as len, drive_link FROM materias ORDER BY id");
    while ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cb = "materia|" . $m['nombre'];
        echo "ID: {$m['id']} | Nombre: '{$m['nombre']}' | Largo: {$m['len']} | callback_data bytes: " . strlen($cb) . " | Drive: " . ($m['drive_link'] ? 'SI' : 'NO') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
