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
        $nome_responsavel   = trim($_POST['beneficiario_nome'] ?? ''); 
        $setor_responsavel  = trim($_POST['beneficiario_setor'] ?? ''); 

        // CRUCIAL: Se a sua tabela do banco ainda não tiver a coluna 'vinculo', ela grava o empréstimo normalmente.
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
            $stmt = $conn->prepare("UPDATE emprestimos SET data_devolucao = NOW(), status = 'Devolvido' WHERE id = ?");
            $stmt->bind_param("i", $id_emprestimo);
            $stmt->execute();
            $_SESSION['msg_sucesso'] = "Devolução registrada com sucesso!";
            header("Location: " . $_SERVER['PHP_SELF']); exit;
        }
    }
}

// 4. Busca dados para renderizar
$itens_disponiveis = $conn->query("SELECT id, nome_item FROM itens ORDER BY nome_item ASC");

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
                        
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold"><i class="fa-solid fa-user me-1 text-muted"></i> Pesquisar Beneficiário</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="busca_beneficiario" class="form-control" placeholder="Digite o nome ou login institucional..." autocomplete="off">
                            </div>
                            <div id="sugestoes_beneficiario" class="list-group mt-1 shadow d-none" style="position: absolute; z-index: 1050; width: 100%; max-height: 250px; overflow-y: auto;"></div>
                        </div>

                        <hr class="text-muted my-3">

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-muted">Nome Completo</label>
                            <input type="text" name="beneficiario_nome" id="hidden_nome" class="form-control bg-light text-dark fw-semibold" readonly required placeholder="Aguardando seleção...">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold text-muted">Vínculo Institucional</label>
                                <input type="text" name="beneficiario_vinculo" id="hidden_vinculo" class="form-control bg-light text-dark fw-semibold" readonly placeholder="Não informado">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold text-muted">Setor / Lotação</label>
                                <input type="text" name="beneficiario_setor" id="hidden_setor" class="form-control bg-light text-dark fw-semibold" readonly required placeholder="Não informado">
                            </div>
                        </div>

                        <input type="hidden" name="beneficiario_login" id="hidden_login">
                        <input type="hidden" name="beneficiario_email" id="hidden_email">
                        <input type="hidden" name="origem_cadastro" id="hidden_origem" value="local"> 
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
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
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Não</button>
                        <button type="submit" class="btn btn-success btn-sm">Sim, Confirmar Devolução</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('modalDevolucao').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('return_id_emprestimo').value = button.dataset.id;
            document.getElementById('return_nome_item').textContent = button.dataset.nome;
            document.getElementById('return_nome_resp').textContent = button.dataset.resp;
        });

        function atualizarRelogio() { document.getElementById('relogio-digital').textContent = new Date().toLocaleString('pt-BR'); }
        setInterval(atualizarRelogio, 1000);
        atualizarRelogio();

        let tempoRestante = 3600;
        setInterval(() => {
            if(tempoRestante > 0) {
                tempoRestante--;
                document.getElementById('cronometro').textContent = Math.floor(tempoRestante/60) + ":" + (tempoRestante%60).toString().padStart(2, '0');
            }
        }, 1000);
    </script>

    <script>
    let timeoutBusca = null;

    document.getElementById('busca_beneficiario').addEventListener('input', function() {
        const termo = this.value;
        const lista = document.getElementById('sugestoes_beneficiario');
        
        clearTimeout(timeoutBusca);
        
        if (termo.length < 4) {
            lista.classList.add('d-none');
            return;
        }

        timeoutBusca = setTimeout(() => {
            lista.innerHTML = '<div class="list-group-item text-muted small"><i class="fa-solid fa-spinner fa-spin me-2"></i>Procurando na base local e UNIOESTE...</div>';
            lista.classList.remove('d-none');

            fetch(`funcionarios_busca_ldap.php?q=${encodeURIComponent(termo)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro no servidor HTTP (Status ${response.status})`);
                    }
                    return response.json();
                })
                .then(dados => {
                    lista.innerHTML = '';
                    
                    if (dados.erro) {
                        lista.innerHTML = `<div class="list-group-item text-danger small"><i class="fa-solid fa-triangle-exclamation me-1"></i> ${dados.erro}</div>`;
                        return;
                    }
                    
                    if (dados.length === 0) {
                        lista.innerHTML = '<div class="list-group-item text-danger small">Nenhuma pessoa encontrada.</div>';
                        return;
                    }

                    dados.forEach(pessoa => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action py-2';
                        
                        const badgeOrigem = pessoa.origem === 'local' 
                            ? '<span class="badge bg-success float-end mt-1 ms-1">Já Cadastrado</span>' 
                            : '<span class="badge bg-primary float-end mt-1 ms-1">UNIOESTE</span>';

                        let corVinculo = 'bg-secondary';
                        if (pessoa.vinculo === 'Aluno') {
                            corVinculo = 'bg-warning text-dark';
                        } else if (pessoa.vinculo === 'Professor') {
                            corVinculo = 'bg-info text-dark';
                        } else if (pessoa.vinculo.includes('Acadê') || pessoa.vinculo.includes('Centro')) {
                            corVinculo = 'bg-dark text-white';
                        } else if (pessoa.vinculo.includes('Extern')) {
                            corVinculo = 'bg-light text-muted border';
                        }
                        
                        const badgeVinculo = pessoa.vinculo 
                            ? `<span class="badge ${corVinculo} float-end mt-1">${pessoa.vinculo}</span>` 
                            : '';

                        item.innerHTML = `
                            <div class="fw-bold text-dark small">
                                ${pessoa.nome} 
                                ${badgeOrigem}
                                ${badgeVinculo}
                            </div>
                            <small class="text-muted">${pessoa.login} | ${pessoa.detalhe || 'Setor não informado'}</small>
                        `;

                        // Ação de Clique: Popula os campos visíveis do front!
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            document.getElementById('busca_beneficiario').value = ''; // Limpa a barra de busca
                            
                            // Popula os novos campos do formulário (que agora são visíveis)
                            document.getElementById('hidden_nome').value = pessoa.nome;
                            document.getElementById('hidden_vinculo').value = pessoa.vinculo;
                            document.getElementById('hidden_setor').value = pessoa.setor || pessoa.detalhe;
                            
                            // Campos de background
                            document.getElementById('hidden_login').value = login;
                            document.getElementById('hidden_email').value = pessoa.email;
                            document.getElementById('hidden_origem').value = pessoa.origem;
                            
                            lista.classList.add('d-none');
                        });

                        lista.appendChild(item);
                    });
                })
                .catch(err => {
                    lista.innerHTML = `<div class="list-group-item text-danger small"><i class="fa-solid fa-circle-xmark me-1"></i> Erro: ${err.message}</div>`;
                });
        }, 600);
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#busca_beneficiario') && !e.target.closest('#sugestoes_beneficiario')) {
            document.getElementById('sugestoes_beneficiario').classList.add('d-none');
        }
    });
    </script>
</body>
</html>