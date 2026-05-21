<?php
// Inclui a trava de segurança que criamos no Passo 1
require_once 'encerra_sessao.php';

// Garante que a sessão está ativa antes de ler as variáveis
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario = $_SESSION['usuario'] ?? 'Operador';
$nome_completo = $_SESSION['nome'] ?? 'Usuário';

// Calcula quantos segundos faltam exatamente para passar para o JavaScript
$tempo_restante_inicial = isset($_SESSION['tempo_limite_total'], $_SESSION['ultimo_clique']) 
    ? $_SESSION['tempo_limite_total'] - (time() - $_SESSION['ultimo_clique']) 
    : 1800; // Valor padrão de 30 minutos caso falte a variável
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
            /* Sincronizado com a página de itens */
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

        /* Customização do botão de devolução */
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
        }

        /* Topbar Clean */
        .topbar {
            background-color: #ffffff;
            height: 75px;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
        }

        .content-body {
            padding: 32px;
            flex-grow: 1;
        }

        /* --- CARDS DE MÉTRICAS (KPIs) --- */
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
        }

        /* Mural Clean */
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
            border-radius: 6px;
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
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>
    
    <div class="main-wrapper">

        <header class="topbar">
            <div>
                <h5 class="fw-bold m-0" style="color: var(--unioeste-blue);">Sistema de Controle de Empréstimos</h5>
            </div>
            <div class="d-flex align-items-center gap-4">
                <div class="text-end">
                    <span class="fw-bold text-dark d-block" style="font-size: 0.9rem;">
                        <i class="fa-regular fa-user me-1 text-muted"></i> <?= htmlspecialchars($nome_completo) ?> (<?= htmlspecialchars($usuario) ?>)
                    </span>
                    <small class="text-muted" style="font-size: 0.8rem; font-weight: 500;">
                        Sessão ativa: <span id="cronometro">--:--</span>
                    </small>
                </div>
                <div style="border-left: 1px solid #e2e8f0; height: 30px;"></div>
                <a href="logout.php" class="btn btn-sm btn-outline-danger px-3 fw-semibold">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sair
                </a>
            </div>
        </header>

        <main class="content-body">
            <div class="row g-4">
                
                <div class="col-xl-8">
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="kpi-card">
                                <div>
                                    <span class="text-muted d-block mb-1" style="font-size: 0.85rem; font-weight: 600;">Data e Hora Atual</span>
                                    <h4 class="fw-bold m-0" id="relogio-digital" style="color: var(--unioeste-blue); font-size: 1.3rem;">--/--/---- --:--:--</h4>
                                </div>
                                <div class="kpi-icon"><i class="fa-regular fa-calendar-days"></i></div>
                            </div>
                        </div>
                        <div class="col-md-6">
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
                        <h5 class="fw-bold text-dark mb-3">Operações Diárias</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="emprestimos.php" class="btn btn-primary w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background-color: var(--unioeste-blue); border: none; border-radius: 10px; font-size: 1.05rem;">
                                    <i class="fa-solid fa-plus fs-5"></i> Registrar Novo Empréstimo
                                </a>
                            </div>
                            <div class="col-md-6">
                               <a href="devolucoes.php" class="btn btn-outline-wine w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm botao-devolucao" style="border-radius: 10px; font-size: 1.05rem;">
                                <i class="fa-solid fa-arrow-rotate-left"></i> Dar Baixa em Devolução
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border rounded-3 p-4 shadow-sm" style="border-color: #e2e8f0 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold text-dark m-0">Últimas Movimentações</h5>
                                <p class="text-muted small m-0">Equipamentos pendentes de devolução ou recém movimentados</p>
                            </div>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-2">Monitoramento Ativo</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle m-0">
                                <thead class="table-light" style="font-size: 0.85rem; color: var(--text-muted);">
                                    <tr>
                                        <th>Equipamento</th>
                                        <th>Retirado por</th>
                                        <th>Setor/Destino</th>
                                        <th>Data de Saída</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size: 0.9rem;">
                                    <tr>
                                        <td><span class="fw-semibold">Notebook Dell Latitude</span> <small class="text-muted d-block">Pat: 45892</small></td>
                                        <td>Prof. Alexandre</td>
                                        <td>Lab. Informática 2</td>
                                        <td>Hoje, 10:15</td>
                                        <td class="text-center"><span class="badge bg-danger-subtle text-danger px-2 py-1 fw-bold">Pendente</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="fw-semibold">Projetor Epson PowerLite</span> <small class="text-muted d-block">Pat: 12455</small></td>
                                        <td>Téc. Maria Silva</td>
                                        <td>Bloco B - Sala 102</td>
                                        <td>Ontem, 14:30</td>
                                        <td class="text-center"><span class="badge bg-success-subtle text-success px-2 py-1 fw-bold">Devolvido</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="fw-semibold">Kit Adaptador HDMI/VGA</span> <small class="text-muted d-block">Sem patrimônio</small></td>
                                        <td>Prof. Carlos</td>
                                        <td>Anfiteatro Central</td>
                                        <td>20/05, 08:00</td>
                                        <td class="text-center"><span class="badge bg-danger-subtle text-danger px-2 py-1 fw-bold">Pendente</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
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
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <span>DIV-INF-MCR &copy; 2026</span>
                <span class="text-dark"><i class="fa-brands fa-whatsapp text-success me-1 fs-6 align-middle"></i> Suporte local: <strong>(45) 3284-7824</strong></span>
            </div>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // 1. CRONÔMETRO DE SESSÃO DO USUÁRIO
        let tempoRestante = <?php echo (int)$tempo_restante_inicial; ?>;
        const display = document.getElementById('cronometro');

        function atualizarCronometro() {
            if (tempoRestante <= 0) {
                window.location.href = "login.php?erro=sessao_expirada";
                return;
            }
            let minutos = Math.floor(tempoRestante / 60);
            let segundos = tempoRestante % 60;

            minutos = minutos < 10 ? "0" + minutos : minutos;
            segundos = segundos < 10 ? "0" + segundos : segundos;

            if (display) {
                display.textContent = minutos + ":" + segundos;
                if (tempoRestante < 300) {
                    display.classList.add('timer-alerta');
                }
            }
            tempoRestante--;
        }
        setInterval(atualizarCronometro, 1000);
        atualizarCronometro();

        // 2. RELÓGIO DIGITAL EM TEMPO REAL
        function atualizarRelogio() {
            const agora = new Date();
            
            const dia = String(agora.getDate()).padStart(2, '0');
            const mes = String(agora.getMonth() + 1).padStart(2, '0');
            const ano = agora.getFullYear();
            
            const horas = String(agora.getHours()).padStart(2, '0');
            const minutos = String(agora.getMinutes()).padStart(2, '0');
            const segundos = String(agora.getSeconds()).padStart(2, '0');
            
            const dataHoraFormatada = `${dia}/${mes}/${ano} ${horas}:${minutos}:${segundos}`;
            
            const relogioElement = document.getElementById('relogio-digital');
            if (relogioElement) {
                relogioElement.innerHTML = `<i class="fa-regular fa-clock me-1 text-muted"></i> ${dataHoraFormatada}`;
            }
        }
        setInterval(atualizarRelogio, 1000);
        atualizarRelogio();
    </script>
</body>
</html>