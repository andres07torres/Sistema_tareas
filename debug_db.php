<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

echo "--- Estructura de control_envios ---\n";
echo "--- Restricciones de control_envios ---\n";
try {
    $stmt = $db->query("SELECT conname, contype FROM pg_constraint WHERE conrelid = 'control_envios'::regclass");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['conname']} ({$row['contype']})\n";
    }
} catch (Exception $e) {
    echo "Error restricciones: " . $e->getMessage() . "\n";
}

echo "\n--- Contenido de control_envios (últimos 5) ---\n";
try {
    $stmt = $db->query("SELECT * FROM control_envios ORDER BY fecha DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error contenido: " . $e->getMessage() . "\n";
}
