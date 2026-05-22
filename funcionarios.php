<?php
// funcionarios.php
// Inclui o controle de sessão institucional do seu sistema
require_once 'encerra_sessao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Funcionários - UNIOESTE</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --unioeste-blue: #000033;
            --unioeste-light-blue: #00001a;
        }

        body {
            background-color: #f1f5f9;
            min-height: 100vh;
        }

        /* Estrutura para não sobrepor a Sidebar */
        .main-wrapper {
            margin-left: 260px; /* Largura exata da sua sidebar.php */
            padding: 40px;
            transition: all 0.3s ease;
        }

        /* Ajuste responsivo para telas menores (Mobile) */
        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                padding: 20px;
                padding-top: 80px; /* Espaço para o botão mobile do menu */
            }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-wrapper">
        <div class="container-fluid p-0">
            
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold m-0" style="color: var(--unioeste-blue);">
                        <i class="fa-solid fa-user-plus me-2"></i>Cadastrar Funcionário via LDAP
                    </h5>
                </div>
                <div class="card-body p-4">
                    
                    <div class="mb-4 bg-light p-3 rounded-3 border" style="position: relative;">
                        <label class="form-label fw-bold text-dark">
                            <i class="fa-solid fa-magnifying-glass me-1 text-muted"></i> Buscar Funcionário no Diretório UNIOESTE
                        </label>
                        <div class="input-group">
                            <input type="text" id="busca_ldap" class="form-control" placeholder="Digite o login institucional ou nome do servidor...">
                            <button class="btn btn-primary" type="button" id="btn_buscar_ldap" style="background-color: var(--unioeste-blue); border: none;">
                                <i class="fa-solid fa-search"></i> Buscar
                            </button>
                        </div>
                        <div id="lista_resultados_ldap" class="list-group mt-2 shadow-sm d-none" style="position: absolute; z-index: 100; width: calc(100% - 32px); max-height: 200px; overflow-y: auto;"></div>
                    </div>

                    <form action="salvar_funcionario.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Login Institucional</label>
                                <input type="text" name="login" id="func_login" class="form-control bg-light" readonly required placeholder="Preenchido via busca">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Nome Completo</label>
                                <input type="text" name="nome" id="func_nome" class="form-control bg-light" readonly required placeholder="Preenchido via busca">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">E-mail Institucional</label>
                                <input type="email" name="email" id="func_email" class="form-control bg-light" readonly placeholder="Preenchido via busca">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Ramal/Telefone</label>
                                <input type="text" name="ramal" id="func_ramal" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Setor Padrão</label>
                                <input type="text" name="setor" id="func_setor" class="form-control">
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-success fw-bold px-4" style="border-radius: 8px;">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Concluir Cadastro Local
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.getElementById('btn_buscar_ldap').addEventListener('click', function() {
        const termo = document.getElementById('busca_ldap').value;
        const lista = document.getElementById('lista_resultados_ldap');
        
        if (termo.length < 3) {
            alert('Digite pelo menos 3 caracteres para buscar.');
            return;
        }
        
        lista.innerHTML = '<div class="list-group-item text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Buscando no servidor UNIOESTE...</div>';
        lista.classList.remove('d-none');
        
        fetch(`funcionarios_busca_ldap.php?q=${encodeURIComponent(termo)}`)
            .then(response => response.json())
            .then(dados => {
                lista.innerHTML = '';
                
                if (dados.length === 0 || dados.erro) {
                    lista.innerHTML = `<div class="list-group-item text-danger">${dados.erro || 'Nenhum funcionário encontrado.'}</div>`;
                    return;
                }
                
                dados.forEach(func => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action py-3';
                    item.innerHTML = `
                        <div class="fw-bold text-dark">${func.nome}</div>
                        <small class="text-muted"><i class="fa-regular fa-user me-1"></i>${func.login} | <i class="fa-regular fa-building me-1"></i>${func.setor || 'Não informado'}</small>
                    `;
                    
                    // Ao clicar no funcionário da lista, preenche o formulário automaticamente
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        document.getElementById('func_login').value = func.login;
                        document.getElementById('func_nome').value = func.nome;
                        document.getElementById('func_email').value = func.email;
                        document.getElementById('func_ramal').value = func.ramal;
                        document.getElementById('func_setor').value = func.setor;
                        
                        lista.classList.add('d-none'); // Oculta a lista de sugestões
                    });
                    
                    lista.appendChild(item);
                });
            })
            .catch(err => {
                lista.innerHTML = '<div class="list-group-item text-danger">Erro ao processar consulta.</div>';
            });
    });

    // Oculta a lista se o usuário clicar em qualquer outra parte da tela
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#busca_ldap') && !e.target.closest('#lista_resultados_ldap')) {
            document.getElementById('lista_resultados_ldap').classList.add('d-none');
        }
    });
    </script>
</body>
</html>