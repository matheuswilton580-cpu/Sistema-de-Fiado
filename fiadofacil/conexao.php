<?php
// Configuração do MySQL no InfinityFree.
// IMPORTANTE: substitua a senha abaixo pela senha MySQL definida no painel da hospedagem.
$host = "sql205.infinityfree.com";
$usuario = "if0_43295038";
$senha = "StonkS13";
$banco = "if0_43295038_fiado";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $usuario, $senha, $banco);
if ($conn->connect_error) {
    error_log("Falha na conexão MySQL: " . $conn->connect_error);
    http_response_code(500);
    die("Não foi possível conectar ao banco de dados. Confira as credenciais em conexao.php.");
}
$conn->set_charset("utf8mb4");
?>
