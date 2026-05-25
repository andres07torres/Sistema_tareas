<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/navbar.css">

<nav class="main-nav">
    <a href="index.php" class="nav-brand">
        <i data-lucide="check-check"></i>
        <span>Asistente de Tareas</span>
    </a>

    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
        <i data-lucide="menu" class="icon-menu"></i>
        <i data-lucide="x" class="icon-close" style="display: none;"></i>
    </button>

    <div class="nav-links" id="navLinks">
        <a href="index.php" class="nav-link">
            <i data-lucide="layout-dashboard"></i>
            <span>Panel de Control</span>
        </a>
        <a href="actividades.php" class="nav-link">
            <i data-lucide="list-todo"></i>
            <span>Gestionar Actividades</span>
        </a>
        <a href="materias.php" class="nav-link">
            <i data-lucide="book"></i>
            <span>Materias</span>
        </a>
        <a href="vencimientos.php" class="nav-link">
            <i data-lucide="calendar-clock"></i>
            <span>Próximas Entregas</span>
        </a>
    </div>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();

        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');
        const iconMenu = menuToggle.querySelector('.icon-menu');
        const iconClose = menuToggle.querySelector('.icon-close');

        function toggleMenu(forceClose = false) {
            const isOpening = forceClose ? false : !navLinks.classList.contains('active');

            if (isOpening) {
                navLinks.classList.add('active');
                iconMenu.style.display = 'none';
                iconClose.style.display = 'block';
            } else {
                navLinks.classList.remove('active');
                iconMenu.style.display = 'block';
                iconClose.style.display = 'none';
            }
        }

        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMenu();
        });

        document.addEventListener('click', (e) => {
            if (navLinks.classList.contains('active') && !navLinks.contains(e.target) && !menuToggle.contains(e.target)) {
                toggleMenu(true);
            }
        });

        const currentPath = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('.nav-links a').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.style.background = 'rgba(255,255,255,0.15)';
                link.style.color = '#fff';
                link.style.boxShadow = 'inset 0 0 0 1px rgba(255,255,255,0.2)';
            }
        });
    });
</script>
