<?php
$host   = ";
$banco  = "atp";
$usuario = "";
$senha  = "";

try {
    $dsn = "mysql:host=$host;dbname=$banco;charset=utf8mb4";
    $pdo = new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    echo "Conexão PDO bem-sucedida!";
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
