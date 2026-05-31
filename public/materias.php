<?php
require_once '../config/database.php';

$db = (new Database())->getConnection();

// Lógica de paginación o simplemente traer todas
$query = "SELECT * FROM materias ORDER BY nombre ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Materias | Asistente de Tareas</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/css/base.css">
    <style>
        .container {
            max-width: 900px;
        }

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

        .btn-add {
            background: #203145;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.9rem;
            transition: filter 0.2s;
        }

        .btn-add:hover { filter: brightness(1.1); }

        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f0f2f5; padding: 1.2rem 1rem; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); }
        td { padding: 1rem; border-top: 1px solid var(--border-color); font-size: 0.95rem; vertical-align: middle; }
        tr:hover td { background-color: #f0f2f5; }

        .materia-name {
            font-weight: 700;
            color: #0f172a;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .btn-icon:hover { background: #e2e8f0; color: var(--text-primary); }
        .btn-icon.delete:hover { background: #fbe7e9; color: var(--danger-red); }

        /* Estilos del Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none; justify-content: center; align-items: center;
            z-index: 2000; padding: 1rem;
        }

        .modal-content {
            background: white; padding: 2rem; border-radius: 16px;
            width: 100%; max-width: 450px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;
        }

        .modal-header h2 { font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem; }

        .close-modal {
            background: #f0f2f5; border: none; color: var(--text-secondary);
            cursor: pointer; padding: 0.5rem; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        .modal-form label {
            display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem;
            color: var(--text-secondary); text-transform: uppercase;
        }

        .modal-form input {
            width: 100%; padding: 0.75rem; border: 1px solid var(--border-color);
            border-radius: 8px; margin-bottom: 1.5rem; font-family: inherit; font-size: 0.95rem;
        }

        .modal-footer { display: flex; justify-content: flex-end; gap: 1rem; }

        .btn-save, .btn-cancel {
            border: none; padding: 0.6rem 1.2rem; border-radius: 8px;
            font-weight: 700; cursor: pointer; font-size: 0.85rem;
        }

        .btn-save { background: #203145; color: white; }
        .btn-cancel { background: #e2e8f0; color: var(--text-primary); }

        .drive-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.7rem;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 700;
            transition: filter 0.2s;
        }

        .drive-link:hover { filter: brightness(0.9); }

        @media (max-width: 600px) {
            header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .btn-add { width: auto; padding: 0.5rem 0.8rem; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <header>
            <div>
                <h1>Gestión de Materias</h1>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 0.25rem;">Administra las materias disponibles para tus actividades</p>
            </div>
            <button class="btn-add" onclick="openModal()">
                <i data-lucide="plus"></i> Nueva Materia
            </button>
        </header>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre de la Materia</th>
                        <th>Enlace Drive</th>
                        <th style="width: 120px; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($materias)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                No hay materias registradas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($materias as $m): ?>
                            <tr>
                                <td class="materia-name"><?php echo htmlspecialchars($m['nombre']); ?></td>
                                <td>
                                    <?php if (!empty($m['drive_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($m['drive_link']); ?>" target="_blank" class="drive-link" title="Abrir carpeta de Drive">
                                            <i data-lucide="external-link" size="16"></i> Drive
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-size: 0.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button class="btn-icon" onclick="openModal(<?php echo $m['id']; ?>)">
                                            <i data-lucide="edit-2" size="18"></i>
                                        </button>
                                        <button class="btn-icon delete" onclick="deleteMateria(<?php echo $m['id']; ?>)">
                                            <i data-lucide="trash-2" size="18"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="materiaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i data-lucide="book"></i> Nueva Materia</h2>
                <button class="close-modal" onclick="closeModal()"><i data-lucide="x" size="20"></i></button>
            </div>
            <form id="materiaForm" class="modal-form">
                <input type="hidden" name="id" id="materiaId">
                
                <label>Nombre de la Materia</label>
                <input type="text" name="nombre" id="materiaNombre" required autocomplete="off" placeholder="Ej. Álgebra Lineal">

                <label>Enlace Google Drive</label>
                <input type="url" name="drive_link" id="materiaDriveLink" autocomplete="off" placeholder="https://drive.google.com/drive/folders/...">

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-save">Guardar Materia</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const modal = document.getElementById('materiaModal');
        const form = document.getElementById('materiaForm');
        
        async function openModal(id = null) {
            form.reset();
            document.getElementById('materiaId').value = '';
            document.getElementById('modalTitle').innerHTML = '<i data-lucide="book"></i> Nueva Materia';
            
            if (id) {
                try {
                    const response = await fetch(`api/api_get_materia.php?id=${id}`);
                    const result = await response.json();
                    if (result.success) {
                        document.getElementById('materiaId').value = result.data.id;
                        document.getElementById('materiaNombre').value = result.data.nombre;
                        document.getElementById('materiaDriveLink').value = result.data.drive_link || '';
                        document.getElementById('modalTitle').innerHTML = '<i data-lucide="edit"></i> Editar Materia';
                    } else {
                        alert(result.error);
                        return;
                    }
                } catch(e) {
                    alert('Error cargando los datos.');
                    return;
                }
            }
            
            modal.style.display = 'flex';
            lucide.createIcons();
            setTimeout(() => document.getElementById('materiaNombre').focus(), 100);
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        form.onsubmit = async (e) => {
            e.preventDefault();
            const data = {
                id: document.getElementById('materiaId').value,
                nombre: document.getElementById('materiaNombre').value,
                drive_link: document.getElementById('materiaDriveLink').value
            };
            
            try {
                const response = await fetch('api/api_save_materia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error(error);
                alert('Ocurrió un error inesperado.');
            }
        };

        async function deleteMateria(id) {
            if (!confirm('¿Estás seguro de que deseas eliminar esta materia? No se podrá si tiene actividades asignadas.')) return;
            
            try {
                const response = await fetch('api/api_delete_materia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error(error);
                alert('Ocurrió un error inesperado.');
            }
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
