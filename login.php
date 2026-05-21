<?php
session_start();

/*
 |-------------------------------------------------------------
 | LOGIN LDAP - ACTIVE DIRECTORY (UNIOESTE)
 |-------------------------------------------------------------
 */

$ldap_host = "10.88.201.2"; 
$ldap_port = 389; 
$base_dn   = "DC=unioeste,DC=br";
$domain    = "@unioeste.br";
$netbios   = "UNIOESTE\\"; 

$erro = "";

// Captura se veio redirecionado da trava de sessão por inatividade
if (isset($_GET['erro']) && $_GET['erro'] === 'sessao_expirada') {
    $erro = "Sua sessão expirou por inatividade. Faça login novamente.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = trim($_POST['senha'] ?? '');

    if (empty($usuario) || empty($senha)) {
        $erro = "Preencha usuário e senha.";
    } else {
        
        $ldap = ldap_connect($ldap_host, $ldap_port);

        if ($ldap) {
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            $ldap_user = (strpos($usuario, '@') === false) ? $usuario . $domain : $usuario;
            
            $bind = @ldap_bind($ldap, $ldap_user, $senha);

            if (!$bind && strpos($usuario, '@') === false) {
                $bind = @ldap_bind($ldap, $netbios . $usuario, $senha);
            }

            if ($bind) {
                $usuario_escaped = ldap_escape($usuario, "", LDAP_ESCAPE_FILTER);
                $filter = "(|(sAMAccountName=$usuario_escaped)(userPrincipalName=$usuario_escaped$domain))";
                $search = @ldap_search($ldap, $base_dn, $filter);
                
                $nome = $usuario;
                if ($search) {
                    $entries = ldap_get_entries($ldap, $search);
                    if ($entries["count"] > 0) {
                        $nome = $entries[0]["displayname"][0] ?? $usuario;
                    }
                }

                $_SESSION['usuario'] = $usuario;
                $_SESSION['nome']    = $nome;
                $_SESSION['ultimo_clique'] = time(); 

                header("Location: dashboard.php");
                exit;

            } else {
                ldap_get_option($ldap, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
                
                if (!empty($extended_error)) {
                    if (strpos($extended_error, 'data 52e') !== false) {
                        $erro = "Usuário ou senha incorretos.";
                    } else if (strpos($extended_error, 'data 533') !== false) {
                        $erro = "Esta conta institucional está bloqueada ou desativada.";
                    } else {
                        $erro = "Erro na autenticação institucional. Detalhes: " . $extended_error;
                    }
                } else {
                    $erro = "Usuário/Senha incorretos ou o servidor AD está inacessível.";
                }
            }

            ldap_close($ldap);
        } else {
            $erro = "Não foi possível conectar ao servidor de autenticação.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Empréstimos - UNIOESTE</title>

    <link rel="icon" type="image/png" href="logo256_unioeste.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background-color: #ffffff;
            font-family: 'Segoe UI', Roboto, sans-serif;
            /* CORREÇÃO: era overflow: hidden — bloqueava o scroll quando o teclado virtual abria em mobile */
            overflow-x: hidden;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
        }

        /* LADO ESQUERDO: Formulário de Login */
        .login-form-area {
            width: 100%;
            max-width: 480px;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.05);
            z-index: 2;
        }

        .logo-unioeste {
            width: 220px;
            display: block;
            margin: 0 auto 35px auto;
        }

        .login-header-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0b2265;
            margin-bottom: 8px;
            text-align: center;
        }

        .login-header-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 30px;
            text-align: center;
        }

        .input-group-text {
            background-color: #f9fafb;
            border-color: #d1d5db;
            color: #9ca3af;
            border-radius: 10px 0 0 10px;
        }

        .form-control {
            border-color: #d1d5db;
            height: 48px;
            font-size: 0.95rem;
            border-radius: 0 10px 10px 0;
        }

        .form-control:focus {
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.15);
        }

        .btn-login {
            width: 100%;
            height: 48px;
            background-color: #0b2265;
            color: #ffffff;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            transition: background-color 0.2s ease;
            font-size: 1rem;
        }

        .btn-login:hover {
            background-color: #1d4ed8;
            color: #ffffff;
        }

        .error-container {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
        }

        .error-container i {
            font-size: 1.1rem;
            margin-right: 10px;
            flex-shrink: 0;
        }

        /* LADO DIREITO: Foto Institucional */
        .login-sidebar {
            flex: 1;
            position: relative;
            background-image: url('fundo-unioeste.png');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 50px;
            color: #ffffff;
            z-index: 1;
        }

        .login-sidebar::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg, 
                rgba(11, 34, 101, 0.2) 0%, 
                rgba(5, 15, 45, 0.65) 100%
            );
            z-index: -1;
        }

        .sidebar-brand-title {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 5px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
            position: relative;
            z-index: 2;
        }

        .sidebar-brand-subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);
            position: relative;
            z-index: 2;
        }

        /* =============================================
           RESPONSIVIDADE
           ============================================= */

        /* Tablet (até 991px): oculta a foto, formulário ocupa tela toda */
        @media (max-width: 991.98px) {
            .login-sidebar {
                display: none;
            }
            .login-form-area {
                max-width: 100%;
                padding: 40px 30px;
            }
        }

        /* Mobile médio (até 576px): reduz padding e logo */
        @media (max-width: 576px) {
            .login-form-area {
                padding: 32px 20px;
                justify-content: flex-start;
                padding-top: 48px;
            }
            .logo-unioeste {
                width: 160px;
                margin-bottom: 24px;
            }
            .login-header-title {
                font-size: 1.4rem;
            }
            .login-header-subtitle {
                font-size: 0.875rem;
                margin-bottom: 20px;
            }
        }

        /* Mobile pequeno (até 380px): ajuste fino para telas de 320px */
        @media (max-width: 380px) {
            .login-form-area {
                padding: 32px 16px;
            }
            .btn-login {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<div class="login-container">

    <div class="login-form-area">
        
        <img class="logo-unioeste" src="logo_unioeste.png" alt="Logo UNIOESTE">

        <h2 class="login-header-title">Acesse o Painel</h2>
        <p class="login-header-subtitle">Utilize suas credenciais institucionais para entrar</p>

        <?php if (!empty($erro)): ?>
            <div class="error-container shadow-sm">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-secondary small fw-bold">Usuário</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                    <input type="text" name="usuario" class="form-control" placeholder="ex: joao.silva" required autocomplete="username">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-secondary small fw-bold">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="senha" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <button class="btn btn-login shadow-sm" type="submit">
                Entrar <i class="fa-solid fa-arrow-right-to-bracket ms-1" style="font-size: 0.9rem;"></i>
            </button>
        </form>

    </div>

    <div class="login-sidebar">
        <div>
            <h1 class="sidebar-brand-title">Controle de Empréstimos</h1>
            <p class="sidebar-brand-subtitle">Campus Marechal Cândido Rondon</p>
        </div>
    </div>

</div>

</body>
</html>