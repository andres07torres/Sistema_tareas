<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

// Check if pg_cron extension exists
$stmt = $db->query("SELECT * FROM pg_extension WHERE extname = 'pg_cron'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "pg_cron installed: " . ($row ? 'YES' : 'NO') . "\n";

// Check if cron schema exists
$stmt = $db->query("SELECT schema_name FROM information_schema.schemata WHERE schema_name = 'cron'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "cron schema exists: " . ($row ? 'YES' : 'NO') . "\n";

// Check if net extension exists (for http_get)
$stmt = $db->query("SELECT * FROM pg_extension WHERE extname = 'pg_net'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "pg_net installed: " . ($row ? 'YES' : 'NO') . "\n";

// List existing cron jobs
try {
    $stmt = $db->query("SELECT * FROM cron.job");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Existing cron jobs: " . count($jobs) . "\n";
    foreach ($jobs as $j) {
        echo json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (Exception $e) {
    echo "Error listing jobs: " . $e->getMessage() . "\n";
}
