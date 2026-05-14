<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

$date = '2026-05-14';
echo "Probando con fecha: $date\n";

$stmt = $db->prepare("INSERT INTO control_envios (fecha) VALUES (?) ON CONFLICT (fecha) DO NOTHING");
$stmt->execute([$date]);

$count = $stmt->rowCount();
echo "Filas afectadas: $count\n";

if ($count === 0) {
    echo "BLOQUEADO (Correcto)\n";
} else {
    echo "PASÓ (Error si ya existía)\n";
}
