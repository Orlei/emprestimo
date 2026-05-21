<?php
// Identifica o nome do arquivo atual para acender o menu correto
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>

<style>
    * {
        box-sizing: border-box;
    }

    /* SIDEBAR */
    aside.sidebar {
        width: 260px;
        background: linear-gradient(180deg, #000033 0%, #00001a 100%);
        min-height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        transition: transform 0.3s ease;
    }

    /* TOPO */
    .sidebar-brand {
        padding: 32px 24px 24px;
        border-bottom: 1px solid rgba(255,255,255,0.04);
        background: rgba(0,0,0,0.12);
        text-align: center;
    }

    .sidebar-brand img {
        width: 100%;
        max-width: 175px;
        height: auto;
        opacity: 0.95;
    }

    /* MENU */
    .sidebar-menu {
        padding: 24px 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex-grow: 1;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 18px;
        color: #94a3b8;
        text-decoration: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 500;
        line-height: 1.2;
        transition: all 0.2s ease-in-out;
    }

    .sidebar-link i {
        font-size: 16px;
        width: 24px;
        text-align: center;
        color: #64748b;
        transition: all 0.2s ease-in-out;
    }

    /* HOVER */
    .sidebar-link:hover {
        color: #f8fafc;
        background-color: rgba(255,255,255,0.05);
    }

    .sidebar-link:hover i {
        color: #f8fafc;
    }

    /* ACTIVE */
    .sidebar-link.active {
        color: #ffffff;
        background-color: rgba(255,255,255,0.1);
        font-weight: 600;
    }

    .sidebar-link.active i {
        color: #ffffff;
    }

    /* BOTÃO MOBILE */
    .menu-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1101;
        background: #000033;
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 10px;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    /* OVERLAY */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }

    .sidebar-overlay.active {
        display: block;
    }

    /* RESPONSIVO */
    @media (max-width: 768px) {

        aside.sidebar {
            transform: translateX(-100%);
        }

        aside.sidebar.open {
            transform: translateX(0);
        }

        .menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-brand {
            padding-top: 80px;
        }

        .sidebar-link {
            font-size: 14px;
        }
    }
</style>

<!-- BOTÃO MOBILE -->
<button class="menu-toggle" onclick="toggleSidebar()">
    <i class="fa-solid fa-bars"></i>
</button>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <img 
            src="logo_unioeste.png" 
            alt="Logo UNIOESTE"
            style="filter: brightness(0) invert(1);"
        >
    </div>

    <div class="sidebar-menu">

        <a href="dashboard.php" class="sidebar-link <?= ($pagina_atual == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-pie"></i>
            Dashboard
        </a>

        <a href="itens.php" class="sidebar-link <?= ($pagina_atual == 'itens.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-layer-group"></i>
            Inventário de Itens
        </a>

        <a href="emprestimos.php" class="sidebar-link <?= ($pagina_atual == 'emprestimos.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-handshake-angle"></i>
            Novos Empréstimos
        </a>

        <a href="devolucoes.php" class="sidebar-link <?= ($pagina_atual == 'devolucoes.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Devoluções
        </a>

        <a href="setores.php" class="sidebar-link <?= ($pagina_atual == 'setores.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-sitemap"></i>
            Gestão de Setores
        </a>

        <a href="funcionarios.php" class="sidebar-link <?= ($pagina_atual == 'funcionarios.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-address-card"></i>
            Funcionários
        </a>

        <a href="usuarios.php" class="sidebar-link <?= ($pagina_atual == 'usuarios.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users-gear"></i>
            Operadores
        </a>

    </div>
</aside>

<script>
    function toggleSidebar() {

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    }

    // Fecha automaticamente ao redimensionar para desktop
    window.addEventListener('resize', function () {

        if (window.innerWidth > 768) {

            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }
    });
</script>