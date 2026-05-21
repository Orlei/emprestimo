<?php
// 1. Segurança e Sessão (O encerra_sessao.php já inicia a session internamente)
require_once 'encerra_sessao.php';

// 2. Conexão com o Banco de Dados
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

// Recupera a mensagem da sessão (caso tenha vindo do redirecionamento do cadastro)
if (isset($_SESSION['msg_sucesso'])) {
    $mensagem = $_SESSION['msg_sucesso'];
    $tipo_mensagem = "success";
    unset($_SESSION['msg_sucesso']); // Deleta para não exibir de novo no próximo F5
}

// 3. Processamento dos Formulários (Ações POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // AÇÃO: Cadastrar Tipo de Item
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

    // AÇÃO: Cadastrar Novo Item (COM TRAVA DE REENVIO DE POST)
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

    // AÇÃO: Editar Item Existente
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

    // AÇÃO: Excluir Item
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

// 4. Busca dados para renderizar a página
$tipos = $conn->query("SELECT * FROM tipos_item ORDER BY nome_tipo ASC");

// Query completa que junta o item com o nome do seu tipo (INNER JOIN)
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
        }

        /* --- CONTEÚDO PRINCIPAL --- */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 70px;
            background-color: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .indicator-pill {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
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
            padding: 14px;
            border-bottom: 2px solid #edf2f7;
        }

        .table td {
            padding: 14px;
            border-bottom: 1px solid #edf2f7;
        }

        .badge-type {
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.82rem;
            display: inline-block;
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
    </style>
</head>
<body>
    
    <!-- Menu da página -->
    <?php include 'sidebar.php'; ?>

    <div class="main-wrapper">

        <div class="main-content">
            
            <header class="topbar">
                <div class="d-flex align-items-center">
                    <h5 class="m-0 fw-bold text-dark"><i class="fa-solid fa-box-archive text-muted me-2"></i>Módulo de Patrimônio</h5>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="indicator-pill" id="relogio-digital">
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
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-dark fw-semibold btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalTipoItem" style="border-radius: 8px;">
                                <i class="fa-solid fa-tags me-1"></i> Novo tipo de item
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
                                    <th width="90">ID</th>
                                    <th width="220">Categoria / Tipo</th>
                                    <th>Nome do Equipamento</th>
                                    <th>Especificações / Descrição</th>
                                    <th width="140" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($itens_inventario && $itens_inventario->num_rows > 0): ?>
                                    <?php while($row = $itens_inventario->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold text-secondary">#<?= $row['id'] ?></td>
                                            <td><span class="badge-type"><i class="fa-solid fa-tag me-1 small"></i><?= htmlspecialchars($row['nome_tipo']) ?></span></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nome_item']) ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($row['descricao'] ?: 'Nenhuma observação ou patrimônio inserido.') ?></td>
                                            <td class="text-center">
                                                <div class="btn-group shadow-sm" style="border-radius: 6px; overflow: hidden;">
                                                    <button class="btn btn-white btn-sm border text-primary px-3" 
                                                            title="Alterar dados"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalEditarItem"
                                                            data-id="<?= $row['id'] ?>"
                                                            data-nome="<?= htmlspecialchars($row['nome_item'], ENT_QUOTES) ?>"
                                                            data-tipo="<?= $row['id_tipo'] ?>"
                                                            data-descricao="<?= htmlspecialchars($row['descricao'], ENT_QUOTES) ?>">
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
                                                    <button class="btn btn-white btn-sm border text-danger px-3" 
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

    <!-- Modal: Novo Tipo de Item -->
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

    <!-- Modal: Cadastrar Novo Item -->
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

    <!-- Modal: Editar Item -->
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

    <!-- Modal: Excluir Item -->
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

    <!-- CORREÇÃO PRINCIPAL: era "bundle.min.js", o correto é "bootstrap.bundle.min.js" -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 1. CRONÔMETRO DE SESSÃO DO USUÁRIO
        let tempoRestante = <?php echo isset($tempo_restante_inicial) ? (int)$tempo_restante_inicial : 1200; ?>;
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
                    document.getElementById('box-cronometro').classList.add('timer-alerta');
                }
            }
            tempoRestante--;
        }
        setInterval(atualizarCronometro, 1000);
        atualizarCronometro();

        // 2. RELÓGIO DIGITAL EM TEMPO REAL
        function atualizarRelogio() {
            const agora = new Date();
            
            const dia     = String(agora.getDate()).padStart(2, '0');
            const mes     = String(agora.getMonth() + 1).padStart(2, '0');
            const ano     = agora.getFullYear();
            const horas   = String(agora.getHours()).padStart(2, '0');
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

        // 3. GATILHOS DOS MODAIS DINÂMICOS

        // Modal Editar: popula os campos com os dados do item clicado
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

        // Modal Excluir: popula o nome e id do item a ser removido
        const modalExcluir = document.getElementById('modalExcluirItem');
        if (modalExcluir) {
            modalExcluir.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('delete_id_item').value      = button.getAttribute('data-id');
                document.getElementById('delete_nome_item').textContent = button.getAttribute('data-nome');
            });
        }
    </script>
</body>
</html>