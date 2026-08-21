<?php
date_default_timezone_set('America/Guayaquil');
class Database {
    private static $migrated = false;

    public function getConnection() {
        try {
            $dsn = "pgsql:host=sistema-tareas-bot-supabase-437ae8-179-49-57-189.sslip.io;port=6543;dbname=postgres";
            $conn = new PDO($dsn, 'postgres', 'pbxybjcvlotd9ffllpgibqk9pzyo3umq');
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
