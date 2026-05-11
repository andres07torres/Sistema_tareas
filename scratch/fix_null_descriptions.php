<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "Conectado a la base de datos...\n";
    
    // 1. Convertir cadenas vacías a NULL
    $sql1 = "UPDATE tareas SET descripcion = NULL WHERE descripcion = ''";
    $count1 = $db->exec($sql1);
    echo "- Se actualizaron $count1 registros que tenían descripción vacía ('') a NULL.\n";
    
    // 2. Convertir el literal 'EMPTY' (caso insensible) a NULL
    $sql2 = "UPDATE tareas SET descripcion = NULL WHERE UPPER(descripcion) = 'EMPTY'";
    $count2 = $db->exec($sql2);
    echo "- Se actualizaron $count2 registros que tenían el texto 'EMPTY' a NULL.\n";
    
    echo "\n¡Limpieza completada con éxito!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
