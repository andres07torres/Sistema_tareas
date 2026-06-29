<?php
date_default_timezone_set('America/Guayaquil');
class Database {
    public function getConnection() {
        // Lógica "inteligente": Lee .env localmente o variables de entorno en la nube
        $env_path = __DIR__ . '/../.env';
        if (file_exists($env_path)) {
            $env = parse_ini_file($env_path);
            $host = $env['DB_HOST'];
            $port = $env['DB_PORT'];
            $dbname = $env['DB_NAME'];
            $user = $env['DB_USER'];
            $password = $env['DB_PASSWORD'];
        } else {
            // En Render, $_ENV es la forma más fiable de obtener las variables
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT');
            $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER');
            $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
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