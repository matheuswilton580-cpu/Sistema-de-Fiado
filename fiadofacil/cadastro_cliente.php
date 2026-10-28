<?php
require_once "verifica_login.php";
require_once "conexao.php";

$mensagem = "";
$tipo     = "mensagem";
$nome = $telefone = $endereco = "";
$limite_credito = 500.00;

// Processa o formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $endereco = trim($_POST['endereco']);

    // REGRA: só o administrador define o limite; usuário comum usa o padrão (R$ 500)
    $limite_credito = eh_admin() ? floatval($_POST['limite_credito']) : 500.00;

    if ($nome === "") {
        $mensagem = "O nome do cliente é obrigatório.";
        $tipo = "erro";
    } elseif ($limite_credito < 0) {
        // REGRA 1: limite de crédito não pode ser negativo
        $mensagem = "O limite de crédito não pode ser negativo.";
        $tipo = "erro";
    } else {
        // REGRA 2: não permitir telefone duplicado
        $duplicado = false;
        if ($telefone !== "") {
            $stmt_dup = $conn->prepare("SELECT id FROM clientes WHERE telefone = ?");
            $stmt_dup->bind_param("s", $telefone);
            $stmt_dup->execute();
            $stmt_dup->store_result();
            $duplicado = $stmt_dup->num_rows > 0;
            $stmt_dup->close();
        }

        if ($duplicado) {
            $mensagem = "Já existe um cliente cadastrado com este telefone.";
            $tipo = "erro";
        } else {
            $stmt = $conn->prepare("INSERT INTO clientes (nome, telefone, endereco, limite_credito) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $nome, $telefone, $endereco, $limite_credito);

            if ($stmt->execute()) {
                $mensagem = "Cliente cadastrado com sucesso!";
                $nome = $telefone = $endereco = "";
                $limite_credito = 500.00;
            } else {
                $mensagem = "Erro ao cadastrar cliente.";
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
    <title>Cadastrar Cliente — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Cadastrar Cliente</h1>

    <?php if ($mensagem): ?>
        <p class="<?php echo $tipo; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <form method="POST" action="cadastro_cliente.php">
        <input type="text" name="nome"     placeholder="Nome completo" value="<?php echo htmlspecialchars($nome); ?>" required>
        <input type="text" name="telefone" placeholder="Telefone"      value="<?php echo htmlspecialchars($telefone); ?>">
        <input type="text" name="endereco" placeholder="Endereço"      value="<?php echo htmlspecialchars($endereco); ?>">

        <?php if (eh_admin()): ?>
            <input type="number" name="limite_credito" placeholder="Limite de crédito (R$)" step="0.01" min="0"
                   value="<?php echo $limite_credito; ?>">
        <?php else: ?>
            <input type="text" value="Limite padrão: R$ 500,00 (somente o administrador altera)" disabled>
        <?php endif; ?>

        <button type="submit">Cadastrar</button>
    </form>

    <a href="dashboard.php"><button>Voltar</button></a>
</div>

</body>
</html>
