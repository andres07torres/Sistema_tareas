<?php
require_once '../config/database.php';

$db = (new Database())->getConnection();

// --- LÓGICA DE PAGINACIÓN ---
$limit = 10; // Tareas por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Obtener total de registros para calcular páginas
$countQuery = "SELECT COUNT(*) FROM tareas";
$totalItems = $db->query($countQuery)->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Obtener tareas con LIMIT y OFFSET
$query = "SELECT * FROM tareas ORDER BY fecha_entrega DESC, materia ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
            max-width: 1100px;
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

        .search-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: white;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            width: 350px;
        }

        .search-wrapper i { color: var(--text-secondary); flex-shrink: 0; }
        .search-wrapper input { border: none; outline: none; width: 100%; font-size: 0.9rem; background: transparent; }

        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: visible;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f1f5f9; padding: 1rem; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-secondary); letter-spacing: 0.05em; }
        td { padding: 1rem; border-top: 1px solid var(--border-color); font-size: 0.9rem; vertical-align: middle; }
        tr:hover td { background-color: #f8fafc; }

        .materia-badge { font-size: 0.7rem; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: 4px; background: #e0f2fe; color: #0369a1; display: inline-block; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .tipo-badge { font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.4rem; border-radius: 4px; background: #f1f5f9; color: #475569; }

        .status-toggle { position: relative; display: inline-block; width: 44px; height: 22px; }
        .status-toggle input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--danger-red); transition: .4s; border-radius: 22px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--success-green); }
        input:checked + .slider:before { transform: translateX(22px); }

        .status-label { font-size: 0.7rem; font-weight: 800; margin-top: 0.25rem; text-transform: uppercase; }
        .status-active { color: var(--success-green); }
        .status-inactive { color: var(--danger-red); }

        .desc-text { color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.25rem; max-width: 300px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

        .date-info { display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.8rem; }
        .date-info span { display: flex; align-items: center; gap: 0.3rem; }

        .actions-dropdown { position: relative; display: inline-block; }
        .actions-btn { background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 0.5rem; border-radius: 50%; transition: background 0.2s; }
        .actions-btn:hover { background: #f1f5f9; color: var(--text-primary); }
        .dropdown-content { display: none; position: absolute; right: 0; top: 100%; background-color: white; min-width: 140px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-radius: 8px; z-index: 50; border: 1px solid var(--border-color); overflow: hidden; }
        .dropdown-content button { width: 100%; padding: 0.75rem 1rem; border: none; background: none; text-align: left; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; color: var(--text-primary); }
        .dropdown-content button:hover { background: #f8fafc; }
        .dropdown-content button.delete-btn { color: var(--danger-red); }
        .dropdown-content button.delete-btn:hover { background: #fff1f2; }
        .show { display: block; }

        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a, .pagination span {
            padding: 0.5rem 0.9rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 600;
            background: white;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .pagination a:hover {
            border-color: var(--accent-blue);
            color: var(--accent-blue);
            background: #f0f7ff;
        }

        .pagination .active {
            background: var(--accent-blue);
            color: white;
            border-color: var(--accent-blue);
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        @media (max-width: 900px) { .hide-tablet { display: none; } }
        @media (max-width: 600px) { header { flex-direction: column; align-items: flex-start; gap: 1rem; } .search-wrapper { width: 100%; } .hide-mobile { display: none; } }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <header>
            <div>
                <h1>Gestión de Actividades</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Control total de tus tareas y visibilidad del Bot</p>
            </div>
            <div class="search-wrapper">
                <i data-lucide="search" size="18"></i>
                <input type="text" id="searchInput" placeholder="Buscar actividad, materia o descripción...">
            </div>
        </header>

        <div class="table-container">
            <table id="tasksTable">
                <thead>
                    <tr>
                        <th style="width: 70px;">Bot</th>
                        <th>Actividad</th>
                        <th class="hide-mobile">Materia</th>
                        <th class="hide-tablet">Fechas (Ap./Ent.)</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tareas)): ?>
                        <tr>
                            <td colspan="5" style="padding: 4rem; text-align: center; color: var(--text-secondary);">
                                <i data-lucide="clipboard-x" size="48" style="margin-bottom: 1rem; opacity: 0.3;"></i>
                                <p>No hay tareas registradas.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tareas as $t): 
                            $isActive = ($t['estado'] === 'pendiente');
                            $statusText = $isActive ? 'Activo' : 'Pasivo';
                            $statusClass = $isActive ? 'status-active' : 'status-label status-inactive'; // Fixed class concatenation
                        ?>
                            <tr data-id="<?php echo $t['id']; ?>">
                                <td>
                                    <div style="display: flex; flex-direction: column; align-items: center;">
                                        <label class="status-toggle">
                                            <input type="checkbox" <?php echo $isActive ? 'checked' : ''; ?> 
                                                   onchange="updateStatus(<?php echo $t['id']; ?>, this)">
                                            <span class="slider"></span>
                                        </label>
                                        <span class="<?php echo $isActive ? 'status-active' : 'status-inactive'; ?> status-label" id="label-<?php echo $t['id']; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 1rem;">
                                        <?php echo htmlspecialchars($t['titulo']); ?>
                                        <span class="tipo-badge" style="margin-left: 0.5rem;"><?php echo ucfirst($t['tipo']); ?></span>
                                    </div>
                                    <?php if (!empty($t['descripcion'])): ?>
                                        <div class="desc-text" title="<?php echo htmlspecialchars($t['descripcion']); ?>">
                                            <?php echo htmlspecialchars($t['descripcion']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile">
                                    <span class="materia-badge" title="<?php echo htmlspecialchars($t['materia']); ?>">
                                        <?php echo htmlspecialchars($t['materia']); ?>
                                    </span>
                                </td>
                                <td class="hide-tablet">
                                    <div class="date-info">
                                        <span title="Fecha de Apertura">
                                            <i data-lucide="calendar-plus" size="14" style="color: #94a3b8;"></i>
                                            <?php echo date('d/m/Y', strtotime($t['fecha_apertura'])); ?>
                                        </span>
                                        <span title="Fecha de Entrega" style="font-weight: 600; color: var(--text-primary);">
                                            <i data-lucide="calendar-check" size="14" style="color: var(--accent-blue);"></i>
                                            <?php echo date('d/m/Y', strtotime($t['fecha_entrega'])); ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <div class="actions-dropdown">
                                        <button class="actions-btn" onclick="toggleDropdown(<?php echo $t['id']; ?>)">
                                            <i data-lucide="more-vertical" size="20"></i>
                                        </button>
                                        <div id="dropdown-<?php echo $t['id']; ?>" class="dropdown-content">
                                            <button onclick="window.location.href='index.php?edit=<?php echo $t['id']; ?>'">
                                                <i data-lucide="edit-3" size="14"></i> Editar
                                            </button>
                                            <button class="delete-btn" onclick="deleteTask(<?php echo $t['id']; ?>)">
                                                <i data-lucide="trash-2" size="14"></i> Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <a href="?page=<?php echo $page - 1; ?>" class="<?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <i data-lucide="chevron-left" size="18"></i>
                </a>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span style="border:none; background:transparent;">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <a href="?page=<?php echo $page + 1; ?>" class="<?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <i data-lucide="chevron-right" size="18"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();

        // Buscador (Nota: El buscador ahora solo filtra lo que está en la página actual debido a la paginación del servidor)
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tasksTable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        function toggleDropdown(id) {
            document.querySelectorAll('.dropdown-content').forEach(d => {
                if (d.id !== 'dropdown-' + id) d.classList.remove('show');
            });
            document.getElementById('dropdown-' + id).classList.toggle('show');
        }

        window.onclick = function(event) {
            if (!event.target.matches('.actions-btn') && !event.target.closest('.actions-btn')) {
                document.querySelectorAll('.dropdown-content').forEach(d => d.classList.remove('show'));
            }
        }

        function updateStatus(id, checkbox) {
            const nuevoEstado = checkbox.checked ? 'pendiente' : 'inactivo';
            const label = document.getElementById('label-' + id);
            label.textContent = checkbox.checked ? 'Activo' : 'Pasivo';
            label.className = checkbox.checked ? 'status-active status-label' : 'status-inactive status-label';

            fetch('api_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, estado: nuevoEstado })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('Error: ' + data.error);
                    checkbox.checked = !checkbox.checked;
                    updateStatusLabels(id, checkbox.checked);
                }
            });
        }

        function deleteTask(id) {
            if (!confirm('¿Estás seguro de que deseas eliminar esta tarea permanentemente?')) return;
            fetch('api_delete_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // Opcional: recargar si la página queda vacía
                        if (document.querySelectorAll('#tasksTable tbody tr').length === 0) location.reload();
                    }, 300);
                } else {
                    alert('Error al eliminar: ' + data.error);
                }
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
