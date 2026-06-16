<?php
require_once '../config/database.php';

$db = (new Database())->getConnection();

$search = $_GET['search'] ?? '';

if ($search) {
    $stmt = $db->prepare("
        SELECT d.*, m.nombre AS materia_nombre
        FROM documentos_drive d
        JOIN materias m ON m.id = d.materia_id
        WHERE LOWER(d.nombre) LIKE LOWER(:search)
           OR LOWER(m.nombre) LIKE LOWER(:search)
           OR LOWER(d.tipo) LIKE LOWER(:search)
        ORDER BY d.detectado_en DESC
    ");
    $stmt->execute([':search' => "%$search%"]);
} else {
    $stmt = $db->query("
        SELECT d.*, m.nombre AS materia_nombre
        FROM documentos_drive d
        JOIN materias m ON m.id = d.materia_id
        ORDER BY d.detectado_en DESC
    ");
}
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Drive | Asistente de Tareas</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/css/base.css">
    <style>
        .container { max-width: 1100px; }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
        }

        .search-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 0.5rem 1rem;
            max-width: 320px;
            width: 100%;
        }

        .search-bar input {
            border: none;
            background: none;
            outline: none;
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--text-primary);
            width: 100%;
        }

        .search-bar input::placeholder { color: var(--text-secondary); }
        .search-bar svg { color: var(--text-secondary); flex-shrink: 0; }

        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f0f2f5; padding: 1.2rem 1rem; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); }
        td { padding: 1rem; border-top: 1px solid var(--border-color); font-size: 0.95rem; vertical-align: middle; }
        tr:hover td { background-color: #f0f2f5; }

        .doc-name { font-weight: 600; color: #0f172a; }

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

        .tipo-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.55rem;
            border-radius: 4px;
            text-transform: uppercase;
            display: inline-block;
        }

        .tipo-pdf { background: #fbe7e9; color: #c62828; }
        .tipo-doc, .tipo-docx { background: #e3f2fd; color: #1565c0; }
        .tipo-otro { background: #f0f2f5; color: #4b4f56; }

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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-icon:hover { background: #e2e8f0; color: var(--text-primary); }
        .btn-icon.notify:hover { background: #e8f5e9; color: #2e7d32; }

        .status-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.55rem;
            border-radius: 4px;
            display: inline-block;
        }

        .status-notified { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff3e0; color: #e65100; }

        .date-text {
            color: var(--text-secondary);
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 1.2rem;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .stat-box .num {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
        }

        .stat-box .label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 700;
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            header { flex-direction: column; align-items: stretch; }
            .search-bar { max-width: 100%; }
            th, td { padding: 0.7rem 0.5rem; font-size: 0.8rem; }
            .stats-row { grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
            .stat-box { padding: 0.8rem; }
            .stat-box .num { font-size: 1.1rem; }
            .materia-badge { font-size: 0.65rem; padding: 0.2rem 0.4rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <header>
            <div>
                <h1>Documentos desde Drive</h1>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 0.25rem;">Archivos PDF y Word detectados en las carpetas de Drive</p>
            </div>
            <form class="search-bar" method="GET" action="">
                <i data-lucide="search" size="18"></i>
                <input type="text" name="search" placeholder="Buscar documentos..." value="<?php echo htmlspecialchars($search); ?>" onchange="this.form.submit()">
                <?php if ($search): ?>
                    <a href="drive_docs.php" style="color: var(--text-secondary); display: flex;">
                        <i data-lucide="x" size="16"></i>
                    </a>
                <?php endif; ?>
            </form>
        </header>

        <?php 
        $totalDocs = count($documentos);
        $totalNotificados = 0;
        $totalPendientes = 0;
        foreach ($documentos as $d) {
            if ($d['notificado'] === 't' || $d['notificado'] === true || $d['notificado'] === 1) {
                $totalNotificados++;
            } else {
                $totalPendientes++;
            }
        }
        ?>
        <div class="stats-row">
            <div class="stat-box">
                <div class="num"><?php echo $totalDocs; ?></div>
                <div class="label">Total Documentos</div>
            </div>
            <div class="stat-box">
                <div class="num"><?php echo $totalNotificados; ?></div>
                <div class="label">Notificados</div>
            </div>
            <div class="stat-box">
                <div class="num"><?php echo $totalPendientes; ?></div>
                <div class="label">Pendientes</div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Documento</th>
                        <th>Tipo</th>
                        <th>Detectado</th>
                        <th>Notificado</th>
                        <th style="width: 160px; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documentos)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                    <i data-lucide="hard-drive" size="48" style="opacity: 0.2; margin-bottom: 1rem; display: block; margin-left: auto; margin-right: auto;"></i>
                                    No se encontraron documentos de Drive.
                                </td>
                            </tr>
                    <?php else: ?>
                        <?php foreach ($documentos as $d): 
                            $notificado = ($d['notificado'] === 't' || $d['notificado'] === true || $d['notificado'] === 1);
                            $tipoLower = strtolower($d['tipo']);
                        ?>
                            <tr>
                                <td><span class="materia-badge"><?php echo htmlspecialchars($d['materia_nombre']); ?></span></td>
                                <td class="doc-name"><?php echo htmlspecialchars($d['nombre']); ?></td>
                                <td><span class="tipo-badge tipo-<?php echo $tipoLower; ?>"><?php echo htmlspecialchars($d['tipo']); ?></span></td>
                                <td class="date-text"><?php echo date('d/m/Y H:i', strtotime($d['detectado_en'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $notificado ? 'notified' : 'pending'; ?>">
                                        <?php echo $notificado ? 'Enviado' : 'Pendiente'; ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="actions">
                                        <a href="<?php echo htmlspecialchars($d['enlace']); ?>" target="_blank" class="btn-icon" title="Abrir en Drive">
                                            <i data-lucide="external-link" size="16"></i>
                                        </a>
                                            <button class="btn-icon notify" onclick="notificar(<?php echo $d['id']; ?>)" title="Enviar notificación manual">
                                                <i data-lucide="send" size="16"></i>
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

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        async function notificar(id) {
            if (!confirm('¿Enviar notificación de este documento a todos los suscriptores?')) return;

            const btn = document.querySelector(`button[onclick="notificar(${id})"]`);
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader" size="16"></i>';
            lucide.createIcons();

            try {
                const res = await fetch('api/api_notificar_drive.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Error de conexión.');
            } finally {
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
