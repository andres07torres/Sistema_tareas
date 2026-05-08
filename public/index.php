<?php
// Proyecto dockerizado y configurado para Supabase
require_once '../config/database.php';
$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = (new Database())->getConnection();
    
    // Consulta preparada para incluir todos los campos
    $query = "INSERT INTO tareas (titulo, descripcion, fecha_entrega, fecha_apertura, materia, tipo) 
              VALUES (:titulo, :descripcion, :fecha_entrega, :fecha_apertura, :materia, :tipo)";
    $stmt = $db->prepare($query);
    
    try {
        $stmt->execute([
            ':titulo' => trim($_POST['titulo']),
            ':descripcion' => trim($_POST['descripcion']),
            ':fecha_entrega' => trim($_POST['fecha_entrega']),
            ':fecha_apertura' => trim($_POST['fecha_apertura']),
            ':materia' => trim($_POST['materia']),
            ':tipo' => trim($_POST['tipo'])
        ]);
        $mensaje = "<div style='color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>¡Tarea registrada con éxito!</div>";
    } catch (Exception $e) {
        $mensaje = "<div style='color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>Error al guardar: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Asignaciones</title>
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
        
        .form-container { display: flex; justify-content: center; padding: 2rem 1rem; }
        
        .card { 
            background: var(--card-bg); 
            padding: 2.5rem; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
            width: 100%; 
            max-width: 450px; 
            border: 1px solid var(--border-color);
        }

        h2 { margin-top: 0; color: var(--text-primary); font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; text-align: center; }

        label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #4b4f56; font-size: 0.9rem; }

        input, textarea, select { 
            width: 100%; 
            margin-bottom: 1.25rem; 
            padding: 0.75rem 1rem; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-size: 0.95rem;
            background-color: #f5f6f7;
            transition: all 0.2s ease;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent-blue);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        button { 
            background: var(--accent-blue); 
            color: white; 
            border: none; 
            padding: 0.9rem;
            border-radius: 8px;
            cursor: pointer; 
            font-weight: 700; 
            font-size: 1rem; 
            width: 100%;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }

        button:hover { background: var(--accent-hover); }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; }
        .alert-error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="form-container">
        <div class="card">
            <h2>Añadir Nueva Tarea</h2>
            
            <?php 
            if ($mensaje) {
                $class = (strpos($mensaje, 'éxito') !== false) ? 'alert-success' : 'alert-error';
                echo "<div class='alert {$class}'>" . strip_tags($mensaje) . "</div>";
            }
            ?>
            
            <form method="POST">
                <label>📘 Materia</label>
                <select name="materia" required>
                    <option value="" disabled selected>Selecciona una materia...</option>
                    <option value="SISTEMAS DISTRIBUIDOS">SISTEMAS DISTRIBUIDOS</option>
                    <option value="SISTEMA DE GESTIÓN DE LA SEGURIDAD DE LA INFORMACIÓN">SISTEMA DE GESTIÓN DE LA SEGURIDAD DE LA INFORMACIÓN</option>
                    <option value="PRÁCTICAS LABORALES II">PRÁCTICAS LABORALES II</option>
                    <option value="GESTIÓN DE SISTEMAS DE CALIDAD">GESTIÓN DE SISTEMAS DE CALIDAD</option>
                    <option value="FORMULACIÓN Y EVALUACIÓN DEL TRABAJO DE TITULACIÓN">FORMULACIÓN Y EVALUACIÓN DEL TRABAJO DE TITULACIÓN</option>
                    <option value="COMPUTACIÓN MÓVIL">COMPUTACIÓN MÓVIL</option>
                </select>

                <label>🏷️ Tipo de Actividad</label>
                <select name="tipo">
                    <option value="tarea">📝 Tarea (Entregable)</option>
                    <option value="test">🎓 Test / Lección</option>
                </select>

                <label>📝 Título</label>
                <input type="text" name="titulo" placeholder="Ej: Proyecto en QGIS" required>
                
                <label>ℹ️ Descripción</label>
                <textarea name="descripcion" rows="3" placeholder="Detalles de la entrega..."></textarea>
                
                <div style="display: flex; gap: 1rem;">
                    <div style="flex: 1;">
                        <label>📅 Apertura</label>
                        <input type="date" name="fecha_apertura" required>
                    </div>
                    <div style="flex: 1;">
                        <label>⌛ Entrega</label>
                        <input type="date" name="fecha_entrega" required>
                    </div>
                </div>
                
                <button type="submit">Guardar Tarea</button>
            </form>
        </div>
    </div>
</body>
</html>