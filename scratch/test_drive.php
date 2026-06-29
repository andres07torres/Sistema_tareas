<?php
$_GET['token'] = 'ClaveUnemi123';
$_SERVER['REMOTE_ADDR'] = 'CLI';
$_SERVER['HTTP_USER_AGENT'] = 'CLI';

ob_start();
require __DIR__ . '/../public/drive_notificador.php';
$output = ob_get_clean();
echo "Output: $output\n";
