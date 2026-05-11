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
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --accent: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.9);
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            font-family: 'Outfit', sans-serif; 
            background: #f0f2f5;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(139, 92, 246, 0.05) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 3rem 2rem;
            border-radius: 24px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 2.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: fadeInDown 0.8s ease-out;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
            filter: blur(40px);
        }

        .hero h1 { font-size: 2.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem; }
        .hero p { font-size: 1.1rem; opacity: 0.8; font-weight: 300; }

        /* Stats Grid */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 1.5rem; 
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.8s ease-out 0.2s backwards;
        }

        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 1.75rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px; height: 50px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }

        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-orange { background: #fff7ed; color: #f97316; }
        .icon-purple { background: #faf5ff; color: #a855f7; }

        .stat-content .label { font-size: 0.875rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-content .value { font-size: 2rem; font-weight: 800; color: var(--text-main); margin-top: 0.25rem; }

        /* Dashboard Layout */
        .main-grid { 
            display: grid; 
            grid-template-columns: 1.6fr 1fr; 
            gap: 2rem; 
            animation: fadeInUp 0.8s ease-out 0.4s backwards;
        }

        .card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .card-header h2 { font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 0.75rem; }

        /* Task List */
        .task-list { display: flex; flex-direction: column; gap: 1rem; }
        .task-item {
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .task-item:hover {
            background: white;
            border-color: #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: scale(1.01);
        }

        .task-info { display: flex; flex-direction: column; gap: 0.4rem; }
        .task-title { font-weight: 700; color: var(--text-main); font-size: 1.05rem; }
        .task-materia { font-size: 0.8rem; color: var(--primary); font-weight: 700; }
        
        .task-date { 
            display: flex; align-items: center; gap: 0.4rem; 
            font-size: 0.85rem; color: var(--text-muted); 
            background: white; padding: 0.4rem 0.75rem; border-radius: 10px;
            border: 1px solid #f1f5f9;
        }

        /* Subjects Section */
        .subject-card {
            background: white;
            padding: 1.25rem;
            border-radius: 16px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #f1f5f9;
        }

        .subject-info { display: flex; align-items: center; gap: 1rem; }
        .subject-initials {
            width: 40px; height: 40px; border-radius: 10px;
            background: #f1f5f9; color: var(--text-main);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.8rem;
        }

        .progress-container { width: 100%; height: 6px; background: #f1f5f9; border-radius: 10px; margin-top: 0.5rem; overflow: hidden; }
        .progress-bar { height: 100%; background: var(--primary); border-radius: 10px; transition: width 1s ease; }

        .btn-action {
            width: 100%; padding: 1rem; margin-top: 1.5rem;
            border: none; border-radius: 14px;
            background: #f1f5f9; color: var(--text-main);
            font-weight: 700; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            text-decoration: none;
        }
        .btn-action:hover { background: var(--text-main); color: white; }

        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 900px) {
            .main-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 600px) {
            .hero { padding: 2rem 1.5rem; }
            .hero h1 { font-size: 1.75rem; }
            .container { padding: 1.5rem 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <!-- Hero Section -->
        <div class="hero">
            <div style="position: relative; z-index: 2;">
                <h1>¡Hola de nuevo!</h1>
                <p>Tienes <strong><?php echo $total_pendientes; ?></strong> actividades pendientes en total. ¡Tú puedes!</p>
            </div>
        </div>

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
                    <h2><i data-lucide="sparkles" style="color: #f59e0b;"></i> Próximos Desafíos</h2>
                    <a href="actividades.php" style="font-size: 0.85rem; font-weight: 700; color: var(--primary); text-decoration: none;">Ver todo</a>
                </div>
                
                <div class="task-list">
                    <?php if (empty($proximas)): ?>
                        <div style="text-align: center; padding: 3rem 0;">
                            <i data-lucide="party-popper" size="48" style="opacity: 0.2; margin-bottom: 1rem;"></i>
                            <p style="color: var(--text-muted);">¡Excelente trabajo! No tienes tareas pendientes.</p>
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
                                    <span style="font-size: 0.65rem; font-weight: 800; background: #eff6ff; color: #3b82f6; padding: 0.3rem 0.6rem; border-radius: 6px; text-transform: uppercase;">
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
                        <h2><i data-lucide="book-open"></i> Materias Top</h2>
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
                            <div style="font-weight: 800; color: var(--text-muted);"><?php echo $ms['total']; ?></div>
                        </div>
                    <?php endforeach; ?>

                    <a href="actividades.php" class="btn-action">
                        <i data-lucide="settings" size="18"></i> Gestionar Todo
                    </a>
                </div>

                <div class="card" style="margin-top: 2rem; background: linear-gradient(135deg, #eff6ff 0%, #faf5ff 100%); border: none;">
                    <h3 style="font-size: 1rem; font-weight: 800; margin-bottom: 0.5rem;">¿Nuevo Semestre?</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Importa todas tus actividades rápidamente usando un archivo CSV.</p>
                    <a href="actividades.php" class="btn-action" style="background: white;">
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