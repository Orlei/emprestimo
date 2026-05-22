<?php
require_once 'encerra_sessao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- CONEXÃO COM O BANCO DE DADOS (Adicionado para resolver o erro) ---
$host = "127.0.0.1";
$user = "root";
$pass = "7!5JJTBpIoZb.5t!";
$db   = "atp";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Falha na conexão com o banco: " . $conn->connect_error);
}

$usuario = $_SESSION['usuario'] ?? 'Operador';
$nome_completo = $_SESSION['nome'] ?? 'Usuário';

$tempo_restante_inicial = isset($_SESSION['tempo_limite_total'], $_SESSION['ultimo_clique']) 
    ? $_SESSION['tempo_limite_total'] - (time() - $_SESSION['ultimo_clique']) 
    : 1800;

// Busca as últimas 3 movimentações para o painel
$ultimas_movimentacoes = $conn->query("
    SELECT e.*, i.nome_item 
    FROM emprestimos e 
    INNER JOIN itens i ON e.id_item = i.id 
    ORDER BY e.data_emprestimo DESC 
    LIMIT 3
");

function formatarDataAmigavel($data_string) {
    $timestamp = strtotime($data_string);
    $data_base = date('Y-m-d', $timestamp);
    $hoje = date('Y-m-d');
    $ontem = date('Y-m-d', strtotime('-1 day'));
    
    $hora = date('H:i', $timestamp);
    
    if ($data_base === $hoje) {
        return "Hoje, " . $hora;
    } elseif ($data_base === $ontem) {
        return "Ontem, " . $hora;
    } else {
        // Se for mais antigo, exibe a data formatada normal em PT-BR
        $meses = ['01'=>'Jan', '02'=>'Fev', '03'=>'Mar', '04'=>'Abr', '05'=>'Mai', '06'=>'Jun', '07'=>'Jul', '08'=>'Ago', '09'=>'Set', '10'=>'Out', '11'=>'Nov', '12'=>'Dez'];
        return date('d', $timestamp) . '/' . $meses[date('m', $timestamp)] . ', ' . $hora;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - UNIOESTE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --unioeste-navy: #000033;
            --unioeste-blue: #0b2265;
            --unioeste-wine: #800020;
            --sidebar-width: 260px;
            --bg-main: #f4f6f9;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --card-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .botao-devolucao {
            color: var(--unioeste-wine);
            border-color: var(--unioeste-wine);
            background-color: #ffffff;
            transition: var(--transition-smooth);
        }

        .botao-devolucao:hover {
            color: #ffffff !important;
            background-color: var(--unioeste-wine) !important;
            border-color: var(--unioeste-wine) !important;
        }

        .botao-devolucao:hover i {
            color: #ffffff !important;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        /* --- CONTEÚDO PRINCIPAL --- */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* Topbar */
        .topbar {
            background-color: #ffffff;
            height: auto;
            min-height: 70px;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 10px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-sidebar-toggle {
            display: none;
            background: none;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            color: #475569;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .content-body {
            padding: 32px;
            flex-grow: 1;
        }

        /* KPI Cards */
        .kpi-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--card-shadow);
            height: 100%;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            background-color: rgba(11, 34, 101, 0.05);
            color: var(--unioeste-blue);
            flex-shrink: 0;
        }

        /* Mural */
        .mural-container {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 24px;
            box-shadow: var(--card-shadow);
        }

        .notice-card {
            border-left: 4px solid var(--unioeste-wine);
            background-color: #fffbfd;
            border-radius: 0 6px 6px 0;
            padding: 16px;
        }

        footer {
            background-color: #ffffff;
            border-top: 1px solid #e5e7eb;
            font-size: 0.85rem;
            color: #475569;
            padding: 16px 32px;
            margin-top: auto;
        }

        .timer-alerta {
            color: #ef4444 !important;
            animation: piscar 1s infinite;
            font-weight: bold;
        }

        @keyframes piscar { 50% { opacity: 0.5; } }

        /* RESPONSIVIDADE */
        @media (max-width: 991.98px) {
            .content-body { padding: 20px; }
            footer { padding: 14px 20px; }
        }

        @media (max-width: 768px) {
            .main-wrapper { margin-left: 0; width: 100%; }
            .btn-sidebar-toggle { display: flex; align-items: center; }
            .content-body { padding: 16px; }
            footer { padding: 12px 16px; }
            .topbar-nome-completo { display: none; }
            .btn-operacao {
                font-size: 0.9rem;
                padding-top: 0.65rem !important;
                padding-bottom: 0.65rem !important;
            }
        }

        @media (max-width: 480px) {
            .topbar { padding: 10px 14px; }
            .topbar h5 { font-size: 0.95rem; }
            .table th, .table td { padding: 10px 8px; font-size: 0.82rem; }
            .table th:nth-child(3), .table td:nth-child(3) { display: none; }
            footer .footer-suporte { display: none; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>
    
    <div class="main-wrapper">

        <header class="topbar">
            <div class="topbar-left">
                <button class="btn-sidebar-toggle" id="btnSidebarToggle" aria-label="Abrir menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h5 class="fw-bold m-0" style="color: var(--unioeste-blue);">Sistema de Controle de Empréstimos</h5>
            </div>

            <div class="topbar-actions">
                <div class="text-end">
                    <span class="fw-bold text-dark d-block topbar-nome-completo" style="font-size: 0.9rem;">
                        <i class="fa-regular fa-user me-1 text-muted"></i> <?= htmlspecialchars($nome_completo) ?> (<?= htmlspecialchars($usuario) ?>)
                    </span>
                    <small class="text-muted" style="font-size: 0.8rem; font-weight: 500;">
                        Sessão: <span id="cronometro">--:--</span>
                    </small>
                </div>
                <div class="d-none d-sm-block" style="border-left: 1px solid #e2e8f0; height: 30px;"></div>
                <a href="logout.php" class="btn btn-sm btn-outline-danger px-3 fw-semibold">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>
                    <span class="d-none d-sm-inline">Sair</span>
                </a>
            </div>
        </header>

        <main class="content-body">
            <div class="row g-4">
                
                <div class="col-xl-8">
                    
                    <div class="row g-4 mb-4">
                        <div class="col-sm-6">
                            <div class="kpi-card">
                                <div>
                                    <span class="text-muted d-block mb-1" style="font-size: 0.85rem; font-weight: 600;">Data e Hora Atual</span>
                                    <h4 class="fw-bold m-0" id="relogio-digital" style="color: var(--unioeste-blue); font-size: 1.1rem;">--/--/---- --:--:--</h4>
                                </div>
                                <div class="kpi-icon"><i class="fa-regular fa-calendar-days"></i></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="kpi-card">
                                <div>
                                    <span class="text-muted d-block mb-1" style="font-size: 0.85rem; font-weight: 600;">Ambiente</span>
                                    <h4 class="fw-bold m-0" style="font-size: 1.4rem;">Campus MCR</h4>
                                </div>
                                <div class="kpi-icon"><i class="fa-solid fa-location-dot"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <a href="emprestimos.php" class="btn btn-primary btn-operacao w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background-color: var(--unioeste-blue); border: none; border-radius: 10px; font-size: 1.05rem;">
                                    <i class="fa-solid fa-plus fs-5"></i> Registrar Novo Empréstimo
                                </a>
                            </div>
                            <div class="col-sm-6">
                               <a href="emprestimos.php" class="btn btn-operacao w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm botao-devolucao" style="border-radius: 10px; font-size: 1.05rem;">
                                    <i class="fa-solid fa-arrow-rotate-left"></i> Dar Baixa em Devolução
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border rounded-3 p-3 p-md-4 shadow-sm" style="border-color: #e2e8f0 !important;">
                        <div class="d-flex justify-content-between align-items-start align-items-md-center mb-3 gap-2 flex-wrap">
                            <div>
                                <h5 class="fw-bold text-dark m-0">Últimas Movimentações</h5>
                                <p class="text-muted small m-0">Equipamentos pendentes de devolução ou recém movimentados</p>
                            </div>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-2">Monitoramento Ativo</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Equipamento</th>
                                        <th>Retirado por</th>
                                        <th>Setor/Destino</th>
                                        <th>Data de Saída</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($ultimas_movimentacoes && $ultimas_movimentacoes->num_rows > 0): ?>
                                        <?php while($mov = $ultimas_movimentacoes->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($mov['nome_item']) ?></div>
                                                    <small class="text-muted">ID do item: #<?= $mov['id_item'] ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($mov['nome_responsavel']) ?></td>
                                                <td><?= htmlspecialchars($mov['setor_responsavel']) ?></td>
                                                <td><?= formatarDataAmigavel($mov['data_emprestimo']) ?></td>
                                                <td>
                                                    <?php if ($mov['status'] === 'Ativo'): ?>
                                                        <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded small">Pendente</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success px-2 py-1 rounded small">Devolvido</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Nenhum empréstimo registrado até o momento.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <div class="col-xl-4">
                    <div class="mural-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold m-0" style="color: var(--unioeste-blue); font-size: 1rem;">Mural Corporativo</h6>
                            <button class="btn btn-sm btn-dark px-3 fw-semibold shadow-sm" style="background-color: var(--unioeste-blue); border: none; font-size: 0.8rem;">
                                <i class="fa-solid fa-plus me-1"></i> Criar Aviso
                            </button>
                        </div>

                        <div class="notice-card shadow-sm">
                            <span class="fw-bold d-block mb-2" style="font-size: 0.88rem; color: var(--unioeste-wine);">
                                <i class="fa-solid fa-bullhorn me-1"></i> Sistema em Modo Beta!
                            </span>
                            <p class="text-dark mb-3 small" style="line-height: 1.5; font-weight: 500;">
                                O sistema está liberado para testes! Ajude-nos a mapear possíveis melhorias ou inconsistências. Reporte para <strong>rondon.informatica@unioeste.br</strong> ou ramal <strong>7824</strong>.
                            </p>
                            <div class="border-top pt-2" style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">
                                Enviado por: orlei.javorski@unioeste.br
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <footer>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>DIV-INF-MCR &copy; 2026</span>
                <span class="text-dark footer-suporte"><i class="fa-brands fa-whatsapp text-success me-1 fs-6 align-middle"></i> Suporte local: <strong>(45) 3284-7824</strong></span>
            </div>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // 1. CRONÔMETRO DE SESSÃO
        let tempoRestante = <?php echo (int)$tempo_restante_inicial; ?>;
        const display = document.getElementById('cronometro');

        function atualizarCronometro() {
            if (tempoRestante <= 0) {
                window.location.href = "login.php?erro=sessao_expirada";
                return;
            }
            let minutos = Math.floor(tempoRestante / 60);
            let segundos = tempoRestante % 60;
            minutos  = minutos  < 10 ? "0" + minutos  : minutos;
            segundos = segundos < 10 ? "0" + segundos : segundos;
            if (display) {
                display.textContent = minutos + ":" + segundos;
                if (tempoRestante < 300) display.classList.add('timer-alerta');
            }
            tempoRestante--;
        }
        setInterval(atualizarCronometro, 1000);
        atualizarCronometro();

        // 2. RELÓGIO DIGITAL
        function atualizarRelogio() {
            const agora = new Date();
            const dia      = String(agora.getDate()).padStart(2, '0');
            const mes      = String(agora.getMonth() + 1).padStart(2, '0');
            const ano      = agora.getFullYear();
            const horas    = String(agora.getHours()).padStart(2, '0');
            const minutos  = String(agora.getMinutes()).padStart(2, '0');
            const segundos = String(agora.getSeconds()).padStart(2, '0');
            const el = document.getElementById('relogio-digital');
            if (el) el.innerHTML = `<i class="fa-regular fa-clock me-1 text-muted"></i> ${dia}/${mes}/${ano} ${horas}:${minutos}:${segundos}`;
        }
        setInterval(atualizarRelogio, 1000);
        atualizarRelogio();

        // 3. BOTÃO HAMBÚRGUER
        const btnToggle = document.getElementById('btnSidebarToggle');
        if (btnToggle) {
            btnToggle.addEventListener('click', function () {
                // Integração com a classe da sidebar futura
            });
        }
    </script>
</body>
</html>