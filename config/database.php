<?php
date_default_timezone_set('America/Guayaquil');
class Database {
    public function getConnection() {
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $port = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 5432);
        $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'postgres';
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'postgres';
        $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $conn = new PDO($dsn, $user, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
}
?>