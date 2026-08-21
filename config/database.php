<?php
date_default_timezone_set('America/Guayaquil');
class Database {
    public function getConnection() {
        $host = 'db';
        $port = 5432;
        $dbname = 'postgres';
        $user = 'postgres';
        $password = 'pbxybjcvlotd9ffllpgibqk9pzyo3umq';

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