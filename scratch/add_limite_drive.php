<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    $db->exec("ALTER TABLE tareas ADD COLUMN IF NOT EXISTS limite_drive DATE DEFAULT NULL");
    echo "Columna 'limite_drive' agregada exitosamente a la tabla 'tareas'.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
