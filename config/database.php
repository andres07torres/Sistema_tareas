<?php
date_default_timezone_set('America/Guayaquil');
class Database {
    private static $migrated = false;

    public function getConnection() {
        try {
            $host = trim(getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ''), '"');
            $port = (int) trim(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432'), '"');
            $dbname = trim(getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'postgres'), '"');
            $user = trim(getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'postgres'), '"');
            $password = trim(getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? ''), '"');

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $conn = new PDO($dsn, $user, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if (!self::$migrated) {
                self::$migrated = true;
                $this->runMigrations($conn);
            }

            return $conn;
        } catch(PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    private function runMigrations($conn) {
        $schemaFile = __DIR__ . '/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $conn->exec($sql);
        }
    }
}
?>
