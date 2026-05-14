<?php
$logFile = __DIR__ . '/../logs/notificador.log';

if (file_exists($logFile)) {
    echo "--- Últimas 50 líneas del log ---\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo implode("", $lastLines);
} else {
    echo "El archivo de log no existe en: " . $logFile;
}
