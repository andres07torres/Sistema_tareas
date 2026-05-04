<?php
require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Añadir la columna 'materia' a la tabla 'tareas'
    $sql = "ALTER TABLE tareas ADD COLUMN IF NOT EXISTS materia VARCHAR(100)";
    $db->exec($sql);
    
    echo "Columna 'materia' añadida con éxito (o ya existía).\n";
} catch (Exception $e) {
    echo "Error al modificar la tabla: " . $e->getMessage() . "\n";
}
?>
