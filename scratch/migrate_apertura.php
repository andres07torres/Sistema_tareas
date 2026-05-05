<?php
require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Añadir la columna 'fecha_apertura' (por defecto la fecha actual si no se pone nada)
    $sql = "ALTER TABLE tareas ADD COLUMN IF NOT EXISTS fecha_apertura DATE DEFAULT CURRENT_DATE";
    $db->exec($sql);
    
    echo "Columna 'fecha_apertura' añadida con éxito.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
