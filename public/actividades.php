<?php
require_once '../config/database.php';

$db = (new Database())->getConnection();

// --- CIERRE AUTOMÁTICO DE TAREAS VENCIDAS ---
$db->exec("UPDATE tareas SET estado = 'inactivo' WHERE estado = 'pendiente' AND fecha_entrega < CURRENT_DATE");

// --- LÓGICA DE PAGINACIÓN ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- FILTRO POR MATERIA ---
$materiaFilter = isset($_GET['materia']) ? trim($_GET['materia']) : '';
$whereClause = '';
$params = [];

if ($materiaFilter !== '') {
    $whereClause = " WHERE materia = :materia";
    $params[':materia'] = $materiaFilter;
}

// Obtener total de registros
$countQuery = "SELECT COUNT(*) FROM tareas" . $whereClause;
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// ORDENACIÓN: Primero por Materia, luego por Fecha de Entrega
$query = "SELECT * FROM tareas" . $whereClause . " ORDER BY materia ASC, fecha_entrega ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->execute();
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH MATERIAS with drive links
$materiasAllStmt = $db->query("SELECT nombre, drive_link FROM materias ORDER BY nombre ASC");
$materiasAll = $materiasAllStmt->fetchAll(PDO::FETCH_ASSOC);
$materias_db = array_column($materiasAll, 'nombre');
$materias_json = json_encode($materias_db);
$driveLinks = [];
foreach ($materiasAll as $m) {
    if (!empty($m['drive_link'])) {
        $driveLinks[$m['nombre']] = $m['drive_link'];
    }
}
$driveLinks_json = json_encode($driveLinks);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actividades | Asistente de Tareas</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/css/base.css">
    <style>
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: white;
            padding: 0.6rem 1.2rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            width: 380px;
        }

        .search-wrapper i { color: var(--text-secondary); flex-shrink: 0; }
        .search-wrapper input { border: none; outline: none; width: 100%; font-size: 0.95rem; background: transparent; font-family: inherit; }

        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            overflow: visible;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            transition: all 0.2s ease;
        }

        .table-container:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f0f2f5; padding: 1.2rem 1rem; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-secondary); letter-spacing: 0.05em; border-bottom: 2px solid var(--border-color); }
        td { padding: 1.2rem 1rem; border-top: 1px solid var(--border-color); font-size: 0.95rem; vertical-align: middle; }
        tr:hover td { background-color: #f0f2f5; }

        .task-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .materia-badge { 
            font-size: 0.75rem; 
            font-weight: 700; 
            padding: 0.3rem 0.6rem; 
            border-radius: 6px; 
            background: #e7f1ff; 
            color: #0d6efd; 
            display: inline-block; 
            border: 1px solid #cfe2ff;
        }

        .tipo-badge { font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 4px; background: #f0f2f5; color: #4b4f56; text-transform: uppercase; }

        .status-toggle { position: relative; display: inline-block; width: 44px; height: 22px; }
        .status-toggle input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--danger-red); border-radius: 22px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; }
        input:checked + .slider { background-color: var(--success-green); }
        input:checked + .slider:before { transform: translateX(22px); }

        .status-label { font-size: 0.7rem; font-weight: 800; margin-top: 0.3rem; text-transform: uppercase; }
        .status-active { color: var(--success-green); }
        .status-inactive { color: var(--danger-red); }

        .desc-text { color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.3rem; line-height: 1.4; }

        .date-info { display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.85rem; }
        .date-info span { display: flex; align-items: center; gap: 0.4rem; color: var(--text-secondary); }
        .date-info .entrega { font-weight: 700; color: var(--text-primary); }

        .actions-dropdown { position: relative; display: inline-block; }
        .actions-btn { background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 0.6rem; border-radius: 50%; }
        .actions-btn:hover { background: #f0f2f5; color: var(--text-primary); }
        .dropdown-content { display: none; position: absolute; right: 0; top: 110%; background-color: white; min-width: 150px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border-radius: 10px; z-index: 50; border: 1px solid var(--border-color); overflow: hidden; }
        .dropdown-content button { width: 100%; padding: 0.8rem 1rem; border: none; background: none; text-align: left; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 0.6rem; color: var(--text-primary); font-family: inherit; font-weight: 500; }
        .dropdown-content button:hover { background: #f0f2f5; }
        .dropdown-content button.delete-btn { color: var(--danger-red); }
        .dropdown-content button.delete-btn:hover { background: #fbe7e9; }
        .show { display: block; }

        .pagination { display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem; }
        .pagination a, .pagination span { padding: 0.6rem 1rem; border-radius: 10px; text-decoration: none; color: var(--text-secondary); font-size: 0.95rem; font-weight: 600; background: white; border: 1px solid var(--border-color); }
        .pagination a:hover { border-color: var(--accent-blue); color: var(--accent-blue); background: #e7f1ff; }
        .pagination .active { background: var(--accent-blue); color: white; border-color: var(--accent-blue); }
        .pagination .disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }

        @media (max-width: 900px) { .hide-tablet { display: none; } }
        @media (max-width: 600px) { header { flex-direction: column; align-items: flex-start; gap: 1.5rem; } .search-wrapper { width: 100%; } .hide-mobile { display: none; } }

        /* Estilos del Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: 1rem;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            width: 100%;
            max-width: 550px;
            max-height: 90dvh;
            overflow-y: auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        @media (max-width: 600px) {
            .modal-content {
                padding: 1.25rem;
                max-height: 85dvh;
                margin: 0.5rem;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-modal {
            background: #f0f2f5;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            background: #e2e8f0;
            color: var(--danger-red);
        }

        .modal-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .modal-form input, .modal-form select, .modal-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-family: inherit;
            font-size: 0.95rem;
        }

        .modal-form input:focus, .modal-form select:focus, .modal-form textarea:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }

        .modal-footer {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-save {
            background: #203145;
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.85rem;
        }

        .btn-save:hover { filter: brightness(1.1); }

        .btn-cancel {
            background: #203145;
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.85rem;
        }

        .btn-cancel:hover { filter: brightness(1.1); }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .btn-add, .btn-import {
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            cursor: pointer;
            color: white;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-add { background: #203145; }
        .btn-import { background: #203145; }

        .btn-add:hover, .btn-import:hover { filter: brightness(1.1); }

        @media (max-width: 850px) {
            header { flex-direction: column; align-items: stretch !important; gap: 1rem; }
            .header-actions { order: -1; justify-content: space-between; }
            .search-wrapper { width: 100% !important; }
        }

        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .header-actions { flex-direction: row; flex-wrap: wrap; gap: 0.5rem; }
            .header-actions button { width: auto; flex: 1; padding: 0.5rem; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <header>
            <div>
                <h1>Gestión de Actividades</h1>
                <p style="color: var(--text-secondary); font-size: 1rem; margin-top: 0.25rem;">Organiza y controla la visibilidad de tus tareas</p>
            </div>
            <div class="header-actions">
                <button class="btn-import" onclick="openImportModal()">
                    <i data-lucide="file-up" size="18"></i> Importar CSV
                </button>
                <button class="btn-add" onclick="openAddModal()">
                    <i data-lucide="plus-circle" size="18"></i> Nueva Actividad
                </button>
            </div>
        </header>

        <div style="margin-bottom: 2rem; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <div class="search-wrapper" style="flex: 1; min-width: 200px;">
                <i data-lucide="search" size="20"></i>
                <input type="text" id="searchInput" placeholder="Buscar actividad, materia o descripción...">
            </div>
            <select id="materiaFilter" style="padding: 0.6rem 1rem; border: 1px solid var(--border-color); border-radius: 12px; font-size: 0.95rem; font-family: inherit; background: white; cursor: pointer; min-width: 160px;">
                <option value="">Todas las materias</option>
                <?php foreach ($materias_db as $m): ?>
                    <option value="<?php echo htmlspecialchars($m); ?>" <?php echo ($materiaFilter === $m) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="table-container">
            <table id="tasksTable">
                <thead>
                    <tr>
                        <th style="width: 80px; text-align: center;">Bot</th>
                        <th>Información de la Actividad</th>
                        <th class="hide-mobile">Tipo</th>
                        <th class="hide-mobile">Materia</th>
                        <th class="hide-tablet">Fechas Clave</th>
                        <th style="text-align: right; width: 80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tareas)): ?>
                        <tr>
                            <td colspan="6" style="padding: 5rem; text-align: center; color: var(--text-secondary);">
                                <i data-lucide="clipboard-x" size="56" style="margin-bottom: 1.5rem; opacity: 0.2;"></i>
                                <p style="font-size: 1.1rem; font-weight: 500;">No hay actividades para mostrar en esta página.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tareas as $t): 
                            $isActive = ($t['estado'] === 'pendiente');
                            $statusText = $isActive ? 'Activo' : 'Pasivo';
                            $statusClass = $isActive ? 'status-active' : 'status-inactive';
                        ?>
                            <tr data-id="<?php echo $t['id']; ?>">
                                <td style="text-align: center;">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.2rem;">
                                        <label class="status-toggle">
                                            <input type="checkbox" <?php echo $isActive ? 'checked' : ''; ?> 
                                                   onchange="updateStatus(<?php echo $t['id']; ?>, this)">
                                            <span class="slider"></span>
                                        </label>
                                        <span class="<?php echo $statusClass; ?> status-label" id="label-<?php echo $t['id']; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="task-title">
                                        <?php echo htmlspecialchars($t['titulo'] ?? ''); ?>
                                    </div>
                                    <?php if (!empty($t['descripcion'])): ?>
                                        <div class="desc-text"><?php echo htmlspecialchars($t['descripcion'] ?? ''); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile">
                                    <span class="tipo-badge"><?php echo ucfirst($t['tipo']); ?></span>
                                </td>
                                <td class="hide-mobile">
                                    <span class="materia-badge" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                                        <?php echo htmlspecialchars($t['materia'] ?? ''); ?>
                                        <?php
                                            $mat = $t['materia'] ?? '';
                                            if ($mat && isset($driveLinks[$mat])):
                                        ?>
                                            <a href="<?php echo htmlspecialchars($driveLinks[$mat]); ?>" target="_blank" title="Abrir carpeta de Drive" style="color: #2e7d32;">
                                                <i data-lucide="external-link" size="12"></i>
                                            </a>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="hide-tablet">
                                    <div class="date-info">
                                        <span title="Apertura">
                                            <i data-lucide="unlock" size="14"></i>
                                            Abre: <?php echo date('d/m/Y', strtotime($t['fecha_apertura'])); ?>
                                        </span>
                                        <?php if (!empty($t['limite_drive'])): ?>
                                        <span title="Limite Drive">
                                            <i data-lucide="cloud" size="14"></i>
                                            Drive: <?php echo date('d/m/Y', strtotime($t['limite_drive'])); ?>
                                        </span>
                                        <?php endif; ?>
                                        <span title="Cierre" class="entrega">
                                            <i data-lucide="lock" size="14"></i>
                                            Cierra: <?php echo date('d/m/Y', strtotime($t['fecha_entrega'])); ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <div class="actions-dropdown">
                                        <button class="actions-btn" onclick="toggleDropdown(<?php echo $t['id']; ?>)">
                                            <i data-lucide="more-horizontal" size="22"></i>
                                        </button>
                                         <div id="dropdown-<?php echo $t['id']; ?>" class="dropdown-content">
                                             <button onclick="openEditModal(<?php echo $t['id']; ?>)">
                                                 <i data-lucide="edit-2" size="16"></i> Editar
                                             </button>
                                            <button class="delete-btn" onclick="deleteTask(<?php echo $t['id']; ?>)">
                                                <i data-lucide="trash-2" size="16"></i> Eliminar
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

        <?php if ($totalPages > 1): ?>
            <?php
                $queryParams = ($materiaFilter !== '') ? '&materia=' . urlencode($materiaFilter) : '';
            ?>
            <div class="pagination">
                <a href="?page=<?php echo $page - 1 . $queryParams; ?>" class="<?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <i data-lucide="chevron-left" size="20"></i>
                </a>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i . $queryParams; ?>" class="<?php echo ($page == $i) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                <a href="?page=<?php echo $page + 1 . $queryParams; ?>" class="<?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <i data-lucide="chevron-right" size="20"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL DE IMPORTACIÓN CSV -->
    <div id="importModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i data-lucide="upload-cloud"></i> Importar Actividades</h2>
                <button class="close-modal" onclick="closeModal('importModal')"><i data-lucide="x" size="20"></i></button>
            </div>
            <div style="background: #f0f2f5; padding: 1rem; border-radius: 8px; font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1.5rem; border: 1px dashed var(--border-color);">
                <strong>Formato CSV:</strong><br>
                <code>titulo, descripcion, fecha_entrega, estado, materia, tipo, fecha_apertura, limite_drive</code>
            </div>
            <form id="importForm" class="modal-form">
                <label>Seleccionar Archivo CSV</label>
                <input type="file" name="csv_file" accept=".csv" required style="padding: 0.5rem; background: #fff;">
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('importModal')">Cancelar</button>
                    <button type="submit" class="btn-save" id="btnImportSubmit">Procesar Archivo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DE EDICIÓN / ADICIÓN -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i data-lucide="edit"></i> Editar Actividad</h2>
                <button class="close-modal" onclick="closeModal('editModal')"><i data-lucide="x" size="20"></i></button>
            </div>
            <form id="editForm" class="modal-form">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="tipo" id="editTipo">
                
                <div id="formFields">
                    <!-- Los campos se cargarán dinámicamente aquí -->
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tasksTable tbody tr');
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        document.getElementById('materiaFilter').addEventListener('change', function() {
            const materia = this.value;
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            if (materia) {
                url.searchParams.set('materia', materia);
            } else {
                url.searchParams.delete('materia');
            }
            window.location.href = url.toString();
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
            label.className = (checkbox.checked ? 'status-active' : 'status-inactive') + ' status-label';

            fetch('api/api_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, estado: nuevoEstado })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert('Error: ' + data.error);
                    location.reload();
                }
            });
        }

        function deleteTask(id) {
            if (!confirm('¿Seguro que deseas eliminar esta actividad?')) return;
            fetch('api/api_delete_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    row.style.opacity = '0';
                    setTimeout(() => location.reload(), 300);
                }
            });
        }

        // FUNCIONES DEL MODAL
        const editModal = document.getElementById('editModal');
        const importModal = document.getElementById('importModal');
        const editForm = document.getElementById('editForm');
        const formFields = document.getElementById('formFields');

        function openAddModal() {
            document.getElementById('editId').value = '';
            document.getElementById('editTipo').value = 'tarea'; // Por defecto
            renderForm({ tipo: 'tarea', titulo: '', descripcion: '', fecha_apertura: '', fecha_entrega: '', materia: '' });
            document.getElementById('modalTitle').innerHTML = '<i data-lucide="plus-circle"></i> Nueva Actividad';
            editModal.style.display = 'flex';
            lucide.createIcons();
        }

        async function openEditModal(id) {
            try {
                const response = await fetch(`api/api_get_task.php?id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const task = result.data;
                    document.getElementById('editId').value = task.id;
                    document.getElementById('editTipo').value = task.tipo;
                    
                    renderForm(task);
                    document.getElementById('modalTitle').innerHTML = '<i data-lucide="edit"></i> Editar Actividad';
                    editModal.style.display = 'flex';
                    lucide.createIcons();
                } else {
                    alert(result.error);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        function openImportModal() {
            importModal.style.display = 'flex';
            lucide.createIcons();
        }

        function closeModal(modalId) {
            if (modalId) {
                document.getElementById(modalId).style.display = 'none';
            } else {
                editModal.style.display = 'none';
                importModal.style.display = 'none';
            }
        }

        function renderForm(task) {
            const tipo = task.tipo;
            let fieldsHtml = '';

            // Selector de Materia (Común para todos)
            const materias = <?php echo $materias_json; ?>;
            let materiaOptions = materias.map(m => `<option value="${m}" ${task.materia === m ? 'selected' : ''}>${m}</option>`).join('');

            const materiaField = `
                <label>Materia</label>
                <select name="materia" required>${materiaOptions}</select>
            `;

            const tituloField = `
                <label>Título de la ${tipo.toUpperCase()}</label>
                <input type="text" name="titulo" value="${task.titulo || ''}" required>
            `;

            const descField = `
                <label>Descripción / Instrucciones</label>
                <textarea name="descripcion" rows="3">${task.descripcion || ''}</textarea>
            `;

            const datesField = `
                <div class="form-row">
                    <div style="flex: 1;">
                        <label>Fecha Apertura</label>
                        <input type="date" name="fecha_apertura" value="${task.fecha_apertura || ''}" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Fecha Entrega</label>
                        <input type="date" name="fecha_entrega" value="${task.fecha_entrega || ''}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div style="flex: 1;">
                        <label>Limite Drive <span style="font-weight:400;text-transform:none;">(opcional)</span></label>
                        <input type="date" name="limite_drive" value="${task.limite_drive || ''}">
                    </div>
                    <div style="flex: 1;"></div>
                </div>
            `;

            if (tipo === 'tarea') {
                fieldsHtml = materiaField + tituloField + descField + datesField;
                document.getElementById('modalTitle').innerHTML = '<i data-lucide="file-text"></i> Editar Tarea';
            } else if (tipo === 'foro') {
                fieldsHtml = materiaField + tituloField + descField + datesField;
                document.getElementById('modalTitle').innerHTML = '<i data-lucide="message-square"></i> Editar Foro';
            } else if (tipo === 'test' || tipo === 'test') {
                fieldsHtml = materiaField + tituloField + `
                    <label>Fecha del Test</label>
                    <input type="date" name="fecha_entrega" value="${task.fecha_entrega || ''}" required>
                    <label>Limite Drive <span style="font-weight:400;text-transform:none;">(opcional)</span></label>
                    <input type="date" name="limite_drive" value="${task.limite_drive || ''}">
                `;
                document.getElementById('modalTitle').innerHTML = '<i data-lucide="graduation-cap"></i> Editar Test';
            }

            formFields.innerHTML = fieldsHtml;
        }

        editForm.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(editForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('api/api_save_task.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    closeModal();
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        };

        const importForm = document.getElementById('importForm');
        importForm.onsubmit = async (e) => {
            e.preventDefault();
            const btnSubmit = document.getElementById('btnImportSubmit');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Procesando...';

            const formData = new FormData(importForm);
            try {
                const response = await fetch('api/api_import_csv.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error al procesar el archivo.');
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Procesar Archivo';
            }
        };

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target == editModal) closeModal('editModal');
            if (event.target == importModal) closeModal('importModal');
            if (!event.target.matches('.actions-btn') && !event.target.closest('.actions-btn')) {
                document.querySelectorAll('.dropdown-content').forEach(d => d.classList.remove('show'));
            }
        }
    </script>
</body>
</html>
