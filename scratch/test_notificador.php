<?php
$_GET['token'] = 'ClaveUnemi123';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'CLI';

ob_start();
require __DIR__ . '/../public/notificador.php';
$output = ob_get_clean();
echo "Output: $output\n";

// Check the log after execution
$log = @file_get_contents(__DIR__ . '/../logs/notificador.log');
echo "\n--- ULTIMAS LINEAS DEL LOG ---\n";
$lines = explode("\n", trim($log));
echo implode("\n", array_slice($lines, -5)) . "\n";
