<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Use cron.reschedule to update existing job
$sql = "SELECT cron.schedule('drive-notificador', '*/5 * * * *', 'SELECT net.http_get(url:=''https://sistema-tareas-4g94.onrender.com/drive_notificador.php?token=ClaveUnemi123'')')";
echo "Running: $sql\n";
$stmt = $db->query($sql);
echo "Cron actualizado a cada 5 minutos.\n";

$stmt = $db->query("SELECT jobname, schedule, active FROM cron.job WHERE jobname = 'drive-notificador'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
