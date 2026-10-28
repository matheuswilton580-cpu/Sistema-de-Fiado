<?php
// Configurações de conexão com o banco de dados MySQL
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistema_fiado";

// Cria a conexão usando mysqli
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Erro ao conectar com o banco: " . $conn->connect_error);
}

// Define o charset como UTF-8 para suportar caracteres especiais
$conn->set_charset("utf8");
?>
