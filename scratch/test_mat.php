<?php 
require "config/database.php"; 
$db = (new Database())->getConnection(); 
$stmt = $db->query("SELECT nombre FROM materias"); 
print_r($stmt->fetchAll(PDO::FETCH_COLUMN)); 
?>
