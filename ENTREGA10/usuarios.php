<?php
// Gerenciamento de usuários — somente administrador
require_once "verifica_admin.php";
require_once "conexao.php";

$mensagem = "";
$tipo     = "mensagem";
$nome = $login = "";
$nivel = "comum";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']);
    $login = trim($_POST['usuario']);
    $senha = $_POST['senha'];
    $nivel = $_POST['nivel'];

    if ($nome === "" || $login === "") {
        $mensagem = "Nome e usuário são obrigatórios.";
        $tipo = "erro";
    } elseif (!in_array($nivel, ['admin', 'comum'])) {
        $mensagem = "Nível de acesso inválido.";
        $tipo = "erro";
    } elseif (strlen($senha) < 6) {
        // REGRA: senha com no mínimo 6 caracteres
        $mensagem = "A senha deve ter no mínimo 6 caracteres.";
        $tipo = "erro";
    } else {
        // REGRA: não permitir nome de usuário duplicado
        $stmt_dup = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt_dup->bind_param("s", $login);
        $stmt_dup->execute();
        $stmt_dup->store_result();
        $duplicado = $stmt_dup->num_rows > 0;
        $stmt_dup->close();

        if ($duplicado) {
            $mensagem = "Este nome de usuário já está em uso.";
            $tipo = "erro";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, usuario, senha, nivel) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome, $login, $hash, $nivel);

            if ($stmt->execute()) {
                $mensagem = "Usuário cadastrado com sucesso!";
                $nome = $login = "";
                $nivel = "comum";
            } else {
                $mensagem = "Erro ao cadastrar usuário.";
                $tipo = "erro";
            }
            $stmt->close();
        }
    }
}

// Listagem com paginação (10 por página)
$por_pagina = 10;
$pagina     = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $por_pagina;

$total = $conn->query("SELECT COUNT(*) FROM usuarios")->fetch_row()[0];
$total_paginas = max(1, (int)ceil($total / $por_pagina));

$stmt = $conn->prepare("SELECT nome, usuario, nivel, criado_em FROM usuarios ORDER BY nome LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $por_pagina, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Usuários — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Gerenciar Usuários</h1>

    <?php if ($mensagem): ?>
        <p class="<?php echo $tipo; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <form method="POST" action="usuarios.php">
        <input type="text"     name="nome"    placeholder="Nome completo" value="<?php echo htmlspecialchars($nome); ?>" required>
        <input type="text"     name="usuario" placeholder="Usuário (login)" value="<?php echo htmlspecialchars($login); ?>" required>
        <input type="password" name="senha"   placeholder="Senha (mínimo 6 caracteres)" required>
        <select name="nivel">
            <option value="comum" <?php if ($nivel === 'comum') echo 'selected'; ?>>Usuário comum</option>
            <option value="admin" <?php if ($nivel === 'admin') echo 'selected'; ?>>Administrador</option>
        </select>
        <button type="submit">Cadastrar Usuário</button>
    </form>

    <table>
        <tr><th>Nome</th><th>Usuário</th><th>Nível</th><th>Criado em</th></tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['nome']); ?></td>
            <td><?php echo htmlspecialchars($row['usuario']); ?></td>
            <td><?php echo $row['nivel'] === 'admin' ? 'Administrador' : 'Usuário comum'; ?></td>
            <td><?php echo date('d/m/Y', strtotime($row['criado_em'])); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="paginacao">
        <?php if ($pagina > 1): ?>
            <a href="?pagina=<?php echo $pagina - 1; ?>">« Anterior</a>
        <?php endif; ?>
        <span>Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>
        <?php if ($pagina < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina + 1; ?>">Próxima »</a>
        <?php endif; ?>
    </div>

    <a href="dashboard.php"><button>Voltar</button></a>
</div>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
