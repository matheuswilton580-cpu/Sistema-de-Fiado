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

    // Busca o usuário pelo login (a senha é conferida depois com password_verify)
    $stmt = $conn->prepare("SELECT id, nome, senha, nivel FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && password_verify($senha, $row['senha'])) {
        // Gera um novo ID de sessão ao logar (evita fixação de sessão)
        session_regenerate_id(true);

        $_SESSION['usuario_id']    = $row['id'];
        $_SESSION['usuario_nome']  = $row['nome'];
        $_SESSION['usuario_nivel'] = $row['nivel'];   // 'admin' ou 'comum'

        $conn->close();
        header("Location: dashboard.php");
        exit();
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
</div>

</body>
</html>
