<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}
$erro = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once "conexao.php";
    $usuario = trim($_POST['usuario']);
    $senha   = trim($_POST['senha']);
    $stmt = $conn->prepare("SELECT id, nome, senha, nivel, status, cliente_id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && password_verify($senha, $row['senha'])) {
        if ($row['status'] === 'pendente') {
            $erro = "Sua solicitação de acesso ainda está aguardando a aprovação do administrador.";
        } elseif ($row['status'] === 'recusado') {
            $erro = "Sua solicitação de acesso foi recusada pelo administrador.";
        } elseif ($row['status'] === 'bloqueado') {
            $erro = "Seu acesso foi bloqueado pelo administrador.";
        } else {
            session_regenerate_id(true);
            $_SESSION['usuario_id']    = $row['id'];
            $_SESSION['usuario_nome']  = $row['nome'];
            $_SESSION['usuario_nivel'] = $row['nivel'];
            $_SESSION['cliente_id']    = $row['cliente_id'];
            $conn->close();
            if ($row['nivel'] === 'cliente') {
                header("Location: minha_divida.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        }
    } else {
        $erro = "Usuário ou senha inválidos.";
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<div class="container">
    <h1>Sistema Fiado</h1>
    <?php if ($erro): ?>
        <p class="erro"><?php echo htmlspecialchars($erro); ?></p>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <input type="text"     name="usuario" placeholder="Usuário" required>
        <input type="password" name="senha"   placeholder="Senha"   required>
        <button type="submit">Entrar</button>
    </form>
    <a href="solicitar_acesso.php"><button class="btn-sair">Sou cliente e quero ver minha dívida (solicitar acesso)</button></a>
</div>
</body>
</html>
