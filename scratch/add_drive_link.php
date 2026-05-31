<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    $db->exec("ALTER TABLE materias ADD COLUMN IF NOT EXISTS drive_link TEXT DEFAULT NULL");
    echo "Columna 'drive_link' agregada exitosamente a la tabla 'materias'.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
