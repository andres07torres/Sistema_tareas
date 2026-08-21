<?php
date_default_timezone_set('America/Guayaquil');
class Database {
    public function getConnection() {
        // Leer archivo .env directamente (tiene los valores correctos de Supabase)
        $env_path = __DIR__ . '/../.env';
        if (file_exists($env_path)) {
            $env = parse_ini_file($env_path);
            $host = $env['DB_HOST'];
            $port = $env['DB_PORT'];
            $dbname = $env['DB_NAME'];
            $user = $env['DB_USER'];
            $password = $env['DB_PASSWORD'];
        } else {
            // En Dokploy/Producción: leer variables del sistema
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'sistema-tareas-bot-supabase-437ae8-179-49-57-189.sslip.io';
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '6543';
            $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'postgres';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'postgres';
            $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'pbxybjcvlotd9ffllpgibqk9pzyo3umq';
        }

        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $conn = new PDO($dsn, $user, $password);
            // Configurar PDO para que lance excepciones en caso de error
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
}
?>