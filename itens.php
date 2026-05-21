<?php
require_once 'encerra_sessao.php';

$host = "127.0.0.1";
$user = "root";
$pass = "7!5JJTBpIoZb.5t!";
$db   = "atp";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Falha na conexão com o banco: " . $conn->connect_error);
}

$mensagem = "";
$tipo_mensagem = "success";

if (isset($_SESSION['msg_sucesso'])) {
    $mensagem = $_SESSION['msg_sucesso'];
    $tipo_mensagem = "success";
    unset($_SESSION['msg_sucesso']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar_tipo') {
        $nome_tipo = trim($_POST['nome_tipo'] ?? '');
        if (!empty($nome_tipo)) {
            $stmt = $conn->prepare("INSERT INTO tipos_item (nome_tipo) VALUES (?)");
            $stmt->bind_param("s", $nome_tipo);
            if ($stmt->execute()) {
                $mensagem = "Tipo de item '$nome_tipo' cadastrado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao cadastrar tipo.";
                $tipo_mensagem = "danger";
            }
            $stmt->close();
        }
    }

    if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar_item') {
        $id_tipo   = intval($_POST['id_tipo'] ?? 0);
        $nome_item = trim($_POST['nome_item'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        if ($id_tipo > 0 && !empty($nome_item)) {
            $stmt = $conn->prepare("INSERT INTO itens (id_tipo, nome_item, descricao) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id_tipo, $nome_item, $descricao);
            if ($stmt->execute()) {
                $_SESSION['msg_sucesso'] = "Item '$nome_item' registrado no inventário!";
                header("Location: itens.php");
                exit;
            } else {
                $mensagem = "Erro ao registrar item.";
                $tipo_mensagem = "danger";
            }
            $stmt->close();
        }
    }

    if (isset($_POST['acao']) && $_POST['acao'] === 'editar_item') {
        $id_item   = intval($_POST['id_item'] ?? 0);
        $id_tipo   = intval($_POST['id_tipo'] ?? 0);
        $nome_item = trim($_POST['nome_item'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        if ($id_item > 0 && $id_tipo > 0 && !empty($nome_item)) {
            $stmt = $conn->prepare("UPDATE itens SET id_tipo = ?, nome_item = ?, descricao = ? WHERE id = ?");
            $stmt->bind_param("issi", $id_tipo, $nome_item, $descricao, $id_item);
            if ($stmt->execute()) {
                $mensagem = "Item atualizado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao atualizar item.";
                $tipo_mensagem = "danger";
            }
            $stmt->close();
        }
    }

    if (isset($_POST['acao']) && $_POST['acao'] === 'excluir_item') {
        $id_item = intval($_POST['id_item'] ?? 0);
        if ($id_item > 0) {
            $stmt = $conn->prepare("DELETE FROM itens WHERE id = ?");
            $stmt->bind_param("i", $id_item);
            if ($stmt->execute()) {
                $mensagem = "Item removido do inventário.";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao excluir item.";
                $tipo_mensagem = "danger";
            }
            $stmt->close();
        }
    }
}

$tipos = $conn->query("SELECT * FROM tipos_item ORDER BY nome_tipo ASC");

$itens_inventario = $conn->query("
    SELECT i.id, i.nome_item, i.descricao, i.id_tipo, t.nome_tipo 
    FROM itens i 
    INNER JOIN tipos_item t ON i.id_tipo = t.id 
    ORDER BY i.id DESC
");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventário de Itens - UNIOESTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --unioeste-navy: #000033;
            --unioeste-blue: #0b2265;
            --unioeste-wine: #800020;
            --sidebar-width: 280px;
            --bg-light: #f4f6f9;
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            /* CORREÇÃO: transição suave para quando a sidebar colapsar */
            transition: margin-left 0.3s ease;
        }

        /* Topbar */
        .topbar {
            min-height: 70px;
            height: auto;
            background-color: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 24px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* CORREÇÃO: permite quebra de linha quando os pills não cabem */
            flex-wrap: wrap;
            gap: 8px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Botão hambúrguer — visível só em mobile */
        .btn-sidebar-toggle {
            display: none;
            background: none;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            color: #475569;
        }

        .topbar-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .indicator-pill {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
            white-space: nowrap;
        }

        .timer-alerta {
            color: #dc3545 !important;
            background-color: #fff5f5 !important;
            border-color: #fbc2c2 !important;
        }

        .inventory-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            padding: 25px;
        }

        .table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            border-bottom: 2px solid #edf2f7;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid #edf2f7;
        }

        .badge-type {
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
            display: inline-block;
            white-space: nowrap;
        }

        .modal-content {
            border-radius: 14px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .modal-header {
            border-bottom: 1px solid #f1f5f9;
            background-color: #fafafa;
            border-radius: 14px 14px 0 0;
        }

        /* =============================================
           RESPONSIVIDADE
           ============================================= */

        /* Tablet (até 991px) */
        @media (max-width: 991.98px) {
            .main-content {
                padding: 0;
            }
            main.p-lg-5 {
                padding: 20px !important;
            }
            .inventory-card {
                padding: 18px;
            }
        }

        /* Mobile (até 768px): colapsa a sidebar */
        @media (max-width: 768px) {
            /* CORREÇÃO PRINCIPAL: remove o margin da sidebar */
            .main-content {
                margin-left: 0;
            }
            .btn-sidebar-toggle {
                display: flex;
                align-items: center;
            }
            main.p-4 {
                padding: 14px !important;
            }
            .inventory-card {
                padding: 14px;
            }
            /* CORREÇÃO: botões de cadastro com wrap */
            .btn-group-cadastro {
                flex-wrap: wrap;
            }
            /* CORREÇÃO: ocultar coluna "Descrição" — menos relevante em mobile */
            .table th:nth-child(4),
            .table td:nth-child(4) {
                display: none;
            }
        }

        /* Mobile pequeno (até 480px) */
        @media (max-width: 480px) {
            /* CORREÇÃO: ocultar relógio no topbar, mantém só o cronômetro */
            .pill-relogio {
                display: none !important;
            }
            .topbar h5 {
                font-size: 0.9rem;
            }
            /* CORREÇÃO: ocultar também coluna "Categoria/Tipo" — deixa só ID, Nome e Ações */
            .table th:nth-child(2),
            .table td:nth-child(2) {
                display: none;
            }
            /* CORREÇÃO: botões de ação menores */
            .btn-group .btn {
                padding: 4px 8px;
            }
            /* CORREÇÃO: texto dos botões de cadastro sem ícone em telas muito pequenas */
            .btn-label-novo-tipo { display: none; }
        }
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>

    <div class="main-wrapper">
        <div class="main-content">
            
            <header class="topbar">
                <div class="topbar-left">
                    <!-- Botão hambúrguer -->
                    <button class="btn-sidebar-toggle" id="btnSidebarToggle" aria-label="Abrir menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h5 class="m-0 fw-bold text-dark">
                        <i class="fa-solid fa-box-archive text-muted me-2"></i>Módulo de Patrimônio
                    </h5>
                </div>
                
                <div class="topbar-pills">
                    <!-- CORREÇÃO: relógio com classe para ocultar em mobile pequeno -->
                    <div class="indicator-pill pill-relogio" id="relogio-digital">
                        <i class="fa-regular fa-clock me-1 text-muted"></i> --/--/---- --:--:--
                    </div>
                    <div class="indicator-pill d-flex align-items-center gap-1" id="box-cronometro">
                        <i class="fa-solid fa-hourglass-half text-muted me-1"></i> Sessão: <span id="cronometro" class="fw-bold text-dark">--:--</span>
                    </div>
                </div>
            </header>

            <main class="p-4 p-lg-5">
                
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
                        <i class="fa-solid fa-circle-info me-2"></i> <?= htmlspecialchars($mensagem) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="inventory-card">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                        <div>
                            <h3 class="fw-bold text-dark m-0">Inventário de Itens</h3>
                            <p class="text-muted m-0 small">Gerenciamento, classificação e controle de equipamentos físicos da unidade.</p>
                        </div>
                        <!-- CORREÇÃO: flex-wrap para botões não sobreporem em telas estreitas -->
                        <div class="d-flex gap-2 flex-wrap btn-group-cadastro">
                            <button class="btn btn-outline-dark fw-semibold btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalTipoItem" style="border-radius: 8px;">
                                <i class="fa-solid fa-tags me-1"></i>
                                <span class="btn-label-novo-tipo">Novo tipo de item</span>
                            </button>
                            <button class="btn btn-primary fw-semibold btn-sm px-3" style="background-color: var(--unioeste-blue); border: none; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#modalNovoItem">
                                <i class="fa-solid fa-plus me-1"></i> Cadastrar Item
                            </button>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-4">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead>
                                <tr>
                                    <th width="70">ID</th>
                                    <!-- CORREÇÃO: colunas com classes d-none para ocultar progressivamente em mobile -->
                                    <th class="d-none d-sm-table-cell" width="180">Categoria / Tipo</th>
                                    <th>Nome do Equipamento</th>
                                    <th class="d-none d-md-table-cell">Especificações / Descrição</th>
                                    <th width="100" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($itens_inventario && $itens_inventario->num_rows > 0): ?>
                                    <?php while($row = $itens_inventario->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold text-secondary">#<?= $row['id'] ?></td>
                                            <td class="d-none d-sm-table-cell">
                                                <span class="badge-type">
                                                    <i class="fa-solid fa-tag me-1 small"></i><?= htmlspecialchars($row['nome_tipo']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold text-dark">
                                                <?= htmlspecialchars($row['nome_item']) ?>
                                                <!-- Em mobile, mostra o tipo abaixo do nome -->
                                                <small class="d-block d-sm-none text-muted fw-normal mt-1">
                                                    <i class="fa-solid fa-tag me-1" style="font-size:0.7rem;"></i><?= htmlspecialchars($row['nome_tipo']) ?>
                                                </small>
                                            </td>
                                            <td class="text-muted small d-none d-md-table-cell">
                                                <?= htmlspecialchars($row['descricao'] ?: 'Nenhuma observação ou patrimônio inserido.') ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group shadow-sm" style="border-radius: 6px; overflow: hidden;">
                                                    <button class="btn btn-white btn-sm border text-primary px-2 px-md-3" 
                                                            title="Alterar dados"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalEditarItem"
                                                            data-id="<?= $row['id'] ?>"
                                                            data-nome="<?= htmlspecialchars($row['nome_item'], ENT_QUOTES) ?>"
                                                            data-tipo="<?= $row['id_tipo'] ?>"
                                                            data-descricao="<?= htmlspecialchars($row['descricao'], ENT_QUOTES) ?>">
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
                                                    <button class="btn btn-white btn-sm border text-danger px-2 px-md-3" 
                                                            title="Excluir do Inventário"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalExcluirItem"
                                                            data-id="<?= $row['id'] ?>"
                                                            data-nome="<?= htmlspecialchars($row['nome_item'], ENT_QUOTES) ?>">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-box-open d-block fs-2 mb-2 opacity-25"></i>
                                            Nenhum equipamento registrado no inventário até o momento.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>

    </div><!-- /.main-wrapper -->

    <!-- ==================== MODAIS ==================== -->

    <div class="modal fade" id="modalTipoItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-tags me-2 text-muted"></i>Novo Tipo de Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="acao" value="cadastrar_tipo">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nome da Categoria / Classificação</label>
                            <input type="text" name="nome_tipo" class="form-control" placeholder="Ex: Projetores, Notebooks, Adaptadores" required style="border-radius: 8px;">
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f8fafc;">
                        <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4" style="background-color: var(--unioeste-blue); border:none; border-radius: 6px;">Salvar Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNovoItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-box-archive me-2 text-muted"></i>Inserir Item no Inventário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="acao" value="cadastrar_item">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Tipo de Item / Categoria</label>
                            <select name="id_tipo" class="form-select" required style="border-radius: 8px;">
                                <option value="" disabled selected>-- Selecione uma Categoria --</option>
                                <?php 
                                if ($tipos && $tipos->num_rows > 0) {
                                    $tipos->data_seek(0);
                                    while ($t = $tipos->fetch_assoc()) {
                                        echo "<option value='{$t['id']}'>" . htmlspecialchars($t['nome_tipo']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nome / Identificação do Equipamento</label>
                            <input type="text" name="nome_item" class="form-control" placeholder="Ex: Projetor Epson PowerLite X49" required style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Descrição / N° de Patrimônio / Observações</label>
                            <textarea name="descricao" class="form-control" rows="3" placeholder="Insira detalhes específicos como número patrimonial, cabos inclusos..." style="border-radius: 8px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f8fafc;">
                        <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4" style="background-color: var(--unioeste-blue); border:none; border-radius: 6px;">Registrar Equipamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark"><i class="fa-regular fa-pen-to-square me-2 text-muted"></i>Alterar Item Selecionado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="acao" value="editar_item">
                    <input type="hidden" name="id_item" id="edit_id_item">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Tipo de Item</label>
                            <select name="id_tipo" id="edit_id_tipo" class="form-select" required style="border-radius: 8px;">
                                <?php 
                                if ($tipos && $tipos->num_rows > 0) {
                                    $tipos->data_seek(0);
                                    while ($t = $tipos->fetch_assoc()) {
                                        echo "<option value='{$t['id']}'>" . htmlspecialchars($t['nome_tipo']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nome do Equipamento</label>
                            <input type="text" name="nome_item" id="edit_nome_item" class="form-control" required style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Especificações técnicas / Descrição</label>
                            <textarea name="descricao" id="edit_descricao" class="form-control" rows="3" style="border-radius: 8px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f8fafc;">
                        <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success btn-sm px-4" style="border-radius: 6px;">Atualizar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalExcluirItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Excluir Item?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="acao" value="excluir_item">
                    <input type="hidden" name="id_item" id="delete_id_item">
                    <div class="modal-body text-center">
                        <p class="m-0 small text-secondary">Tem certeza que deseja remover este item permanentemente do sistema?</p>
                        <p class="fw-bold text-dark mt-2 p-2 bg-light border rounded" id="delete_nome_item">--</p>
                    </div>
                    <div class="modal-footer justify-content-center" style="background-color: #f8fafc;">
                        <button type="button" class="btn btn-light border btn-sm px-3" data-bs-dismiss="modal">Não</button>
                        <button type="submit" class="btn btn-danger btn-sm px-3" style="border-radius: 6px;">Sim, Remover</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== SCRIPTS ==================== -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 1. CRONÔMETRO DE SESSÃO
        let tempoRestante = <?php echo isset($tempo_restante_inicial) ? (int)$tempo_restante_inicial : 1200; ?>;
        const display = document.getElementById('cronometro');

        function atualizarCronometro() {
            if (tempoRestante <= 0) {
                window.location.href = "login.php?erro=sessao_expirada";
                return;
            }
            let minutos  = Math.floor(tempoRestante / 60);
            let segundos = tempoRestante % 60;
            minutos  = minutos  < 10 ? "0" + minutos  : minutos;
            segundos = segundos < 10 ? "0" + segundos : segundos;
            if (display) {
                display.textContent = minutos + ":" + segundos;
                if (tempoRestante < 300) {
                    document.getElementById('box-cronometro').classList.add('timer-alerta');
                }
            }
            tempoRestante--;
        }
        setInterval(atualizarCronometro, 1000);
        atualizarCronometro();

        // 2. RELÓGIO DIGITAL
        function atualizarRelogio() {
            const agora    = new Date();
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

        // 3. BOTÃO HAMBÚRGUER (preparado para integração com sidebar.php)
        const btnToggle = document.getElementById('btnSidebarToggle');
        if (btnToggle) {
            btnToggle.addEventListener('click', function () {
                // Placeholder: document.querySelector('.sidebar').classList.toggle('sidebar-open');
            });
        }

        // 4. MODAIS DINÂMICOS
        const modalEditar = document.getElementById('modalEditarItem');
        if (modalEditar) {
            modalEditar.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('edit_id_item').value   = button.getAttribute('data-id');
                document.getElementById('edit_nome_item').value = button.getAttribute('data-nome');
                document.getElementById('edit_id_tipo').value   = button.getAttribute('data-tipo');
                document.getElementById('edit_descricao').value = button.getAttribute('data-descricao');
            });
        }

        const modalExcluir = document.getElementById('modalExcluirItem');
        if (modalExcluir) {
            modalExcluir.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('delete_id_item').value         = button.getAttribute('data-id');
                document.getElementById('delete_nome_item').textContent = button.getAttribute('data-nome');
            });
        }
    </script>
</body>
</html>