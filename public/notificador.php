<?php
// Forzar la lectura de variables de entorno en Render
$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');
$chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? getenv('TELEGRAM_CHAT_ID');
$token_seguridad = $_ENV['CRON_TOKEN'] ?? getenv('CRON_TOKEN');

// Si no hay variables de entorno, intenta cargar el .env local (solo para tu PC)
if (!$telegramToken && file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $telegramToken = $env['TELEGRAM_TOKEN'];
    $chatId = $env['TELEGRAM_CHAT_ID'];
    $token_seguridad = $env['CRON_TOKEN'];
}

// Validación de seguridad
if (!isset($_GET['token']) || $_GET['token'] !== $token_seguridad) {
    header('HTTP/1.1 401 Unauthorized');
    die("Token de seguridad inválido.");
}

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// 2. Buscar tareas (Ordenadas por materia para agrupar)
$query = "SELECT titulo, materia, tipo, fecha_apertura, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes 
          FROM tareas 
          WHERE estado = 'pendiente' 
          AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7
          ORDER BY materia ASC, fecha_entrega ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Evaluar y enviar el mensaje agrupado
if (count($tareas) > 0) {
    // APERTURA
    $mensaje = "🔔 *RECORDATORIO DIARIO* 🔔\n\n";
    
    $materiaActual = "";

    foreach ($tareas as $t) {
        $materia = str_replace(['_', '*', '`'], ' ', $t['materia'] ?? 'General');
        $titulo = str_replace(['_', '*', '`'], ' ', $t['titulo']);
        $dias = $t['dias_restantes'];
        $tipo = $t['tipo'] ?? 'tarea';
        $f_apertura = $t['fecha_apertura'] ?? 'N/A';
        $f_entrega = $t['fecha_entrega'] ?? 'N/A';
        
        // Icono según el tipo
        $icono = ($tipo == 'test') ? "🎓" : "📝";
        
        // Encabezado de materia si cambia
        if ($materia !== $materiaActual) {
            $mensaje .= "\n📘 *{$materia}*\n";
            $materiaActual = $materia;
        }

        $texto_dias = ($dias == 0) ? "¡VENCE HOY!" : "vence en $dias día(s)";
        $mensaje .= "{$icono} {$titulo}\n";
        $mensaje .= "📅 *Apertura:* {$f_apertura}\n";
        $mensaje .= "⌛ *Cierre:* {$f_entrega} ({$texto_dias})\n";
    }

    // CIERRE
    $mensaje .= "\n🚀 _¡A estudiar se ha dicho!_";

    $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch); 
    curl_close($ch);
    
    echo "Reporte enviado con éxito.";
} else {
    echo "No hay tareas urgentes. Todo al día.";
}
?>