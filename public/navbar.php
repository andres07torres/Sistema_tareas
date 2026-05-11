<style>
    /* Reset global para evitar desbordamientos */
    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        overflow-x: hidden;
        box-sizing: border-box;
    }

    *, *:before, *:after {
        box-sizing: inherit;
    }

    .main-nav {
        background: #203145;
        padding: 1rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        display: flex;
        justify-content: center;
        gap: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        flex-wrap: wrap;
        width: 100%;
    }

    .main-nav a {
        text-decoration: none;
        color: #e2e8f0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        white-space: nowrap;
    }

    .main-nav a:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }

    /* Responsividad para móviles */
    @media (max-width: 768px) {
        .main-nav {
            gap: 0.5rem;
            padding: 0.5rem;
        }
        .main-nav a {
            font-size: 0.9rem;
            padding: 0.6rem 0.8rem;
            flex: 1 1 calc(50% - 0.5rem);
            justify-content: center;
        }
        .main-nav a:last-child {
            flex: 1 1 100%;
        }
    }

    @media (max-width: 480px) {
        .main-nav a {
            flex: 1 1 100%;
        }
    }
</style>

<nav class="main-nav">
    <a href="index.php">
        <i data-lucide="plus-circle"></i>
        <span>Añadir Tarea</span>
    </a>
    <a href="vencimientos.php">
        <i data-lucide="layout-dashboard"></i>
        <span>Próximas Entregas</span>
    </a>
    <a href="actividades.php">
        <i data-lucide="list-todo"></i>
        <span>Gestionar Actividades</span>
    </a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
    // Resaltar link activo
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.main-nav a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active-link');
            // Estilo extra para el activo (podría estar en el CSS pero lo mantenemos dinámico)
            link.style.background = 'rgba(255,255,255,0.15)';
            link.style.color = '#fff';
            link.style.boxShadow = 'inset 0 0 0 1px rgba(255,255,255,0.2)';
        }
    });
</script>
