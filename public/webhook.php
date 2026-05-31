<?php
// 1. CARGAR CONFIGURACIÓN
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}

$telegramToken = $_ENV['TELEGRAM_TOKEN'] ?? getenv('TELEGRAM_TOKEN');

// 2. RECIBIR DATOS
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || (!isset($update["message"]) && !isset($update["callback_query"]))) {
    die("Sin mensaje ni callback.");
}

// --- FUNCIONES ---

function enviarRespuesta($chatId, $token, $mensaje) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Registra o actualiza un suscriptor en la base de datos
 */
function registrarSuscriptor($chatId, $update, $db) {
    $nombre = "Desconocido";
    $tipo = $update["message"]["chat"]["type"] ?? "private";

    if ($tipo == "private") {
        $nombre = $update["message"]["from"]["first_name"] ?? "Usuario";
    } else {
        $nombre = $update["message"]["chat"]["title"] ?? "Grupo";
    }

    $stmt = $db->prepare("INSERT INTO suscriptores (chat_id, nombre, tipo_chat) 
                          VALUES (:id, :nom, :tipo) 
                          ON CONFLICT (chat_id) DO UPDATE SET nombre = EXCLUDED.nombre");
    $stmt->execute([':id' => $chatId, ':nom' => $nombre, ':tipo' => $tipo]);
}

function enviarKeyboard($chatId, $token, $mensaje, $botones) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $botones], JSON_UNESCAPED_UNICODE)
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

function formatearTexto($tareas, $titulo_seccion) {
    if (count($tareas) == 0) return "☕ No hay tareas pendientes.";
    
    $res = "{$titulo_seccion}\n";
    $materiaActual = "";
    
    foreach ($tareas as $t) {
        $materia = $t['materia'] ?? 'General';
        $titulo = $t['titulo'];
        $f_entrega = $t['fecha_entrega'] ?? 'N/A';
        $dias = $t['dias_restantes'] ?? null;
        $tipo = $t['tipo'] ?? 'tarea';
        
        if ($materia !== $materiaActual) {
            $res .= "\n📘 *{$materia}*\n";
            $materiaActual = $materia;
        }
        
        $icono = ($tipo == 'test') ? "🎓" : "📝";
        $vence = "";
        if ($dias !== null) {
            if ($dias < 0) $vence = " (atrasada " . abs($dias) . "d)";
            elseif ($dias == 0) $vence = " (¡HOY!)";
            else $vence = " (vence en {$dias}d)";
        }
        
        $res .= "{$icono} *{$titulo}*\n";
        $limiteDrive = $t['limite_drive'] ?? null;
        if ($limiteDrive) {
            $res .= "⌛ *Limite Drive:* {$limiteDrive}\n";
        }
        $res .= "⌛ *Cierre:* {$f_entrega}{$vence}\n";
    }
    return $res;
}

// 3. EJECUCIÓN
try {
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();

    // --- CIERRE AUTOMÁTICO DE TAREAS VENCIDAS ---
    $db->exec("UPDATE tareas SET estado = 'inactivo' WHERE estado = 'pendiente' AND fecha_entrega < CURRENT_DATE");

    // ========== CALLBACK QUERY (CLIC EN BOTÓN) ==========
    if (isset($update["callback_query"])) {
        $callback = $update["callback_query"];
        $chatId = $callback["message"]["chat"]["id"];
        $data = $callback["data"];

        // Responder el callback para quitar el "cargando"
        $url = "https://api.telegram.org/bot{$telegramToken}/answerCallbackQuery";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['callback_query_id' => $callback['id']]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);

        if (strpos($data, 'materia|') === 0) {
            $materia = substr($data, 8);
            $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega, limite_drive, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' AND materia = :materia ORDER BY fecha_entrega ASC");
            $stmt->execute([':materia' => $materia]);
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtDrive = $db->prepare("SELECT drive_link FROM materias WHERE nombre = ?");
            $stmtDrive->execute([$materia]);
            $driveLink = $stmtDrive->fetchColumn();

            $respuesta = formatearTexto($tareas, "📚 ACTIVIDADES DE *" . strtoupper($materia) . "*");
            if ($driveLink) {
                $respuesta .= "\n📁 *Carpeta Drive:* [Abrir enlace]({$driveLink})";
            }
            enviarRespuesta($chatId, $telegramToken, $respuesta);
        }
        exit;
    }

    // ========== MENSAJE DE TEXTO (COMANDOS) ==========
    $chatId = $update["message"]["chat"]["id"];
    $text = trim($update["message"]["text"] ?? "");

    // Limpieza para grupos
    if (strpos($text, '/') === 0) {
        $parts = explode(' ', $text);
        $cmd = explode('@', $parts[0])[0];
        $text = $cmd . (isset($parts[1]) ? ' ' . implode(' ', array_slice($parts, 1)) : '');
    }

    // REGISTRO AUTOMÁTICO DE SUSCRIPTOR
    registrarSuscriptor($chatId, $update, $db);

    if ($text == "/start" || $text == "/ayuda") {
        enviarRespuesta($chatId, $telegramToken, "🤖 *Asistente UNEMI Activo*\n\n/hoy - Tareas de hoy\n/semana - Próximos 7 días\n/tareas - Todos los pendientes\n/materias - Ver por materia\n/motivacion - Frase motivacional");
    }
    elseif ($text == "/hoy") {
        $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega FROM tareas WHERE estado = 'pendiente' AND fecha_entrega = CURRENT_DATE ORDER BY materia ASC");
        $stmt->execute();
        enviarRespuesta($chatId, $telegramToken, formatearTexto($stmt->fetchAll(PDO::FETCH_ASSOC), "📅 TAREAS PARA HOY"));
    }
    elseif ($text == "/semana") {
        $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7 ORDER BY materia ASC, fecha_entrega ASC");
        $stmt->execute();
        enviarRespuesta($chatId, $telegramToken, formatearTexto($stmt->fetchAll(PDO::FETCH_ASSOC), "🗓 REPORTE DE LA SEMANA"));
    }
    elseif ($text == "/tareas") {
        $stmt = $db->prepare("SELECT titulo, materia, tipo, fecha_entrega, (fecha_entrega - CURRENT_DATE) as dias_restantes FROM tareas WHERE estado = 'pendiente' ORDER BY materia ASC, fecha_entrega ASC");
        $stmt->execute();
        enviarRespuesta($chatId, $telegramToken, formatearTexto($stmt->fetchAll(PDO::FETCH_ASSOC), "📋 TODOS LOS PENDIENTES"));
    }
    elseif ($text == "/materias") {
        $stmt = $db->query("SELECT nombre, drive_link FROM materias ORDER BY nombre ASC");
        $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($materias)) {
            enviarRespuesta($chatId, $telegramToken, "☕ No hay materias registradas.");
        } else {
            $botones = [];
            foreach ($materias as $m) {
                $icono = !empty($m['drive_link']) ? "📁" : "📘";
                $botones[] = [['text' => "{$icono} {$m['nombre']}", 'callback_data' => "materia|{$m['nombre']}"]];
            }
            enviarKeyboard($chatId, $telegramToken, "📚 *SELECCIONA UNA MATERIA*\n\nElige una materia para ver sus actividades y enlace Drive:", $botones);
        }
    }
    elseif ($text == "/motivacion") {
        $meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
        $dia = date('j');
        $mes = $meses[(int)date('m') - 1];
        $frases = [
            "El éxito no es la clave de la felicidad. La felicidad es la clave del éxito. — Albert Schweitzer",
            "El estudio es la llave que abre todas las puertas. — Anónimo",
            "No estudies para saber más, estudia para ser más. — Anónimo",
            "La educación es el arma más poderosa que puedes usar para cambiar el mundo. — Nelson Mandela",
            "El conocimiento te da poder, pero el carácter te da respeto. — Anónimo",
            "Los grandes logros requieren tiempo y perseverancia. No te rindas.",
            "Hoy es  $dia de $mes, un día perfecto para avanzar. ¡Tú puedes!",
            "No se trata de tener tiempo, se trata de hacerlo. ¡A darle!",
            "El mejor momento para empezar fue ayer. El segundo mejor momento es ahora. — Proverbio chino",
            "Las pequeñas acciones diarias llevan a grandes resultados. — Anónimo",
            "La disciplina es el puente entre tus metas y tus logros. — Jim Rohn",
            "Cada día es una nueva oportunidad para mejorar. Aprovecha este $dia de $mes.",
            "No cuentes los días, haz que los días cuenten. — Muhammad Ali",
            "El éxito es la suma de pequeños esfuerzos repetidos día tras día. — Robert Collier",
            "Cree en ti y en tu capacidad para lograr lo que te propongas.",
            "La única competencia real eres tú mismo. Hoy $dia de $mes, sé mejor que ayer.",
            "Las dificultades preparan a personas comunes para destinos extraordinarios. — C.S. Lewis",
            "El estudio profundo construye mentes brillantes. ¡Sigue adelante!",
            "Hoy puede ser el día en que todo cambie. Solo necesitas empezar.",
            "No esperes el momento perfecto, toma el momento y hazlo perfecto. — Zoey Sayward",
            "La constancia vence lo que la fuerza no puede. — Anónimo",
            "El secreto del éxito es empezar. — Mark Twain",
            "No dejes para mañana lo que puedes hacer hoy. — Benjamín Franklin",
            "El que estudia y no practica, como el que caza y no mata. — Anónimo",
            "La excelencia no es un acto, es un hábito. — Aristóteles",
            "Si puedes soñarlo, puedes lograrlo. — Walt Disney",
            "El esfuerzo de hoy es el éxito de mañana. — Anónimo",
            "No hay atajos para llegar a donde vale la pena. — Beverly Sills",
            "El aprendizaje nunca cansa a la mente. — Leonardo da Vinci",
            "La motivación te ayuda a empezar, el hábito te hace continuar. — Jim Ryun",
            "Tu única competencia eres tú mismo. — Anónimo",
            "El éxito no es para los que nunca fallan, sino para los que nunca se rinden. — Anónimo",
            "Cada hora de estudio es una inversión en tu futuro. — Anónimo",
            "La diferencia entre lo imposible y lo posible está en tu determinación. — Tommy Lasorda",
            "Estudiar es construir tu propio futuro ladrillo a ladrillo. — Anónimo",
            "No te compares con otros, compárate con la persona que fuiste ayer. — Anónimo",
            "El conocimiento es la única riqueza que nadie puede quitarte. — Anónimo",
            "Haz de hoy un día productivo, tu futuro te lo agradecerá. — Anónimo",
            "La mejor inversión que puedes hacer es en ti mismo. — Warren Buffett",
            "No importa cuán lento vayas, mientras no te detengas. — Confucio",
            "El estudio te da alas para volar más alto. — Anónimo",
            "La disciplina es el puente entre las metas y los logros. — Jim Rohn",
            "Cada día es una segunda oportunidad. Aprovéchala. — Anónimo",
            "Si te caes ayer, levántate hoy. — Proverbio inglés",
            "Tu esfuerzo de hoy define tu éxito de mañana. No te rindas. — Anónimo",
            "El éxito es la suma de pequeños esfuerzos repetidos. — Robert Collier",
            "No hay viento favorable para el que no sabe a dónde va. — Séneca",
            "El primer paso siempre es el más difícil. Ya lo diste. — Anónimo",
            "La perseverancia no es una carrera larga, son muchas carreras cortas. — Anónimo",
            "El único lugar donde el éxito viene antes que el trabajo es en el diccionario. — Vidal Sassoon",
            "La educación no es preparación para la vida, la educación es la vida misma. — John Dewey",
            "Los hábitos pequeños crean resultados grandes. — Anónimo",
            "El momento de actuar es ahora. Mañana puede ser tarde. — Anónimo",
            "El dolor del estudio es temporal, la ignorancia es para siempre. — Anónimo",
            "Los sueños no funcionan a menos que tú trabajes. — Anónimo",
            "Cada error es una lección disfrazada. — Anónimo",
            "La mente es como un músculo: mientras más la ejercitas, más fuerte se vuelve. — Anónimo",
            "No te rindas, los principios son duros, pero el final es hermoso. — Anónimo",
            "La mejor forma de predecir el futuro es crearlo. — Peter Drucker",
            "Las grandes cosas nunca vienen de las zonas de confort. — Anónimo",
            "Si hoy estudias, mañana cosechas. — Anónimo",
            "El éxito no es casualidad, es trabajo duro, perseverancia y aprendizaje. — Anónimo",
            "Aprender es como remar contra corriente: si dejas de hacerlo, retrocedes. — Proverbio chino",
            "Tú eres el dueño de tu destino. Empieza hoy. — Anónimo",
            "La clave del éxito está en empezar antes de estar listo. — Anónimo",
            "El estudio no ocupa espacio, pero llena el alma. — Anónimo",
            "La única forma de hacer un gran trabajo es amar lo que haces. — Steve Jobs",
            "Tu actitud determina tu altitud. — Anónimo",
            "No esperes a estar motivado para empezar, empieza y la motivación llegará. — Anónimo",
            "El aprendizaje es un tesoro que seguirá a su dueño a todas partes. — Proverbio chino",
            "Hazlo con pasión o no lo hagas. — Anónimo",
            "Las paredes que construyes alrededor de tu zona de confort también bloquean tu crecimiento. — Anónimo",
            "El tiempo es el recurso más valioso, úsalo sabiamente. — Anónimo",
            "Primero ellos te ignoran, luego se rien de ti, luego te vencen. — Mahatma Gandhi",
            "La excelencia no es un destino, es un viaje continuo. — Anónimo",
            "El estudio te da respuestas que la experiencia no puede explicar. — Anónimo",
            "No es que no tengas tiempo, es que no es prioridad. — Anónimo",
            "El fracaso es la materia prima del éxito. — Anónimo",
            "La preparación es la clave del éxito. — Alexander Graham Bell",
            "Cada página leída te acerca un paso más a tu meta. — Anónimo",
            "El éxito es fácil de obtener si haces lo necesario cuando no tienes ganas. — Anónimo",
            "No compares tu progreso con el de otros. Cada uno corre su propia carrera. — Anónimo",
            "La paciencia y la constancia son las alas del logro. — Anónimo",
            "Tu futuro depende de lo que hagas hoy, no de lo que harás mañana. — Anónimo",
            "El estudio te hace libre. — Anónimo",
            "La fuerza no viene de ganar. Tus luchas desarrollan tus fortalezas. — Arnold Schwarzenegger",
            "No hay gloria sin esfuerzo. — Anónimo",
            "El éxito es un viaje, no un destino. El hacer es más importante que el resultado. — Anónimo",
            "Cree en ti y el mundo creerá en ti. — Anónimo",
            "El mejor momento para plantar un árbol fue hace 20 años. El segundo mejor momento es ahora. — Proverbio chino",
            "No busques el éxito, conviértete en una persona de valor. — Albert Einstein",
            "La disciplina es el arte de hacer lo que hay que hacer cuando hay que hacerlo. — Anónimo",
            "El estudio constante forma mentes brillantes. — Anónimo",
            "No te detengas cuando estés cansado, detente cuando hayas terminado. — Anónimo",
            "El conocimiento habla, pero la sabiduría escucha. — Jimi Hendrix",
            "Los obstáculos son esas cosas que ves cuando apartas los ojos de tus metas. — Anónimo",
            "La educación es el pasaporte hacia el futuro. — Malcolm X",
            "Tu único límite es tu mente. — Anónimo",
            "La motivación es lo que te pone en marcha, el hábito es lo que te mantiene. — Anónimo",
            "El éxito no es la meta, es el camino. — Anónimo",
            "Estudiar es el combustible de la mente. — Anónimo",
            "No hay atajos para la excelencia. — Anónimo",
            "La diferencia entre una persona exitosa y otra que no lo es no es la falta de fuerza, sino la falta de voluntad. — Vince Lombardi",
            "Puedes tener resultados diferentes si haces cosas diferentes. — Anónimo",
            "El momento es ahora. No esperes más. — Anónimo",
            "Tu potencial es infinito, solo tienes que decidir usarlo. — Anónimo",
            "La vida es 10% lo que te sucede y 90% cómo reaccionas ante ello. — Charles R. Swindoll",
            "La excelencia no es un lujo, es una necesidad. — Anónimo",
            "El aprendizaje es un regalo que nadie puede robarte. — Anónimo",
            "No te preocupes por los fracasos, preocúpate por las oportunidades que pierdes cuando ni siquiera lo intentas. — Jack Canfield",
            "El éxito es la suma de pequeños pasos dados cada día. — Anónimo",
            "La mente que se abre a una nueva idea jamás volverá a su tamaño original. — Albert Einstein",
            "No dejes que lo que no puedes hacer interfiera con lo que puedes hacer. — John Wooden",
            "El secreto para avanzar es comenzar. — Mark Twain",
            "La educación cuesta dinero, pero la ignorancia también. — Anónimo",
            "La perseverancia es la madre del éxito. — Anónimo",
            "El talento es importante, pero la actitud lo es todo. — Anónimo",
            "No puedes cruzar el mar simplemente mirando el agua. — Rabindranath Tagore",
            "El éxito no se logra solo con cualidades especiales, se logra con acciones ordinarias hechas extraordinariamente bien. — Anónimo",
            "El primer paso hacia el éxito es la decisión. — Anónimo",
            "Nunca es demasiado tarde para ser lo que podrías haber sido. — George Eliot",
            "La falta de dirección, no la falta de tiempo, es el problema. — Anónimo",
            "Hoy es el día perfecto para empezar algo grandioso. — Anónimo",
            "La educación genera confianza, la confianza genera esperanza, la esperanza genera paz. — Confucio",
            "Las personas exitosas hacen lo que las personas no exitosas no quieren hacer. — Anónimo",
            "No necesitas ser grande para empezar, pero necesitas empezar para ser grande. — Zig Ziglar",
            "El conocimiento es poder. La información es liberadora. — Kofi Annan",
            "Cada día es una oportunidad para ser mejor que ayer. — Anónimo",
            "Tu futuro es creado por lo que haces hoy, no mañana. — Anónimo",
            "La acción es la clave fundamental de todo éxito. — Pablo Picasso",
            "No esperes las condiciones perfectas, empieza donde estás. — Anónimo",
            "El esfuerzo de hoy es la fuerza del mañana. — Anónimo",
            "Estudiar es sembrar conocimiento, la cosecha es eterna. — Anónimo",
            "El éxito no es mágico, es trabajo. — Anónimo",
            "La educación es la base sobre la cual construimos nuestro futuro. — Christine Gregoire",
            "No te rindas, cada fracaso es un paso más cerca del éxito. — Anónimo",
            "La clave del éxito está en la constancia y la paciencia. — Anónimo",
            "Cada día sin estudiar es un día perdido. — Anónimo",
            "Los sueños se logran con esfuerzo, dedicación y mucha disciplina. — Anónimo",
            "La mejor herencia que puedes dejar es una educación sólida. — Anónimo",
            "Aprender de los errores te hace más fuerte. — Anónimo",
            "El éxito es la consecuencia del esfuerzo constante. — Anónimo",
            "No importa cuántas veces caigas, sino cuántas veces te levantes. — Anónimo",
            "La educación es el arma más poderosa contra la mediocridad. — Anónimo",
            "Tu actitud, no tu aptitud, determinará tu altitud. — Zig Ziglar",
            "El éxito no es un accidente, es trabajo duro. — Anónimo",
            "La persistencia es el camino más corto hacia el éxito. — Anónimo",
            "El estudio te prepara para cuando la oportunidad llegue. — Anónimo",
            "No hay suerte, solo preparación encuentro a la oportunidad. — Anónimo",
            "La educación te da las alas para volar. — Anónimo",
            "El cambio es difícil al principio, pero hermoso al final. — Anónimo",
            "No estudies para aprobar, estudia para aprender. — Anónimo",
            "El límite no está en el cielo, está en tu mente. — Anónimo",
            "La dedicación diaria construye logros extraordinarios. — Anónimo",
            "Cada esfuerzo cuenta, cada minuto suma. — Anónimo",
            "La zona de confort es tu peor enemiga. — Anónimo",
            "No hay metas imposibles, solo plazos más largos. — Anónimo",
            "El éxito es la suma de pequeños aciertos. — Anónimo",
            "Hoy puede ser el día más importante de tu vida. — Anónimo",
            "La constancia es la virtud más poderosa. — Anónimo",
            "Tú decides si hoy es un día perdido o ganado. — Anónimo",
            "El aprendizaje es continuo, no se detiene nunca. — Anónimo",
            "No te compares con los demás, compárate con tu potencial. — Anónimo",
            "La educación es la inversión más rentable. — Benjamin Franklin",
            "El conocimiento bien usado es poder absoluto. — Anónimo",
            "Los pequeños avances diarios generan grandes resultados. — Anónimo",
            "La disciplina es el motor del logro. — Anónimo",
            "El éxito es para quienes se levantan una vez más. — Anónimo",
            "La paciencia abre puertas que la prisa cierra. — Anónimo",
            "El estudio te da claridad en un mundo confuso. — Anónimo",
            "La sabiduría no llega por casualidad, se construye. — Anónimo",
            "Cada día de estudio es un paso hacia tu mejor versión. — Anónimo",
            "La motivación te despierta, la disciplina te mantiene. — Anónimo",
            "No subestimes el poder de empezar hoy. — Anónimo",
            "El conocimiento es la luz que disipa la oscuridad de la ignorancia. — Anónimo",
            "El verdadero éxito es superarte a ti mismo cada día. — Anónimo",
            "La clave está en hacerlo, no en pensarlo. — Anónimo",
            "El estudio te hace dueño de tu destino. — Anónimo",
            "No hay atajos para el conocimiento. — Anónimo",
            "La perseverancia convierte el fracaso en éxito. — Anónimo",
            "El esfuerzo es la moneda del éxito. — Anónimo",
            "La educación es el camino hacia la libertad financiera. — Anónimo",
            "Tu capacidad de aprender es infinita. — Anónimo",
            "No te rindas antes de intentarlo. — Anónimo",
            "El éxito es la recompensa de los que persisten. — Anónimo",
            "El estudio es el puente hacia tus metas. — Anónimo",
            "La excelencia se cultiva día a día. — Anónimo",
            "La disciplina vence al talento cuando el talento no se disciplina. — Anónimo",
            "El esfuerzo de hoy es el éxito de mañana. — Anónimo",
            "No esperes el momento perfecto, hazlo perfecto. — Anónimo",
            "Cada hora de estudio te acerca a tu mejor yo. — Anónimo",
            "La educación te da el poder de cambiar tu historia. — Anónimo",
            "El éxito no es un destino, es el camino que recorres. — Anónimo",
            "El aprendizaje te hace imparable. — Anónimo",
            "Hoy es el mejor día para empezar a cambiar tu vida. — Anónimo",
            "La ignorancia es la única enfermedad que no necesita tratamiento, sino educación. — Anónimo",
            "El éxito es la suma de tus decisiones diarias. — Anónimo",
            "No hay barrera que la educación no pueda romper. — Anónimo",
            "La persistencia es el secreto de todos los triunfos. — Anónimo",
            "Tu futuro depende de lo que hagas hoy. — Anónimo",
            "El conocimiento es el único bien que aumenta cuando se comparte. — Anónimo",
            "Los sueños sin acción son solo sueños. — Anónimo",
            "La educación te da la llave para abrir cualquier puerta. — Anónimo",
            "El éxito no es un regalo, es una conquista. — Anónimo",
            "La dedicación es la semilla del logro. — Anónimo",
            "Cada error es una oportunidad para aprender y crecer. — Anónimo",
            "La educación es la vacuna contra la mediocridad. — Anónimo",
            "La excelencia comienza con la decisión de intentarlo. — Anónimo",
            "El estudio es el camino más corto al éxito. — Anónimo",
            "La constancia rompe barreras. — Anónimo",
            "No hay éxito sin sacrificio. — Anónimo",
            "El conocimiento te hace libre e independiente. — Anónimo",
            "El verdadero fracaso es no haberlo intentado. — Anónimo",
            "La educación es la base de toda grandeza. — Anónimo",
            "El esfuerzo constante vence cualquier obstáculo. — Anónimo",
            "La paciencia y el estudio son las alas del éxito. — Anónimo",
            "Hoy es el día para dar el primer paso hacia tu meta. — Anónimo",
            "El aprendizaje es la aventura que nunca termina. — Anónimo",
            "La disciplina es el puente entre el sueño y la realidad. — Anónimo",
            "El éxito no es para los que tienen suerte, es para los que se esfuerzan. — Anónimo",
            "La educación te transforma de adentro hacia afuera. — Anónimo",
            "Cada nuevo conocimiento te hace más fuerte. — Anónimo",
            "No te detengas hasta estar orgulloso de ti mismo. — Anónimo",
            "La perseverancia es la clave que abre todas las puertas. — Anónimo"
        ];
        $frase = $frases[array_rand($frases)];
        enviarRespuesta($chatId, $telegramToken, "🌟 *FRASE DEL DÍA*\n📅 {$dia} de {$mes}\n\n_{$frase}_\n\n💪 ¡A darle con todo!");
    }

} catch (Exception $e) {
    enviarRespuesta($chatId, $telegramToken, "⚠️ Error: " . $e->getMessage());
}
