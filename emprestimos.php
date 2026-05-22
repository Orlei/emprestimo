<?php
// 1. Segurança e Sessão
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
if (isset($_SESSION['msg_sucesso'])) {
    $mensagem = $_SESSION['msg_sucesso'];
    unset($_SESSION['msg_sucesso']);
}
    // Tradução da data e hora para o padrão brasileiro.
     function dataEmPortugues($data) {
    if (!$data) return '<span class="text-muted small">Pendente</span>';
    
    // Converte a data do banco para o formato padrão do PHP
    $data_formatada = date('d/M/Y H:i', strtotime($data));
    
    // Lista de tradução dos meses abreviados
    $meses_en = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $meses_pt = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    
    // Substitui o mês em inglês pelo mês em português
    return str_replace($meses_en, $meses_pt, $data_formatada);
}

// 3. Processamento dos Formulários (PRG Pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    
    // AÇÃO: Registrar Empréstimo
    if ($_POST['acao'] === 'registrar_emprestimo') {
        $id_item            = intval($_POST['id_item'] ?? 0);
        $nome_responsavel   = trim($_POST['nome_responsavel'] ?? '');
        $setor_responsavel  = trim($_POST['setor_responsavel'] ?? '');

        if ($id_item > 0 && !empty($nome_responsavel) && !empty($setor_responsavel)) {
            $stmt = $conn->prepare("INSERT INTO emprestimos (id_item, nome_responsavel, setor_responsavel) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $id_item, $nome_responsavel, $setor_responsavel);
            $stmt->execute();
            $_SESSION['msg_sucesso'] = "Empréstimo registrado com sucesso!";
            header("Location: " . $_SERVER['PHP_SELF']); exit;
        }
    }

    // AÇÃO: Registrar Devolução
    if ($_POST['acao'] === 'registrar_devolucao') {
        $id_emprestimo = intval($_POST['id_emprestimo'] ?? 0);

        if ($id_emprestimo > 0) {
            // Define a data_devolucao com o horário atual e muda o status para Devolvido
            $stmt = $conn->prepare("UPDATE emprestimos SET data_devolucao = NOW(), status = 'Devolvido' WHERE id = ?");
            $stmt->bind_param("i", $id_emprestimo);
            $stmt->execute();
            $_SESSION['msg_sucesso'] = "Devolução registrada com sucesso!";
            header("Location: " . $_SERVER['PHP_SELF']); exit;
        }
    }
}

// 4. Busca dados para renderizar
// Busca itens para listar no select do Modal
$itens_disponiveis = $conn->query("SELECT id, nome_item FROM itens ORDER BY nome_item ASC");

// Busca o histórico de empréstimos trazendo o nome do equipamento correspondente via INNER JOIN
$historico_emprestimos = $conn->query("
    SELECT e.*, i.nome_item 
    FROM emprestimos e 
    INNER JOIN itens i ON e.id_item = i.id 
    ORDER BY e.status ASC, e.data_emprestimo DESC
");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Empréstimos - UNIOESTE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --unioeste-navy: #000033; --unioeste-blue: #0b2265; --unioeste-wine: #800020; --sidebar-width: 280px; --bg-light: #f4f6f9; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { height: 70px; background-color: #fff; border-bottom: 1px solid #e5e7eb; padding: 0 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; }
        .indicator-pill { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 600; color: #475569; }
        .inventory-card { background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 25px; }
        .badge-active { background-color: #fef3c7; color: #d97706; font-weight: 600; padding: 6px 12px; border-radius: 6px; font-size: 0.82rem; }
        .badge-returned { background-color: #dcfce7; color: #15803d; font-weight: 600; padding: 6px 12px; border-radius: 6px; font-size: 0.82rem; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <header class="topbar">
            <h5 class="m-0 fw-bold text-dark"><i class="fa-solid fa-handshake text-muted me-2"></i>Módulo de Patrimônio</h5>
            <div class="d-flex align-items-center gap-3">
                <div class="indicator-pill" id="relogio-digital">--:--</div>
                <div class="indicator-pill">Sessão: <span id="cronometro" class="fw-bold text-dark">--:--</span></div>
            </div>
        </header>

        <main class="p-4 p-lg-5">
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <div class="inventory-card">
                <div class="d-flex justify-content-between mb-4">
                    <div>
                        <h3 class="fw-bold text-dark m-0">Movimentação de Empréstimos</h3>
                        <p class="text-muted m-0 small">Controle de saídas, destinos e devoluções de equipamentos.</p>
                    </div>
                    <div>
                        <button class="btn btn-primary btn-sm" style="background-color: var(--unioeste-blue); border: none;" data-bs-toggle="modal" data-bs-target="#modalNovoEmprestimo"><i class="fa-solid fa-plus me-1"></i>Novo Empréstimo</button>
                    </div>
                </div>
                
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Equipamento</th>
                            <th>Responsável</th>
                            <th>Setor</th>
                            <th>Retirada (Data/Hora)</th>
                            <th>Devolução (Data/Hora)</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $historico_emprestimos->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nome_item']) ?></td>
                                <td><?= htmlspecialchars($row['nome_responsavel']) ?></td>
                                <td><?= htmlspecialchars($row['setor_responsavel']) ?></td>
                                <td><?= dataEmPortugues($row['data_emprestimo']) ?></td>
                                <td><?= dataEmPortugues($row['data_devolucao']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'Ativo'): ?>
                                        <span class="badge-active">Em Uso</span>
                                    <?php else: ?>
                                        <span class="badge-returned">Devolvido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Ativo'): ?>
                                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalDevolucao" 
                                                data-id="<?= $row['id'] ?>" data-nome="<?= htmlspecialchars($row['nome_item']) ?>" data-resp="<?= htmlspecialchars($row['nome_responsavel']) ?>"><i class="fa-solid fa-arrow-rotate-left me-1"></i>Devolver</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light text-muted" disabled><i class="fa-solid fa-check"></i> Finalizado</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div class="modal fade" id="modalNovoEmprestimo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="registrar_emprestimo">
                    <div class="modal-header">
                        <h5 class="fw-bold m-0">Registrar Novo Empréstimo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Selecione o Equipamento</label>
                            <select name="id_item" class="form-select" required>
                                <option value="">-- Escolha um item --</option>
                                <?php $itens_disponiveis->data_seek(0); while($item = $itens_disponiveis->fetch_assoc()): ?>
                                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['nome_item']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nome do Responsável</label>
                            <input type="text" name="nome_responsavel" class="form-control" required placeholder="Ex: João Silva">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Setor de Destino</label>
                            <input type="text" name="setor_responsavel" class="form-control" required placeholder="Ex: Laboratório de Informática 3">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm" style="background-color: var(--unioeste-blue); border: none;">Confirmar Saída</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDevolucao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="registrar_devolucao">
                    <input type="hidden" name="id_emprestimo" id="return_id_emprestimo">
                    <div class="modal-header">
                        <h5 class="fw-bold m-0 text-success">Confirmar Recebimento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="m-0">Você confirma o retorno e a devolução do equipamento <b id="return_nome_item" class="text-dark"></b> que estava com <b id="return_nome_resp"></b>?</p>
                        <small class="text-muted d-block mt-2"><i class="fa-solid fa-clock me-1"></i>A data e hora atuais serão salvas automaticamente.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal">Não</button>
                        <button type="submit" class="btn btn-success btn-sm">Sim, Confirmar Devolução</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JS para passar as variáveis dinâmicas para dentro do Modal de Devolução
        document.getElementById('modalDevolucao').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('return_id_emprestimo').value = button.dataset.id;
            document.getElementById('return_nome_item').textContent = button.dataset.nome;
            document.getElementById('return_nome_resp').textContent = button.dataset.resp;
        });

        // Mantendo seus scripts dinâmicos de relógio
        function atualizarRelogio() { document.getElementById('relogio-digital').textContent = new Date().toLocaleString('pt-BR'); }
        setInterval(atualizarRelogio, 1000);
        atualizarRelogio();

        // Cronômetro padrão (Exemplo 20 minutos)
        let tempoRestante = 3600;
        setInterval(() => {
            if(tempoRestante > 0) {
                tempoRestante--;
                document.getElementById('cronometro').textContent = Math.floor(tempoRestante/60) + ":" + (tempoRestante%60).toString().padStart(2, '0');
            }
        }, 1000);
    </script>
</body>
</html>