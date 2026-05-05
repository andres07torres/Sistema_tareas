<?php
// Script para simular una petición de Telegram a webhook.php

// 1. Definir los datos de la "petición"
$datos = [
    "update_id" => 123456,
    "message" => [
        "message_id" => 1,
        "from" => ["id" => 8380935990, "first_name" => "Andres"],
        "chat" => ["id" => 8380935990, "type" => "private"],
        "date" => time(),
        "text" => "/tareas"
    ]
];

$json = json_encode($datos);

// 2. Simular el entorno de servidor para que webhook.php lo lea
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// 3. Incluir el webhook capturando la salida
echo "--- Iniciando Simulación de /tareas ---\n";

// Capturamos el buffer para que el 'die' o los 'echo' no rompan el script
ob_start();

// Mock de php://input para file_get_contents
// (En PHP CLI no se puede sobreescribir php://input fácilmente, 
// así que modificaremos temporalmente webhook.php para una prueba controlada o usaremos curl local si hay server)

echo "Probando ejecución directa...\n";
try {
    // Definimos las variables que webhook.php espera del .env
    require_once __DIR__ . '/../config/database.php';
    $env_path = __DIR__ . '/../.env';
    if (file_exists($env_path)) {
        $env = parse_ini_file($env_path);
        foreach($env as $k => $v) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }

    // Como webhook.php usa file_get_contents("php://input"), 
    // la forma más limpia de probarlo en local es vía CURL si levantamos el server
    // Pero primero probemos si la base de datos conecta:
    $db = (new Database())->getConnection();
    echo "✅ Conexión a Base de Datos: OK\n";
    
    $query = "SELECT COUNT(*) FROM tareas WHERE estado = 'pendiente'";
    $count = $db->query($query)->fetchColumn();
    echo "📊 Tareas pendientes encontradas: $count\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "--- Fin de la prueba ---\n";
