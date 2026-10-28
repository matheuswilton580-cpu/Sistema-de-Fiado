<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}
require_once "conexao.php";
$mensagem = "";
$tipo = "mensagem";
$nome = $telefone = $usuario = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $usuario  = trim($_POST['usuario']);
    $senha    = $_POST['senha'];
    if ($nome === "" || $telefone === "" || $usuario === "") {
        $mensagem = "Preencha nome, telefone e usuário.";
        $tipo = "erro";
    } elseif (strlen($senha) < 6) {
        $mensagem = "A senha deve ter no mínimo 6 caracteres.";
        $tipo = "erro";
    } else {
        $stmt_dup = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt_dup->bind_param("s", $usuario);
        $stmt_dup->execute();
        $stmt_dup->store_result();
        $duplicado = $stmt_dup->num_rows > 0;
        $stmt_dup->close();
        if ($duplicado) {
            $mensagem = "Este nome de usuário já está em uso.";
            $tipo = "erro";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, usuario, senha, nivel, status, telefone_solicitado) VALUES (?, ?, ?, 'cliente', 'pendente', ?)");
            $stmt->bind_param("ssss", $nome, $usuario, $hash, $telefone);
            if ($stmt->execute()) {
                $mensagem = "Solicitação enviada! Aguarde o administrador aprovar para você conseguir entrar.";
                $nome = $telefone = $usuario = "";
            } else {
                $mensagem = "Erro ao enviar a solicitação.";
                $tipo = "erro";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Solicitar Acesso — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<div class="container">
    <h1>Solicitar Acesso de Cliente</h1>
    <p class="boas-vindas">Com este acesso você só consegue ver a sua própria dívida.</p>
    <?php if ($mensagem): ?>
        <p class="<?php echo $tipo; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>
    <form method="POST" action="solicitar_acesso.php">
        <input type="text" name="nome" placeholder="Nome completo" value="<?php echo htmlspecialchars($nome); ?>" required>
        <input type="text" name="telefone" placeholder="Telefone cadastrado na loja" value="<?php echo htmlspecialchars($telefone); ?>" required>
        <input type="text" name="usuario" placeholder="Usuário (login)" value="<?php echo htmlspecialchars($usuario); ?>" required>
        <input type="password" name="senha" placeholder="Senha (mínimo 6 caracteres)" required>
        <button type="submit">Enviar solicitação</button>
    </form>
    <a href="login.php"><button class="btn-sair">Voltar ao login</button></a>
</div>
</body>
</html>
