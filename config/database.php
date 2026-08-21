<?php
date_default_timezone_set('America/Guayaquil');
class Database {
    public function getConnection() {
        // Priorizar variables de entorno del sistema (Dokploy/Render)
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: null;
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: null;
        $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: null;
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: null;
        $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: null;

        // Fallback: leer .env solo si las variables de entorno no existen
        if (!$host) {
            $env_path = __DIR__ . '/../.env';
            if (file_exists($env_path)) {
                $env = parse_ini_file($env_path);
                $host = $env['DB_HOST'];
                $port = $env['DB_PORT'];
                $dbname = $env['DB_NAME'];
                $user = $env['DB_USER'];
                $password = $env['DB_PASSWORD'];
            }
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