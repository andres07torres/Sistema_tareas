<?php
// Proyecto dockerizado y configurado para Supabase
require_once '../config/database.php';
$mensaje = '';
$db = (new Database())->getConnection();

// Lógica para cargar datos si se está editando
$edit_mode = false;
$tarea_actual = [
    'id' => '',
    'titulo' => '',
    'descripcion' => '',
    'fecha_entrega' => '',
    'fecha_apertura' => '',
    'materia' => '',
    'tipo' => 'tarea',
    'estado' => 'pendiente'
];

if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $stmt_edit = $db->prepare("SELECT * FROM tareas WHERE id = :id");
    $stmt_edit->execute([':id' => $id_edit]);
    $res = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    if ($res) {
        $tarea_actual = $res;
        $edit_mode = true;
    }
}

// --- MANEJO DE POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. IMPORTACIÓN CSV
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        
        // Detectar delimitador
        $f = fopen($file, 'r');
        $firstLine = fgets($f);
        fclose($f);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        
        $handle = fopen($file, "r");
        $header = fgetcsv($handle, 1000, $delimiter, "\"", "\\"); // Saltar cabecera
        
        $count = 0;
        $errors = [];
        $lineNum = 1;

        $query = "INSERT INTO tareas (titulo, descripcion, fecha_entrega, estado, materia, tipo, fecha_apertura) 
                  VALUES (:titulo, :descripcion, :fecha_entrega, :estado, :materia, :tipo, :fecha_apertura)";
        $stmt = $db->prepare($query);

        while (($data = fgetcsv($handle, 1000, $delimiter, "\"", "\\")) !== FALSE) {
            $lineNum++;
            if (empty(array_filter($data))) continue;

            if (count($data) >= 7) {
                try {
                    // Limpiar descripción y convertir a NULL si está vacía o es 'EMPTY'
                    $descripcion = trim($data[1]);
                    if ($descripcion === "" || strtoupper($descripcion) === "EMPTY") {
                        $descripcion = null;
                    }

                    $stmt->execute([
                        ':titulo'         => trim($data[0]),
                        ':descripcion'    => $descripcion,
                        ':fecha_entrega'  => trim($data[2]),
                        ':estado'         => trim($data[3]) ?: 'pendiente',
                        ':materia'        => trim($data[4]),
                        ':tipo'           => trim($data[5]),
                        ':fecha_apertura' => trim($data[6])
                    ]);
                    $count++;
                } catch (Exception $e) {
                    $errors[] = "Línea $lineNum: " . $e->getMessage();
                }
            } else {
                $errors[] = "Línea $lineNum: Columnas insuficientes.";
            }
        }
        fclose($handle);
        
        if (empty($errors)) {
            $mensaje = "<div class='alert alert-success'>¡Éxito! Se importaron $count tareas.</div>";
        } else {
            $mensaje = "<div class='alert alert-error'>Importación finalizada con " . count($errors) . " errores. ($count éxitos)</div>";
        }
    }
    
    // 2. FORMULARIO MANUAL
    elseif (isset($_POST['titulo'])) {
        $id = $_POST['id'] ?? null;
        $descripcion = trim($_POST['descripcion']);
        if ($descripcion === "" || strtoupper($descripcion) === "EMPTY") {
            $descripcion = null;
        }

        if ($id) {
            $query = "UPDATE tareas SET titulo = :titulo, descripcion = :descripcion, fecha_entrega = :fecha_entrega, 
                      fecha_apertura = :fecha_apertura, materia = :materia, tipo = :tipo WHERE id = :id";
            $params = [
                ':titulo' => trim($_POST['titulo']),
                ':descripcion' => $descripcion,
                ':fecha_entrega' => trim($_POST['fecha_entrega']),
                ':fecha_apertura' => trim($_POST['fecha_apertura']),
                ':materia' => trim($_POST['materia']),
                ':tipo' => trim($_POST['tipo']),
                ':id' => $id
            ];
        } else {
            $query = "INSERT INTO tareas (titulo, descripcion, fecha_entrega, fecha_apertura, materia, tipo) 
                      VALUES (:titulo, :descripcion, :fecha_entrega, :fecha_apertura, :materia, :tipo)";
            $params = [
                ':titulo' => trim($_POST['titulo']),
                ':descripcion' => $descripcion,
                ':fecha_entrega' => trim($_POST['fecha_entrega']),
                ':fecha_apertura' => trim($_POST['fecha_apertura']),
                ':materia' => trim($_POST['materia']),
                ':tipo' => trim($_POST['tipo'])
            ];
        }

        try {
            $db->prepare($query)->execute($params);
            $mensaje = "<div class='alert alert-success'>Tarea guardada correctamente.</div>";
        } catch (Exception $e) {
            $mensaje = "<div class='alert alert-error'>Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Editar Tarea' : 'Gestor de Tareas'; ?></title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #65676b;
            --accent-blue: #0d6efd;
            --border-color: #dddfe2;
        }

        body { font-family: system-ui, sans-serif; background: var(--bg-color); margin: 0; color: var(--text-primary); }
        
        .container { max-width: 950px; margin: 0 auto; padding: 2rem 1rem; }
        
        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
        }

        .form-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 2rem; }

        .card { 
            background: var(--card-bg); 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
            border: 1px solid var(--border-color);
        }

        h2 { margin-top: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; }

        label { display: block; margin-bottom: 0.4rem; font-weight: 600; color: #4b4f56; font-size: 0.85rem; }

        input, textarea, select { 
            width: 100%; 
            margin-bottom: 1rem; 
            padding: 0.7rem; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 0.9rem;
            background-color: #f5f6f7;
            transition: all 0.2s ease;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent-blue);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        .btn-primary { 
            background: var(--accent-blue); 
            color: white; 
            border: none; 
            padding: 0.8rem 2rem;
            border-radius: 8px;
            cursor: pointer; 
            font-weight: 700; 
            font-size: 0.95rem; 
            display: inline-block;
        }

        .btn-danger { 
            background: #dc3545; 
            color: white; 
            border: none; 
            padding: 0.8rem 2rem;
            border-radius: 8px;
            cursor: pointer; 
            font-weight: 700; 
            font-size: 0.95rem; 
            text-decoration: none;
            display: inline-block;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-csv {
            background: #198754;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid transparent;
        }
        .alert-success { color: #155724; background: #d4edda; border-color: #c3e6cb; }
        .alert-error { color: #721c24; background: #f8d7da; border-color: #f5c6cb; }

        .csv-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            border: 1px dashed #ced4da;
        }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <header style="margin-bottom: 2rem;">
            <h1>Añadir Nueva Tarea</h1>
            <p style="color: var(--text-secondary); font-size: 1rem; margin-top: 0.25rem;">Registra manualmente o importa tus actividades académicas</p>
        </header>
        <?php echo $mensaje; ?>

        <div class="form-grid">
            <!-- FORMULARIO MANUAL -->
            <div class="card">
                <h2><i data-lucide="<?php echo $edit_mode ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $edit_mode ? 'Editar Tarea' : 'Nueva Tarea'; ?></h2>
                <form method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="id" value="<?php echo $tarea_actual['id']; ?>">
                    <?php endif; ?>

                    <label>Materia</label>
                    <select name="materia" required>
                        <option value="" disabled <?php echo empty($tarea_actual['materia']) ? 'selected' : ''; ?>>Selecciona...</option>
                        <?php
                        $materias = ["SISTEMAS DISTRIBUIDOS", "SISTEMA DE GESTIÓN DE LA SEGURIDAD DE LA INFORMACIÓN", "PRÁCTICAS LABORALES II", "GESTIÓN DE SISTEMAS DE CALIDAD", "FORMULACIÓN Y EVALUACIÓN DEL TRABAJO DE TITULACIÓN", "COMPUTACIÓN MÓVIL"];
                        foreach ($materias as $m) {
                            $sel = ($tarea_actual['materia'] == $m) ? 'selected' : '';
                            echo "<option value='$m' $sel>$m</option>";
                        }
                        ?>
                    </select>

                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="tarea" <?php echo $tarea_actual['tipo'] == 'tarea' ? 'selected' : ''; ?>>Tarea (Entregable)</option>
                        <option value="test" <?php echo $tarea_actual['tipo'] == 'test' ? 'selected' : ''; ?>>Test / Lección</option>
                        <option value="foro" <?php echo $tarea_actual['tipo'] == 'foro' ? 'selected' : ''; ?>>Foro</option>
                    </select>

                    <label>Título</label>
                    <input type="text" name="titulo" required value="<?php echo htmlspecialchars($tarea_actual['titulo'] ?? ''); ?>">
                    
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3"><?php echo htmlspecialchars($tarea_actual['descripcion'] ?? ''); ?></textarea>
                    
                    <div style="display: flex; gap: 1rem;">
                        <div style="flex: 1;">
                            <label>Apertura</label>
                            <input type="date" name="fecha_apertura" required value="<?php echo $tarea_actual['fecha_apertura']; ?>">
                        </div>
                        <div style="flex: 1;">
                            <label>Entrega</label>
                            <input type="date" name="fecha_entrega" required value="<?php echo $tarea_actual['fecha_entrega']; ?>">
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn-primary"><?php echo $edit_mode ? 'Actualizar' : 'Guardar'; ?></button>
                        <?php if ($edit_mode): ?>
                            <a href="actividades.php" class="btn-danger">Cancelar</a>
                        <?php else: ?>
                             <a href="index.php" class="btn-danger">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- IMPORTACIÓN CSV -->
            <div class="card" style="height: fit-content;">
                <h2><i data-lucide="file-up"></i> Importar CSV</h2>
                <div class="csv-info">
                    <strong>Formato de tu archivo:</strong><br>
                    <code>titulo, descripcion, fecha_entrega, estado, materia, tipo, fecha_apertura</code><br><br>
                    <em>Nota: Asegúrate de que las fechas sean AAAA-MM-DD.</em>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <label>Archivo .csv</label>
                    <input type="file" name="csv_file" accept=".csv" required style="background: white; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 8px;">
                    <button type="submit" class="btn-csv">
                        <i data-lucide="upload-cloud" size="18"></i> Cargar Tareas
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>