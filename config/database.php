<?php
date_default_timezone_set('America/Guayaquil');
class Database {
    public function getConnection() {
        $host = 'sistema-tareas-bot-supabase-437ae8-179-49-57-189.sslip.io';
        $port = '6543';
        $dbname = 'postgres';
        $user = 'postgres';
        $password = 'pbxybjcvlotd9ffllpgibqk9pzyo3umq';

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