<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
ob_start();
require __DIR__ . '/../public/api/api_mantenimiento.php';
$output = ob_get_clean();
echo $output . "\n";
