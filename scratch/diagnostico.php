<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

echo "=== FECHA ACTUAL DB ===\n";
$stmt = $db->query("SELECT CURRENT_DATE, CURRENT_TIMESTAMP");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n=== CONTROL ENVIOS (ultimos 10) ===\n";
$stmt = $db->query("SELECT * FROM control_envios ORDER BY fecha DESC LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== TAREAS PENDIENTES (proximos 7 dias) ===\n";
$stmt = $db->query("SELECT titulo, materia, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7 ORDER BY fecha_entrega ASC");
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total: " . count($tareas) . "\n";
foreach ($tareas as $t) {
    echo json_encode($t, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== SUSCRIPTORES ===\n";
$stmt = $db->query("SELECT chat_id, nombre FROM suscriptores");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== CRON JOBS ===\n";
$stmt = $db->query("SELECT jobid, jobname, schedule, active, last_run FROM cron.job ORDER BY jobid");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}
