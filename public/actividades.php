<?php
require_once '../config/database.php';

$db = (new Database())->getConnection();

// Obtener todas las tareas ordenadas por fecha de entrega
$query = "SELECT * FROM tareas ORDER BY fecha_entrega DESC, materia ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actividades | Asistente UNEMI</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --accent-blue: #3b82f6;
            --success-green: #22c55e;
            --danger-red: #ef4444;
            --border-color: #e2e8f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
        }

        .search-container {
            position: relative;
            width: 300px;
        }

        .search-container input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
        }

        .search-container input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-container i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            width: 16px;
        }

        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: #f1f5f9;
            padding: 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-secondary);
            letter-spacing: 0.05em;
        }

        td {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.9rem;
            vertical-align: middle;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .materia-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background: #e0f2fe;
            color: #0369a1;
            display: inline-block;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tipo-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            background: #f1f5f9;
            color: #475569;
        }

        /* Toggle Switch Styling */
        .status-toggle {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }

        .status-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: var(--danger-red);
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success-green);
        }

        input:checked + .slider:before {
            transform: translateX(24px);
        }

        .status-label {
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 0.5rem;
            text-transform: uppercase;
        }

        .status-active { color: var(--success-green); }
        .status-inactive { color: var(--danger-red); }

        .empty-state {
            padding: 4rem;
            text-align: center;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            header { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .search-container { width: 100%; }
            .hide-mobile { display: none; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <header>
            <div>
                <h1>Gestión de Actividades</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Controla qué tareas son visibles en el bot de Telegram</p>
            </div>
            <div class="search-container">
                <i data-lucide="search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por título o materia...">
            </div>
        </header>

        <div class="table-container">
            <table id="tasksTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">Bot</th>
                        <th>Tarea / Actividad</th>
                        <th class="hide-mobile">Materia</th>
                        <th class="hide-mobile">Fecha Entrega</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tareas)): ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i data-lucide="clipboard-x" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <p>No se encontraron actividades registradas.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tareas as $t): 
                            $isActive = ($t['estado'] === 'pendiente');
                            $statusText = $isActive ? 'Activo' : 'Pasivo';
                            $statusClass = $isActive ? 'status-active' : 'status-inactive';
                        ?>
                            <tr data-id="<?php echo $t['id']; ?>">
                                <td>
                                    <label class="status-toggle">
                                        <input type="checkbox" <?php echo $isActive ? 'checked' : ''; ?> 
                                               onchange="updateStatus(<?php echo $t['id']; ?>, this)">
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($t['titulo']); ?></div>
                                    <div style="font-size: 0.75rem; display: flex; gap: 0.5rem; margin-top: 0.25rem;">
                                        <span class="tipo-badge"><?php echo ucfirst($t['tipo']); ?></span>
                                        <span class="<?php echo $statusClass; ?> status-label" id="label-<?php echo $t['id']; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="hide-mobile">
                                    <span class="materia-badge" title="<?php echo htmlspecialchars($t['materia']); ?>">
                                        <?php echo htmlspecialchars($t['materia']); ?>
                                    </span>
                                </td>
                                <td class="hide-mobile">
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                        <?php echo date('d/m/Y', strtotime($t['fecha_entrega'])); ?>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <!-- Botones de acción futura (editar/borrar) -->
                                    <button style="background: none; border: none; color: var(--text-secondary); cursor: not-allowed;" title="Próximamente">
                                        <i data-lucide="more-vertical" style="width: 18px;"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Buscador en tiempo real
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tasksTable tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // Actualizar estado vía AJAX
        function updateStatus(id, checkbox) {
            const nuevoEstado = checkbox.checked ? 'pendiente' : 'inactivo';
            const label = document.getElementById('label-' + id);
            
            // Efecto visual inmediato
            label.textContent = checkbox.checked ? 'Activo' : 'Pasivo';
            label.className = checkbox.checked ? 'status-active status-label' : 'status-inactive status-label';

            fetch('api_update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    estado: nuevoEstado
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error al actualizar: ' + (data.error || 'Desconocido'));
                    // Revertir si hay error
                    checkbox.checked = !checkbox.checked;
                    updateStatusLabels(id, checkbox.checked);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión');
                checkbox.checked = !checkbox.checked;
                updateStatusLabels(id, checkbox.checked);
            });
        }

        function updateStatusLabels(id, isChecked) {
            const label = document.getElementById('label-' + id);
            label.textContent = isChecked ? 'Activo' : 'Pasivo';
            label.className = isChecked ? 'status-active status-label' : 'status-inactive status-label';
        }
    </script>
</body>
</html>
