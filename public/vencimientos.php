<?php
require_once '../config/database.php';

$db = (new Database())->getConnection();

// Obtener tareas que vencen en los próximos 7 días
$query = "SELECT *, (fecha_entrega - CURRENT_DATE) as dias_restantes 
          FROM tareas 
          WHERE estado = 'pendiente' 
          AND (fecha_entrega - CURRENT_DATE) BETWEEN 0 AND 7
          ORDER BY fecha_entrega ASC, materia ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por días restantes para la UI
$agrupadas = [];
foreach ($tareas as $t) {
    $dias = $t['dias_restantes'];
    $label = ($dias == 0) ? "Vence Hoy" : (($dias == 1) ? "Vence Mañana" : "Vence en $dias días");
    $agrupadas[$label][] = $t;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vencimientos Próximos | Asistente de Tareas</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/css/base.css">
    <style>
        .container {
            max-width: 600px;
        }

        header {
            margin-bottom: 2rem;
            text-align: left;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-top: 0.25rem;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            margin: 2rem 0 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .task-list {
            display: grid;
            gap: 1rem;
        }

        .task-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            border-left: 5px solid var(--accent-blue);
            transition: all 0.2s ease;
        }

        .task-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .task-card.urgent { border-left-color: var(--accent-urgent); }
        .task-card.warning { border-left-color: var(--accent-warning); }

        .materia {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--accent-blue);
            margin-bottom: 0.25rem;
        }

        .titulo {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1c1e21;
        }

        .descripcion {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            padding-left: 0.5rem;
            border-left: 2px solid #eee;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
            border-top: 1px solid #f0f2f5;
            padding-top: 0.75rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .badge {
            margin-left: auto;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            background: #f0f2f5;
            color: #4b4f56;
        }

        .task-card.urgent .badge { background: #fbe7e9; color: var(--accent-urgent); }
        .task-card.warning .badge { background: #fff8e1; color: #856404; }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }

        .empty-state:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        @media (max-width: 640px) {
            body { padding: 1rem 0.5rem; }
            h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <header>
            <div>
                <h1>Próximas Entregas</h1>
                <p class="subtitle">Visualización de tareas para los próximos 7 días</p>
            </div>
        </header>

        <?php if (empty($tareas)): ?>
            <div class="empty-state">
                <i data-lucide="party-popper" size="48"></i>
                <h3>¡Todo al día!</h3>
                <p>No tienes tareas pendientes para los próximos 7 días.</p>
            </div>
        <?php else: ?>
            <?php foreach ($agrupadas as $label => $tasks): ?>
                <div class="section-title">
                    <i data-lucide="calendar"></i>
                    <?php echo $label; ?>
                </div>
                <div class="task-list">
                    <?php foreach ($tasks as $t): 
                        $isUrgent = ($t['dias_restantes'] <= 1);
                        $isWarning = ($t['dias_restantes'] <= 3 && $t['dias_restantes'] > 1);
                        $cardClass = $isUrgent ? 'urgent' : ($isWarning ? 'warning' : '');
                        $icon = ($t['tipo'] == 'test') ? 'graduation-cap' : 'file-text';
                    ?>
                        <div class="task-card <?php echo $cardClass; ?>">
                            <div class="materia"><?php echo htmlspecialchars($t['materia'] ?? ''); ?></div>
                            <div class="titulo"><?php echo htmlspecialchars($t['titulo'] ?? ''); ?></div>
                            <?php if (!empty($t['descripcion'])): ?>
                                <div class="descripcion"><?php echo htmlspecialchars($t['descripcion'] ?? ''); ?></div>
                            <?php endif; ?>
                            <div class="meta">
                                <div class="meta-item">
                                    <i data-lucide="<?php echo $icon; ?>" size="16"></i>
                                    <span><?php echo ($t['tipo'] == 'test') ? 'Test / Lección' : 'Tarea'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i data-lucide="clock" size="16"></i>
                                    <span>Entrega: <?php echo date('d M, Y', strtotime($t['fecha_entrega'])); ?></span>
                                </div>
                                <div class="badge">
                                    <?php echo $t['dias_restantes'] == 0 ? '¡Hoy!' : ($t['dias_restantes'] == 1 ? 'Mañana' : $t['dias_restantes'] . ' días rest.'); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
