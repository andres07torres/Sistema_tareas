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
    'tipo' => 'tarea'
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

// --- MANEJO DE POST (REGISTRO / EDICIÓN / CSV) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. IMPORTACIÓN CSV
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        $header = fgetcsv($handle, 1000, ","); // Leer cabecera
        
        $count = 0;
        $errors = 0;
        
        $query = "INSERT INTO tareas (titulo, descripcion, fecha_entrega, fecha_apertura, materia, tipo) 
                  VALUES (:titulo, :descripcion, :fecha_entrega, :fecha_apertura, :materia, :tipo)";
        $stmt = $db->prepare($query);

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Esperamos: titulo, descripcion, fecha_entrega, fecha_apertura, materia, tipo
            if (count($data) >= 6) {
                try {
                    $stmt->execute([
                        ':titulo' => trim($data[0]),
                        ':descripcion' => trim($data[1]),
                        ':fecha_entrega' => trim($data[2]),
                        ':fecha_apertura' => trim($data[3]),
                        ':materia' => trim($data[4]),
                        ':tipo' => trim($data[5])
                    ]);
                    $count++;
                } catch (Exception $e) {
                    $errors++;
                }
            }
        }
        fclose($handle);
        $mensaje = "<div class='alert alert-success'>¡Importación finalizada! Registrados: $count, Errores: $errors</div>";
    }
    
    // 2. FORMULARIO MANUAL
    elseif (isset($_POST['titulo'])) {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // MODO EDICIÓN
            $query = "UPDATE tareas SET titulo = :titulo, descripcion = :descripcion, fecha_entrega = :fecha_entrega, 
                      fecha_apertura = :fecha_apertura, materia = :materia, tipo = :tipo WHERE id = :id";
            $params = [
                ':titulo' => trim($_POST['titulo']),
                ':descripcion' => trim($_POST['descripcion']),
                ':fecha_entrega' => trim($_POST['fecha_entrega']),
                ':fecha_apertura' => trim($_POST['fecha_apertura']),
                ':materia' => trim($_POST['materia']),
                ':tipo' => trim($_POST['tipo']),
                ':id' => $_POST['id']
            ];
            $msg_exito = "¡Tarea actualizada con éxito!";
        } else {
            // MODO CREACIÓN
            $query = "INSERT INTO tareas (titulo, descripcion, fecha_entrega, fecha_apertura, materia, tipo) 
                      VALUES (:titulo, :descripcion, :fecha_entrega, :fecha_apertura, :materia, :tipo)";
            $params = [
                ':titulo' => trim($_POST['titulo']),
                ':descripcion' => trim($_POST['descripcion']),
                ':fecha_entrega' => trim($_POST['fecha_entrega']),
                ':fecha_apertura' => trim($_POST['fecha_apertura']),
                ':materia' => trim($_POST['materia']),
                ':tipo' => trim($_POST['tipo'])
            ];
            $msg_exito = "¡Tarea registrada con éxito!";
        }

        $stmt = $db->prepare($query);
        try {
            $stmt->execute($params);
            $mensaje = "<div class='alert alert-success'>{$msg_exito}</div>";
        } catch (Exception $e) {
            $mensaje = "<div class='alert alert-error'>Error al guardar: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Editar Tarea' : 'Gestor de Asignaciones'; ?></title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --text-primary: #1c1e21;
            --text-secondary: #65676b;
            --accent-blue: #0d6efd;
            --accent-hover: #0b5ed7;
            --border-color: #dddfe2;
        }

        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg-color); padding: 0; margin: 0; color: var(--text-primary); }
        
        .container { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; display: flex; flex-direction: column; gap: 2rem; }
        
        .form-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; }

        .card { 
            background: var(--card-bg); 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
            border: 1px solid var(--border-color);
        }

        h2 { margin-top: 0; color: var(--text-primary); font-size: 1.3rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }

        label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #4b4f56; font-size: 0.85rem; }

        input, textarea, select { 
            width: 100%; 
            margin-bottom: 1rem; 
            padding: 0.7rem 0.9rem; 
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
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer; 
            font-weight: 700; 
            font-size: 0.95rem; 
            width: 100%;
            transition: background 0.2s;
        }
        .btn-primary:hover { background: var(--accent-hover); }

        .btn-csv {
            background: #198754;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            width: 100%;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-csv:hover { background: #157347; }

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
            font-size: 0.8rem;
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
                    </select>

                    <label>Título</label>
                    <input type="text" name="titulo" required value="<?php echo htmlspecialchars($tarea_actual['titulo']); ?>">
                    
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3"><?php echo htmlspecialchars($tarea_actual['descripcion']); ?></textarea>
                    
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
                    
                    <button type="submit" class="btn-primary"><?php echo $edit_mode ? 'Actualizar' : 'Guardar'; ?></button>
                    <?php if ($edit_mode): ?>
                        <a href="index.php" style="display:block; text-align:center; margin-top:1rem; color:var(--text-secondary); text-decoration:none; font-size:0.9rem;">Cancelar Edición</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- IMPORTACIÓN CSV -->
            <div class="card" style="height: fit-content;">
                <h2><i data-lucide="file-up"></i> Importar CSV</h2>
                <div class="csv-info">
                    <strong>Formato requerido (con cabecera):</strong><br>
                    <code>titulo, descripcion, fecha_entrega, fecha_apertura, materia, tipo</code><br><br>
                    <em>Nota: Las fechas deben ser YYYY-MM-DD.</em>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <label>Seleccionar Archivo .csv</label>
                    <input type="file" name="csv_file" accept=".csv" required style="background: white; padding: 0.5rem;">
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