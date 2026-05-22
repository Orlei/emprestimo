<?php
// funcionarios_busca_ldap.php (Focado 100% em diagnosticar o Active Directory)
require_once 'encerra_sessao.php';
header('Content-Type: application/json; charset=utf-8');

// Permite ver erros se eles acontecerem, mas vamos tentar capturar tudo no JSON
ini_set('display_errors', 0); 

$busca = filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW);
if (!$busca || strlen($busca) < 3) {
    echo json_encode([]);
    exit;
}

$resultados = [];

// ========================================================
// CONFIGURAÇÃO DO ACTIVE DIRECTORY DA UNIOESTE via SESSÃO
// ========================================================
$ldap_host = "10.88.201.2";
$ldap_port = 389;
$base_dn   = "DC=unioeste,DC=br";
$netbios   = "UNIOESTE\\";

// Puxa as credenciais da sessão
$ldap_user = $_SESSION['usuario'] ?? ''; 
$ldap_pass = $_SESSION['senha_ldap'] ?? '';

if (empty($ldap_user) || empty($ldap_pass)) {
    echo json_encode(['erro' => 'Sessão inválida ou sem senha. Faça login novamente.']);
    exit;
}

$ldap_conn = ldap_connect($ldap_host, $ldap_port);
if (!$ldap_conn) {
    echo json_encode(['erro' => 'Não foi possível conectar ao servidor LDAP.']);
    exit;
}

ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

// ========================================================
// TESTADOR AUTOMÁTICO DE FORMATOS DE LOGIN (AD BIND)
// ========================================================
$login_puro = str_replace($netbios, "", $ldap_user); // Remove o prefixo se já existir
$login_puro = explode("@", $login_puro)[0];         // Remove o sufixo se já existir

// Formato 1: UNIOESTE\usuario (Padrão NetBIOS)
$tentativa_1 = $netbios . $login_puro;

// Formato 2: usuario@unioeste.br (Padrão UPN)
$tentativa_2 = $login_puro . "@unioeste.br";

// Formato 3: apenas 'usuario' (Padrão simples)
$tentativa_3 = $login_puro;

$bind_sucesso = false;

// Tenta o Formato 1
if (@ldap_bind($ldap_conn, $tentativa_1, $ldap_pass)) {
    $bind_sucesso = true;
} 
// Se falhar, tenta o Formato 2
elseif (@ldap_bind($ldap_conn, $tentativa_2, $ldap_pass)) {
    $bind_sucesso = true;
} 
// Se falhar, tenta o Formato 3
elseif (@ldap_bind($ldap_conn, $tentativa_3, $ldap_pass)) {
    $bind_sucesso = true;
}

// Se nenhuma das 3 formas de login funcionar no AD
if (!$bind_sucesso) {
    $error_msg = ldap_error($ldap_conn);
    echo json_encode(['erro' => "Falha de autenticação com a sessão atual no AD: $error_msg. Tentado: $tentativa_1, $tentativa_2 e $tentativa_3."]);
    ldap_unbind($ldap_conn);
    exit;
}
// ========================================================

// ... daqui para baixo segue o código normal de busca ...
// ... daqui para baixo o restante do código de busca continua exatamente igual ...

// ... código anterior de autenticação/bind igual ...

// Filtro do AD continua o mesmo
// ... código anterior igual ...

$filtro = "(|(samaccountname=*$busca*)(cn=*$busca*))";

// 1. ADICIONADO: 'memberof' na lista de atributos solicitados ao AD
$atributos = ["samaccountname", "cn", "mail", "department", "title", "description", "distinguishedname", "memberof"];

$search = @ldap_search($ldap_conn, $base_dn, $filtro, $atributos);

if (!$search) {
    $error_msg = ldap_error($ldap_conn);
    echo json_encode(['erro' => "Erro na busca do AD: $error_msg"]);
    ldap_unbind($ldap_conn);
    exit;
}

$entries = ldap_get_entries($ldap_conn, $search);

for ($i = 0; $i < $entries["count"]; $i++) {
    $login_ldap = $entries[$i]["samaccountname"][0] ?? '';
    
    if (!empty($login_ldap)) {
        $title   = $entries[$i]["title"][0] ?? '';
        $desc    = $entries[$i]["description"][0] ?? '';
        $dn      = $entries[$i]["distinguishedname"][0] ?? '';
        $setor   = $entries[$i]["department"][0] ?? '';
        $email   = $entries[$i]["mail"][0] ?? '';
        
        // Captura a lista de grupos (memberof)
        $grupos_array = $entries[$i]["memberof"] ?? [];
        $grupos_string = "";
        if (isset($grupos_array['count'])) {
            for ($g = 0; $g < $grupos_array['count']; $g++) {
                $grupos_string .= " " . strtolower($grupos_array[$g]);
            }
        }
        
        $vinculo = "Não Informado";
        $detalhe = $setor;
        
        $dn_lower    = strtolower($dn);
        $desc_lower  = strtolower($desc);
        $setor_lower = strtolower($setor);
        
        // 2. CRITÉRIO PREFERENCIAL: Investigação pelos Grupos do AD (memberof)
        if (!empty($grupos_string)) {
            if (strpos($grupos_string, 'aluno') !== false || strpos($grupos_string, 'discente') !== false || strpos($grupos_string, 'graduacao') !== false) {
                $vinculo = "Aluno";
            } elseif (strpos($grupos_string, 'docente') !== false || strpos($grupos_string, 'professor') !== false) {
                $vinculo = "Professor";
            } elseif (strpos($grupos_string, 'servidor') !== false || strpos($grupos_string, 'agente') !== false || strpos($grupos_string, 'tecnico') !== false) {
                $vinculo = "Servidor";
            }
        }
        
        // 3. CRITÉRIO SECUNDÁRIO (Caso os grupos não tenham desempatado)
        if ($vinculo === "Não Informado") {
            if (strpos($dn_lower, 'aluno') !== false || strpos($desc_lower, 'aluno') !== false) {
                $vinculo = "Aluno";
            } elseif (strpos($dn_lower, 'professor') !== false || strpos($dn_lower, 'docente') !== false) {
                $vinculo = "Professor";
            } elseif (strpos($dn_lower, 'servidor') !== false || strpos($dn_lower, 'agente') !== false) {
                $vinculo = "Servidor";
            }
        }
        
        // 4. REGRAS DE INFERÊNCIA BASEADAS EM SIGLAS (Último recurso)
        if ($vinculo === "Não Informado") {
            if (strpos($setor_lower, 'clg') !== false || strpos($setor_lower, 'colegiado') !== false) {
                $vinculo = "Aluno";
                $detalhe = str_replace(['clg', 'Clg'], 'Colegiado de ', $setor);
            } 
            elseif (preg_match('/(ccbs|cchs|cece|cca|ccs|chel|ccsa|ccet)/', $setor_lower)) {
                // Se cair no centro acadêmico puro e não desempatou nos grupos,
                // deixamos uma tag combinada flexível para o operador ver na tela
                $vinculo = "Acadêmico / Centro";
                $detalhe = $setor;
            }
            elseif (strpos($setor_lower, 'hu-') !== false || strpos($setor_lower, 'hu') !== false) {
                $vinculo = "Servidor (HU)";
                $detalhe = $setor;
            }
            elseif (empty($setor)) {
                $vinculo = "Aluno / Perfil Extern";
                $detalhe = "Sem setor mapeado no AD";
            }
            else {
                $vinculo = "Servidor";
                $detalhe = $setor;
            }
        }

        // Força "Professor" ou "Servidor" caso o cargo 'title' esteja preenchido explicitamente
        if (!empty($title)) {
            $vinculo = (strpos(strtolower($title), 'prof') !== false) ? "Professor" : "Servidor";
            $detalhe = $title . (!empty($setor) ? " (" . $setor . ")" : "");
        }

        $resultados[] = [
            'login'   => $login_ldap,
            'nome'    => $entries[$i]["cn"][0] ?? '',
            'email'   => $email,
            'setor'   => $setor,
            'vinculo' => $vinculo,
            'detalhe' => $detalhe,
            'origem'  => 'ldap'
        ];
    }
    if (count($resultados) >= 10) break;
}

ldap_unbind($ldap_conn);
echo json_encode($resultados);