<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

echo "PHP Date: " . date('Y-m-d H:i:s') . "\n";
$stmt = $db->query("SELECT CURRENT_DATE as db_date, CURRENT_TIMESTAMP as db_ts");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "DB Date: " . $row['db_date'] . "\n";
echo "DB Timestamp: " . $row['db_ts'] . "\n";
