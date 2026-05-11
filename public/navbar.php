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

    /* Estilos base de la navegación */
    .main-nav {
        background: #203145;
        padding: 0.5rem 1rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        position: relative;
        z-index: 1000;
        min-height: 70px;
    }

    .nav-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #fff;
        text-decoration: none;
        font-weight: 800;
        font-size: 1.2rem;
        letter-spacing: -0.02em;
    }

    .nav-brand i {
        color: #3b82f6;
    }

    .nav-links {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .main-nav a.nav-link {
        text-decoration: none;
        color: #e2e8f0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        padding: 0.6rem 1rem;
        border-radius: 8px;
        white-space: nowrap;
    }

    .main-nav a.nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }

    .menu-toggle {
        display: none;
        background: none;
        border: none;
        color: #fff;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: background 0.2s;
    }

    .menu-toggle:hover {
        background: rgba(255,255,255,0.1);
    }

    /* Responsividad para móviles */
    @media (max-width: 850px) {
        .menu-toggle {
            display: block;
        }

        .nav-links {
            display: none; /* Oculto por defecto en móvil */
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: #203145;
            padding: 1rem;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.05);
            gap: 0.5rem;
        }

        .nav-links.active {
            display: flex;
            animation: slideDown 0.3s ease forwards;
        }

        .main-nav a.nav-link {
            width: 100%;
            padding: 1rem;
            justify-content: flex-start;
            font-size: 1rem;
        }
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Ajustes globales de tipografía móvil */
    @media (max-width: 600px) {
        h1 { font-size: 1.4rem !important; }
        h2 { font-size: 1.2rem !important; }
        p, span, label, input, select, textarea, td, th, .btn-primary, .btn-danger, .btn-csv { 
            font-size: 0.85rem !important; 
        }
        .nav-brand { font-size: 1.1rem !important; }
        .nav-brand i { width: 20px; height: 20px; }
        .container { padding: 1rem 0.75rem !important; }
    }
</style>

<nav class="main-nav">
    <a href="index.php" class="nav-brand">
        <i data-lucide="check-check"></i>
        <span>Asistente UNEMI</span>
    </a>

    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
        <i data-lucide="menu" id="menuIcon"></i>
    </button>

    <div class="nav-links" id="navLinks">
        <a href="index.php" class="nav-link">
            <i data-lucide="plus-circle"></i>
            <span>Añadir Tarea</span>
        </a>
        <a href="vencimientos.php" class="nav-link">
            <i data-lucide="layout-dashboard"></i>
            <span>Próximas Entregas</span>
        </a>
        <a href="actividades.php" class="nav-link">
            <i data-lucide="list-todo"></i>
            <span>Gestionar Actividades</span>
        </a>
    </div>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    // Inicializar iconos
    lucide.createIcons();

    // Lógica del menú hamburguesa
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    const menuIcon = document.getElementById('menuIcon');

    menuToggle.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        const isActive = navLinks.classList.contains('active');
        
        // Cambiar icono entre menu y x
        menuIcon.setAttribute('data-lucide', isActive ? 'x' : 'menu');
        lucide.createIcons();
    });

    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!menuToggle.contains(e.target) && !navLinks.contains(e.target) && navLinks.classList.contains('active')) {
            navLinks.classList.remove('active');
            menuIcon.setAttribute('data-lucide', 'menu');
            lucide.createIcons();
        }
    });

    // Resaltar link activo
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.nav-links a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.style.background = 'rgba(255,255,255,0.15)';
            link.style.color = '#fff';
            link.style.boxShadow = 'inset 0 0 0 1px rgba(255,255,255,0.2)';
        }
    });
</script>
