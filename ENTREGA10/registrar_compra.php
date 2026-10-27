<?php
require_once "verifica_login.php";
require_once "conexao.php";

$mensagem = "";
$tipo     = "mensagem";

// Processa o formulário de registro de compra
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    $descricao  = trim($_POST['descricao']);
    $valor      = round(floatval($_POST['valor']), 2);

    if ($cliente_id <= 0 || $valor <= 0) {
        $mensagem = "Selecione um cliente e informe um valor válido.";
        $tipo = "erro";
    } else {
        // Chama a stored procedure transacional (valida limite de crédito e grava a compra)
        $stmt = $conn->prepare("CALL sp_registrar_compra(?, ?, ?, @ok, @msg)");
        $stmt->bind_param("isd", $cliente_id, $descricao, $valor);
        $stmt->execute();
        $stmt->close();

        // Lê os parâmetros de saída (OUT) da procedure
        $saida    = $conn->query("SELECT @ok AS ok, @msg AS msg")->fetch_assoc();
        $mensagem = $saida['msg'];
        $tipo     = ($saida['ok'] == 1) ? "mensagem" : "erro";
    }
}

// Busca lista de clientes para o <select>
$clientes = $conn->query("SELECT id, nome FROM clientes ORDER BY nome");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registrar Compra — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Registrar Compra no Fiado</h1>

    <?php if ($mensagem): ?>
        <p class="<?php echo $tipo; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <form method="POST" action="registrar_compra.php">
        <select name="cliente_id" required>
            <option value="">Selecione o cliente...</option>
            <?php while ($c = $clientes->fetch_assoc()): ?>
                <option value="<?php echo $c['id']; ?>">
                    <?php echo htmlspecialchars($c['nome']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="text"   name="descricao" placeholder="Descrição (ex: Arroz e feijão)">
        <input type="number" name="valor"      placeholder="Valor (R$)" step="0.01" min="0.01" required>

        <button type="submit">Registrar Compra</button>
    </form>

    <a href="dashboard.php"><button>Voltar</button></a>
</div>

</body>
</html>
<?php $conn->close(); ?>
