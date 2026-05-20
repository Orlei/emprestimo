<?php
// Inclui a trava de segurança que criamos no Passo 1
require_once 'encerra_sessao.php';

$usuario = $_SESSION['usuario'];
$nome_completo = $_SESSION['nome'] ?? 'Usuário';

// Calcula quantos segundos faltam exatamente para passar para o JavaScript
$tempo_restante_inicial = $_SESSION['tempo_limite_total'] - (time() - $_SESSION['ultimo_clique']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Controle de Empréstimos - UNIOESTE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --unioeste-blue: #000050;
            --unioeste-light-blue: #1d4ed8;
            --bg-gray: #f3f4f6;
        }

        body {
            background-color: var(--bg-gray);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .custom-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 24px;
        }

        .logo-institucional {
            height: 42px;
        }

        .main-menu-container, .side-notice-container {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            height: 100%;
        }

        .side-notice-container { padding: 24px; }

        .menu-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.25s ease;
            text-decoration: none;
            color: #1f2937;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 35px 20px;
            height: 100%;
            text-align: center;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 80, 0.08);
            border-color: var(--unioeste-light-blue);
            color: var(--unioeste-light-blue);
        }

        .menu-card i {
            font-size: 42px;
            color: #374151;
            margin-bottom: 16px;
            transition: color 0.25s ease;
        }

        .menu-card:hover i { color: var(--unioeste-light-blue); }
        .card-menu-title { font-size: 1.15rem; font-weight: 600; margin-bottom: 8px; }
        .card-menu-desc { font-size: 0.85rem; color: #6b7280; line-height: 1.4; }
        .notice-item { background: #ffffff; border: 1px solid #e5e7eb; border-top: 4px solid #ef4444; border-radius: 8px; padding: 16px; }
        footer { background-color: #ffffff; border-top: 1px solid #e5e7eb; font-size: 0.85rem; color: #6b7280; }
        
        /* Estilo do timer em alerta */
        .timer-alerta {
            color: #ef4444 !important;
            animation: piscar 1s infinite;
            font-weight: bold;
        }
        @keyframes piscar {
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light custom-navbar shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="https://www.unioeste.br/portal/images/logo_unioeste.png" class="logo-institucional me-3" alt="Logo UNIOESTE">
                <span class="fw-bold text-dark d-none d-sm-inline" style="font-size: 1.2rem; letter-spacing: -0.5px;">
                    Sistema de Controle de Empréstimos
                </span>
            </a>
            
            <div class="ms-auto d-flex align-items-center">
                <div class="me-4 text-end d-none d-md-block">
                    <span class="fw-semibold text-dark d-block" style="font-size: 0.95rem;">
                        <i class="fa-regular fa-user me-1 text-muted"></i> <?= htmlspecialchars($usuario) ?>
                    </span>
                    <small class="text-muted" style="font-size: 0.85rem;">
                        <i class="fa-regular fa-clock"></i> Sessão: <span id="cronometro">--:--</span>
                    </small>
                </div>
                <a href="logout.php" class="btn btn-light border btn-sm px-3 fw-semibold text-secondary">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <main class="container-fluid px-4 my-4 flex-grow-1">
        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="main-menu-container">
                    <div class="mb-4">
                        <h3 class="fw-bold text-dark m-0">Dashboard - Menu Principal</h3>
                        <p class="text-muted sm m-0">Bem-vindo ao Sistema de Controle de Empréstimos</p>
                    </div>

                    <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-md-3">
                        <div class="col">
                            <a href="itens.php" class="menu-card">
                                <i class="fa-solid fa-layer-group"></i>
                                <span class="card-menu-title">Inventário de Itens</span>
                                <span class="card-menu-desc">Inventário de itens para manutenção e consultas.</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="emprestimos.php" class="menu-card">
                                <i class="fa-solid fa-handshake-angle"></i>
                                <span class="card-menu-title">Novos Empréstimos</span>
                                <span class="card-menu-desc">Novos empréstimos e saídas para servidores.</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="devolucoes.php" class="menu-card">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span class="card-menu-title">Controle de Devoluções</span>
                                <span class="card-menu-desc">Retorno de materiais e baixa de pendências.</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="setores.php" class="menu-card">
                                <i class="fa-solid fa-sitemap"></i>
                                <span class="card-menu-title">Gestão de Setores</span>
                                <span class="card-menu-desc">Configuração e controle dos setores do campus.</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="funcionarios.php" class="menu-card">
                                <i class="fa-solid fa-address-card"></i>
                                <span class="card-menu-title">Registro de Funcionários</span>
                                <span class="card-menu-desc">Cadastro e consulta de servidores vinculados.</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="usuarios.php" class="menu-card">
                                <i class="fa-solid fa-users-gear"></i>
                                <span class="card-menu-title">Usuários do Sistema</span>
                                <span class="card-menu-desc">Gerenciamento de permissões de operadores.</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="side-notice-container">
                    <div class="d-flex justify-content-between align-items-c
                    enter mb-4">
                        <h4 class="fw-bold text-dark m-0">Mural de Avisos</h4>
                        <button class="btn btn-primary btn-sm px-3 fw-semibold" style="background-color: #0b2265; border: none;">
                            <i class="fa-solid fa-plus me-1"></i> Novo Aviso
                        </button>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <div class="notice-item shadow-sm">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="fw-bold text-dark" style="font-size: 0.9rem;">📢 Atenção: Sistema em Modo Beta!</span>
                            </div>
                            <p class="text-secondary mb-2" style="font-size: 0.85rem; line-height: 1.5;">
                                O sistema já está no ar para testes! Nesta fase, precisamos da sua ajuda para caçar "bugs" 🐛. Se notarem algo errado, reportem para <strong>rondon.informatica@unioeste.br</strong> ou ramal <strong>7824</strong>.
                            </p>
                            <small class="text-muted d-block border-top pt-1" style="font-size: 0.75rem;">
                                Por: orlei.javorski@unioeste.br
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <footer class="py-3 text-center">
        <div class="container-fluid px-4 d-flex justify-content-between align-items-center flex-wrap">
            <span>© DIV-INF-MCR</span>
            <span><i class="fa-brands fa-whatsapp text-success me-1"></i> (45) 3284-7824</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Pega o tempo restante calculado pelo PHP no carregamento da página
        let tempoRestante = <?php echo $tempo_restante_inicial; ?>;
        const display = document.getElementById('cronometro');

        function atualizarCronometro() {
            if (tempoRestante <= 0) {
                // Se o tempo acabar, força o redirecionamento para deslogar
                window.location.href = "login.php?erro=sessao_expirada";
                return;
            }

            let minutos = Math.floor(tempoRestante / 60);
            let segundos = tempoRestante % 60;

            // Formata para ter sempre dois dígitos (ex: 05:09 em vez de 5:9)
            minutos = minutos < 10 ? "0" + minutos : minutos;
            segundos = segundos < 10 ? "0" + segundos : segundos;

            display.textContent = minutos + ":" + segundos;

            // Se faltarem menos de 5 minutos (300 segundos), o cronômetro fica vermelho e pisca
            if (tempoRestante < 300) {
                display.classList.add('timer-alerta');
            }

            tempoRestante--;
        }

        // Executa a função a cada 1 segundo (1000 milissegundos)
        setInterval(atualizarCronometro, 1000);
        atualizarCronometro(); // Roda a primeira vez imediatamente
    </script>
</body>
</html>