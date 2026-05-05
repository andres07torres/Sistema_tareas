<?php
require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Añadir la columna 'tipo' (por defecto 'tarea')
    $sql = "ALTER TABLE tareas ADD COLUMN IF NOT EXISTS tipo VARCHAR(20) DEFAULT 'tarea'";
    $db->exec($sql);
    
    echo "Columna 'tipo' añadida con éxito.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
