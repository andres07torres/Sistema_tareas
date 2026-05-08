<nav style="
    background: white; 
    padding: 1rem; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
    margin-bottom: 2rem; 
    display: flex; 
    justify-content: center; 
    gap: 1.5rem;
    border-bottom: 1px solid #dddfe2;
">
    <a href="index.php" style="
        text-decoration: none; 
        color: #65676b; 
        font-weight: 600; 
        display: flex; 
        align-items: center; 
        gap: 0.5rem;
        font-size: 0.95rem;
        transition: color 0.2s;
    " onmouseover="this.style.color='#0d6efd'" onmouseout="this.style.color='#65676b'">
        <i data-lucide="plus-circle" style="width: 18px;"></i>
        Añadir Tarea
    </a>
    <a href="vencimientos.php" style="
        text-decoration: none; 
        color: #65676b; 
        font-weight: 600; 
        display: flex; 
        align-items: center; 
        gap: 0.5rem;
        font-size: 0.95rem;
        transition: color 0.2s;
    " onmouseover="this.style.color='#0d6efd'" onmouseout="this.style.color='#65676b'">
        <i data-lucide="layout-dashboard" style="width: 18px;"></i>
        Próximas Entregas
    </a>
</nav>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
    // Resaltar link activo
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('nav a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.style.color = '#0d6efd';
            link.style.borderBottom = '2px solid #0d6efd';
            link.style.paddingBottom = '2px';
        }
    });
</script>
