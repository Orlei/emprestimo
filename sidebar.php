<?php
// Identifica o nome do arquivo atual para acender o menu correto
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* --- CUSTOMIZAÇÃO BLINDADA DA SIDEBAR --- */
    
    /* Container Principal */
    aside.sidebar {
        width: 260px !important;
        background: linear-gradient(180deg, #000033 0%, #00001a 100%) !important;
        min-height: 100vh !important;
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        z-index: 100 !important;
        display: flex !important;
        flex-direction: column !important;
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15) !important;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif !important;
    }

    /* Topo / Área da Logo */
    aside.sidebar .sidebar-brand {
        padding: 32px 24px 24px 24px !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04) !important;
        background: rgba(0, 0, 0, 0.12) !important;
        text-align: center !important;
    }

    aside.sidebar .sidebar-brand img {
        width: 100% !important;
        max-width: 175px !important;
        height: auto !important;
        display: inline-block !important;
        opacity: 0.95 !important;
    }

    /* Menu / Lista de Links */
    aside.sidebar .sidebar-menu {
        padding: 24px 16px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
        flex-grow: 1 !important;
    }

    /* LINKS DO MENU (Força tamanho idêntico para todos os estados) */
    aside.sidebar .sidebar-menu a.sidebar-link {
        display: flex !important;
        align-items: center !important;
        gap: 14px !important;
        padding: 12px 18px !important;
        color: #94a3b8 !important; /* Cor padrão (Inativo) */
        text-decoration: none !important;
        border-radius: 8px !important;
        
        /* Travas de tamanho e espaçamento */
        font-size: 15px !important; /* Tamanho fixo em pixels para evitar herança */
        font-weight: 500 !important; /* Peso padrão confortável */
        line-height: 1.2 !important;
        white-space: nowrap !important;
        
        transition: all 0.2s ease-in-out !important;
    }

    /* Ícones dos Links */
    aside.sidebar .sidebar-menu a.sidebar-link i {
        font-size: 16px !important; /* Tamanho do ícone fixado */
        width: 24px !important;
        text-align: center !important;
        color: #64748b !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.2s ease-in-out !important;
    }

    /* --- COMPORTAMENTOS (HOVER E ATIVO) --- */

    /* Efeito ao passar o mouse (Hover) */
    aside.sidebar .sidebar-menu a.sidebar-link:hover {
        color: #f8fafc !important;
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
    
    aside.sidebar .sidebar-menu a.sidebar-link:hover i {
        color: #f8fafc !important;
    }

    /* Link da Página Atual (Active) */
    aside.sidebar .sidebar-menu a.sidebar-link.active {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.1) !important;
        
        /* Mantém rigorosamente o mesmo tamanho de fonte do link inativo */
        font-size: 15px !important; 
        font-weight: 600 !important; /* Apenas um leve ganho de nitidez, sem alterar o tamanho do bloco */
    }

    aside.sidebar .sidebar-menu a.sidebar-link.active i {
        color: #ffffff !important;
    }
</style>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="logo_unioeste.png" alt="Logo UNIOESTE" style="filter: brightness(0) invert(1);">
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-link <?= ($pagina_atual == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        <a href="itens.php" class="sidebar-link <?= ($pagina_atual == 'itens.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-layer-group"></i> Inventário de Itens
        </a>
        <a href="emprestimos.php" class="sidebar-link <?= ($pagina_atual == 'emprestimos.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-handshake-angle"></i> Novos Empréstimos
        </a>
        <a href="devolucoes.php" class="sidebar-link <?= ($pagina_atual == 'devolucoes.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-clock-rotate-left"></i> Devoluções
        </a>
        <a href="setores.php" class="sidebar-link <?= ($pagina_atual == 'setores.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-sitemap"></i> Gestão de Setores
        </a>
        <a href="funcionarios.php" class="sidebar-link <?= ($pagina_atual == 'funcionarios.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-address-card"></i> Funcionários
        </a>
        <a href="usuarios.php" class="sidebar-link <?= ($pagina_atual == 'usuarios.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users-gear"></i> Operadores
        </a>
    </div>
</aside>