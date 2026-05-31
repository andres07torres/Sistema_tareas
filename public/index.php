<?php
require_once '../config/database.php';
$db = (new Database())->getConnection();

// Estadísticas rápidas
$total_pendientes = $db->query("SELECT COUNT(*) FROM tareas WHERE estado = 'pendiente'")->fetchColumn();
$total_hoy = $db->query("SELECT COUNT(*) FROM tareas WHERE estado = 'pendiente' AND fecha_entrega = CURRENT_DATE")->fetchColumn();
$total_semana = $db->query("SELECT COUNT(*) FROM tareas WHERE estado = 'pendiente' AND fecha_entrega BETWEEN CURRENT_DATE AND (CURRENT_DATE + INTERVAL '7 days')")->fetchColumn();

// Próximas 5 tareas
$stmt = $db->prepare("SELECT * FROM tareas WHERE estado = 'pendiente' ORDER BY fecha_entrega ASC LIMIT 5");
$stmt->execute();
$proximas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tareas por materia (Top 3)
$materia_stats = $db->query("SELECT materia, COUNT(*) as total FROM tareas WHERE estado = 'pendiente' GROUP BY materia ORDER BY total DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Asistente de Tareas</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/css/base.css">
    <style>
        /* Stats Grid */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 50px; height: 50px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }

        .icon-blue { background: #e7f1ff; color: #0d6efd; }
        .icon-orange { background: #fff8e1; color: #856404; }
        .icon-purple { background: #f3e8ff; color: #7c3aed; }

        .stat-content .label { font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-content .value { font-size: 2rem; font-weight: 800; color: var(--text-primary); margin-top: 0.25rem; }

        /* Dashboard Layout */
        .main-grid { 
            display: grid; 
            grid-template-columns: 1.6fr 1fr; 
            gap: 2rem;
        }

        .card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .card-header h2 { font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 0.75rem; }

        /* Task List */
        .task-list { display: flex; flex-direction: column; gap: 1rem; }
        .task-item {
            background: #f0f2f5;
            padding: 1.25rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .task-item:hover {
            background: white;
            border-color: var(--border-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        .task-info { display: flex; flex-direction: column; gap: 0.4rem; }
        .task-title { font-weight: 700; color: var(--text-primary); font-size: 1.05rem; }
        .task-materia { font-size: 0.8rem; color: var(--accent-blue); font-weight: 700; }
        
        .task-date { 
            display: flex; align-items: center; gap: 0.4rem; 
            font-size: 0.85rem; color: var(--text-secondary); 
            background: white; padding: 0.4rem 0.75rem; border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        /* Subjects Section */
        .subject-card {
            background: white;
            padding: 1.25rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .subject-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        .subject-info { display: flex; align-items: center; gap: 1rem; }
        .subject-initials {
            width: 40px; height: 40px; border-radius: 10px;
            background: #f0f2f5; color: var(--text-primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.8rem;
        }

        .progress-container { width: 100%; height: 6px; background: #f0f2f5; border-radius: 10px; margin-top: 0.5rem; overflow: hidden; }
        .progress-bar { height: 100%; background: var(--accent-blue); border-radius: 10px; }

        .btn-action {
            padding: 0.5rem 1.2rem;
            margin: 1.5rem auto 0;
            border: none; border-radius: 8px;
            background: #203145; color: white;
            font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            width: fit-content;
        }
        .btn-action:hover { filter: brightness(1.1); }

        @media (max-width: 900px) {
            .main-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 600px) {
            .container { padding: 1.5rem 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i data-lucide="layers"></i></div>
                <div class="stat-content">
                    <div class="label">Total Pendientes</div>
                    <div class="value"><?php echo $total_pendientes; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i data-lucide="alert-circle"></i></div>
                <div class="stat-content">
                    <div class="label">Para Hoy</div>
                    <div class="value"><?php echo $total_hoy; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i data-lucide="calendar-check"></i></div>
                <div class="stat-content">
                    <div class="label">Esta Semana</div>
                    <div class="value"><?php echo $total_semana; ?></div>
                </div>
            </div>
        </div>

        <div class="main-grid">
            <!-- Left Column: Upcoming Tasks -->
            <div class="card">
                <div class="card-header">
                    <h2>Tareas Programadas</h2>
                    <a href="actividades.php" style="font-size: 0.85rem; font-weight: 700; color: var(--accent-blue); text-decoration: none;">Ver todo</a>
                </div>
                
                <div class="task-list">
                    <?php if (empty($proximas)): ?>
                        <div style="text-align: center; padding: 3rem 0;">
                            <i data-lucide="party-popper" size="48" style="opacity: 0.2; margin-bottom: 1rem;"></i>
                            <p style="color: var(--text-secondary);">¡Excelente trabajo! No tienes tareas pendientes.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($proximas as $t): 
                            $dias = (strtotime($t['fecha_entrega']) - strtotime(date('Y-m-d'))) / 86400;
                            $urgencyColor = ($dias <= 1) ? 'var(--danger)' : (($dias <= 3) ? 'var(--warning)' : 'var(--success)');
                        ?>
                            <div class="task-item">
                                <div class="task-info">
                                    <div class="task-materia"><?php echo htmlspecialchars($t['materia']); ?></div>
                                    <div class="task-title"><?php echo htmlspecialchars($t['titulo']); ?></div>
                                    <div style="display: flex; gap: 0.5rem; margin-top: 0.4rem;">
                                        <div class="task-date">
                                            <i data-lucide="calendar" size="14"></i>
                                            <?php echo date('d M', strtotime($t['fecha_entrega'])); ?>
                                        </div>
                                        <div class="task-date" style="color: <?php echo $urgencyColor; ?>; font-weight: 800;">
                                            <i data-lucide="clock" size="14"></i>
                                            <?php echo ($dias == 0) ? 'Hoy' : (($dias == 1) ? 'Mañana' : "$dias días"); ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 0.65rem; font-weight: 800; background: #e7f1ff; color: #0d6efd; padding: 0.3rem 0.6rem; border-radius: 6px; text-transform: uppercase;">
                                        <?php echo $t['tipo']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Materials & Actions -->
            <div class="side-content">
                <div class="card">
                    <div class="card-header">
                        <h2><i data-lucide="book-open"></i> Materias</h2>
                    </div>
                    <?php foreach ($materia_stats as $ms): 
                        $initials = substr($ms['materia'], 0, 2);
                        $percentage = ($total_pendientes > 0) ? ($ms['total'] / $total_pendientes) * 100 : 0;
                    ?>
                        <div class="subject-card">
                            <div class="subject-info">
                                <div class="subject-initials"><?php echo $initials; ?></div>
                                <div>
                                    <div style="font-weight: 700; font-size: 0.85rem; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo $ms['materia']; ?>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div style="font-weight: 800; color: var(--text-secondary);"><?php echo $ms['total']; ?></div>
                        </div>
                    <?php endforeach; ?>

                    <a href="materias.php" class="btn-action">
                        <i data-lucide="settings" size="18"></i> Gestionar Materias
                    </a>
                </div>

                <div class="card" style="margin-top: 2rem;">
                    <h3 style="font-size: 1rem; font-weight: 800; margin-bottom: 0.5rem;">¿Nuevo Semestre?</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">Importa todas tus actividades rápidamente usando un archivo CSV.</p>
                    <a href="actividades.php" class="btn-action">
                        <i data-lucide="file-up" size="18"></i> Subir Archivo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>