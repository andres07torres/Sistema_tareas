<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$db->exec("ALTER TABLE documentos_drive ADD COLUMN IF NOT EXISTS modified_at timestamp");
echo "Columna modified_at agregada correctamente.\n";
