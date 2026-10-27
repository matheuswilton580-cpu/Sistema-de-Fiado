<?php
require_once "verifica_login.php";
require_once "conexao.php";

$mensagem = "";
$tipo     = "mensagem";

// Valida o ID recebido pela URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: listar_clientes.php");
    exit();
}

// Processa o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $endereco = trim($_POST['endereco']);

    if (eh_admin()) {
        $limite_credito = floatval($_POST['limite_credito']);
    } else {
        // REGRA: usuário comum não altera o limite — mantém o valor atual do banco
        $stmt_lim = $conn->prepare("SELECT limite_credito FROM clientes WHERE id = ?");
        $stmt_lim->bind_param("i", $id);
        $stmt_lim->execute();
        $limite_credito = (float)($stmt_lim->get_result()->fetch_assoc()['limite_credito'] ?? 0);
        $stmt_lim->close();
    }

    if ($nome === "") {
        $mensagem = "O nome do cliente é obrigatório.";
        $tipo = "erro";
    } elseif ($limite_credito < 0) {
        $mensagem = "O limite de crédito não pode ser negativo.";
        $tipo = "erro";
    } else {
        // REGRA: telefone duplicado (ignorando o próprio cliente)
        $duplicado = false;
        if ($telefone !== "") {
            $stmt_dup = $conn->prepare("SELECT id FROM clientes WHERE telefone = ? AND id <> ?");
            $stmt_dup->bind_param("si", $telefone, $id);
            $stmt_dup->execute();
            $stmt_dup->store_result();
            $duplicado = $stmt_dup->num_rows > 0;
            $stmt_dup->close();
        }

        if ($duplicado) {
            $mensagem = "Já existe outro cliente cadastrado com este telefone.";
            $tipo = "erro";
        } else {
            $stmt = $conn->prepare("UPDATE clientes SET nome=?, telefone=?, endereco=?, limite_credito=? WHERE id=?");
            $stmt->bind_param("sssdi", $nome, $telefone, $endereco, $limite_credito, $id);

            if ($stmt->execute()) {
                $mensagem = "Dados atualizados com sucesso!";
            } else {
                $mensagem = "Erro ao atualizar cliente.";
                $tipo = "erro";
            }
            $stmt->close();
        }
    }
}

// Busca os dados atuais do cliente
$stmt = $conn->prepare("SELECT nome, telefone, endereco, limite_credito FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: listar_clientes.php");
    exit();
}

$cliente = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Editar Cliente</h1>

    <?php if ($mensagem): ?>
        <p class="<?php echo $tipo; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <form method="POST" action="editar_cliente.php?id=<?php echo $id; ?>">
        <input type="text" name="nome"     placeholder="Nome completo"
               value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
        <input type="text" name="telefone" placeholder="Telefone"
               value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>">
        <input type="text" name="endereco" placeholder="Endereço"
               value="<?php echo htmlspecialchars($cliente['endereco'] ?? ''); ?>">

        <?php if (eh_admin()): ?>
            <input type="number" name="limite_credito" placeholder="Limite de crédito (R$)" step="0.01" min="0"
                   value="<?php echo $cliente['limite_credito']; ?>">
        <?php else: ?>
            <input type="text" disabled
                   value="Limite: R$ <?php echo number_format($cliente['limite_credito'], 2, ',', '.'); ?> (somente o administrador altera)">
        <?php endif; ?>

        <button type="submit">Salvar Alterações</button>
    </form>

    <a href="listar_clientes.php"><button>Voltar</button></a>
</div>

</body>
</html>
