<nav style="
    background: #203145; 
    padding: 1.5rem 1rem; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
    margin-bottom: 2rem; 
    display: flex; 
    justify-content: center; 
    gap: 2rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
">
    <a href="index.php" style="
        text-decoration: none; 
        color: #e2e8f0; 
        font-weight: 600; 
        display: flex; 
        align-items: center; 
        gap: 0.6rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        padding: 0.5rem 1rem;
        border-radius: 8px;
    " onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='#fff'" onmouseout="this.style.background='transparent'; this.style.color='#e2e8f0'">
        <i data-lucide="plus-circle" style="width: 20px;"></i>
        Añadir Tarea
    </a>
    <a href="vencimientos.php" style="
        text-decoration: none; 
        color: #e2e8f0; 
        font-weight: 600; 
        display: flex; 
        align-items: center; 
        gap: 0.6rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        padding: 0.5rem 1rem;
        border-radius: 8px;
    " onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='#fff'" onmouseout="this.style.background='transparent'; this.style.color='#e2e8f0'">
        <i data-lucide="layout-dashboard" style="width: 20px;"></i>
        Próximas Entregas
    </a>
    <a href="actividades.php" style="
        text-decoration: none; 
        color: #e2e8f0; 
        font-weight: 600; 
        display: flex; 
        align-items: center; 
        gap: 0.6rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        padding: 0.5rem 1rem;
        border-radius: 8px;
    " onmouseover="this.style.background='rgba(255,255,255,0.1)'; this.style.color='#fff'" onmouseout="this.style.background='transparent'; this.style.color='#e2e8f0'">
        <i data-lucide="list-todo" style="width: 20px;"></i>
        Gestionar Actividades
    </a>
</nav>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
    // Resaltar link activo
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('nav a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.style.background = 'rgba(255,255,255,0.15)';
            link.style.color = '#fff';
            link.style.boxShadow = 'inset 0 0 0 1px rgba(255,255,255,0.2)';
        }
    });
</script>
