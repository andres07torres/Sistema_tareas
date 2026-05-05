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
        body { font-family: system-ui, sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding: 2rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333; }
        input, textarea, button { width: 100%; margin-bottom: 1.5rem; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #0d6efd; color: white; border: none; cursor: pointer; font-weight: bold; font-size: 1rem; }
        button:hover { background: #0b5ed7; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="margin-top: 0; color: #1a1a1a;">Añadir Tarea</h2>
        <?php echo $mensaje; ?>
        
        <form method="POST">
            <label>Materia:</label>
            <select name="materia" style="width: 100%; margin-bottom: 1.5rem; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; background: white;" required>
                <option value="" disabled selected>Selecciona una materia...</option>
                <option value="SISTEMAS DISTRIBUIDOS">SISTEMAS DISTRIBUIDOS</option>
                <option value="SISTEMA DE GESTIÓN DE LA SEGURIDAD DE LA INFORMACIÓN">SISTEMA DE GESTIÓN DE LA SEGURIDAD DE LA INFORMACIÓN</option>
                <option value="PRÁCTICAS LABORALES II">PRÁCTICAS LABORALES II</option>
                <option value="GESTIÓN DE SISTEMAS DE CALIDAD">GESTIÓN DE SISTEMAS DE CALIDAD</option>
                <option value="FORMULACIÓN Y EVALUACIÓN DEL TRABAJO DE TITULACIÓN">FORMULACIÓN Y EVALUACIÓN DEL TRABAJO DE TITULACIÓN</option>
                <option value="COMPUTACIÓN MÓVIL">COMPUTACIÓN MÓVIL</option>
            </select>

            <label>Tipo de Actividad:</label>
            <select name="tipo" style="width: 100%; margin-bottom: 1.5rem; padding: 0.8rem; border: 1px solid #ccc; border-radius: 4px; background: white;">
                <option value="tarea">📝 Tarea (Entregable)</option>
                <option value="test">🎓 Test / Lección</option>
            </select>

            <label>Título de la tarea:</label>
            <input type="text" name="titulo" placeholder="Ej: Proyecto en QGIS" required>
            
            <label>Descripción o anotaciones:</label>
            <textarea name="descripcion" rows="3" placeholder="Detalles de la entrega..."></textarea>
            
            <label>Fecha de apertura:</label>
            <input type="date" name="fecha_apertura" value="<?php echo date('Y-m-d'); ?>" required>

            <label>Fecha máxima de entrega:</label>
            <input type="date" name="fecha_entrega" required>
            
            <button type="submit">Guardar Tarea</button>
        </form>
    </div>
</body>
</html>