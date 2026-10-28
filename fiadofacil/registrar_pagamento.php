<?php
require_once "verifica_login.php";
require_once "conexao.php";

$mensagem = "";
$tipo     = "mensagem";

// Processa o formulário de pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)$_POST['cliente_id'];
    $valor      = round(floatval($_POST['valor']), 2);

    if ($cliente_id <= 0 || $valor <= 0) {
        $mensagem = "Selecione um cliente e informe um valor válido.";
        $tipo = "erro";
    } else {
        // Calcula a dívida atual do cliente
        $stmt_saldo = $conn->prepare("
            SELECT COALESCE((SELECT SUM(valor) FROM compras    WHERE cliente_id = ?), 0)
                 - COALESCE((SELECT SUM(valor) FROM pagamentos WHERE cliente_id = ?), 0) AS saldo
        ");
        $stmt_saldo->bind_param("ii", $cliente_id, $cliente_id);
        $stmt_saldo->execute();
        $saldo = (float)$stmt_saldo->get_result()->fetch_assoc()['saldo'];
        $stmt_saldo->close();

        if ($saldo <= 0) {
            // REGRA: cliente sem dívida não recebe pagamento
            $mensagem = "Este cliente não possui dívida em aberto.";
            $tipo = "erro";
        } elseif ($valor > $saldo) {
            // REGRA: o pagamento não pode ser maior que a dívida
            $mensagem = "O valor pago (R$ " . number_format($valor, 2, ',', '.') .
                        ") é maior que a dívida (R$ " . number_format($saldo, 2, ',', '.') . ").";
            $tipo = "erro";
        } else {
            $stmt = $conn->prepare("INSERT INTO pagamentos (cliente_id, valor) VALUES (?, ?)");
            $stmt->bind_param("id", $cliente_id, $valor);

            if ($stmt->execute()) {
                $mensagem = "Pagamento registrado com sucesso!";
            } else {
                $mensagem = "Erro ao registrar pagamento.";
                $tipo = "erro";
            }
            $stmt->close();
        }
    }
}

// Busca clientes com saldo devedor (depois do POST, para a lista já sair atualizada)
$clientes = $conn->query("
    SELECT c.id, c.nome,
           COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id), 0)
           - COALESCE((SELECT SUM(p.valor)  FROM pagamentos p  WHERE p.cliente_id = c.id), 0)
           AS saldo_devedor
    FROM clientes c
    HAVING saldo_devedor > 0
    ORDER BY c.nome
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registrar Pagamento — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Registrar Pagamento</h1>

    <?php if ($mensagem): ?>
        <p class="<?php echo $tipo; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <form method="POST" action="registrar_pagamento.php">
        <select name="cliente_id" required>
            <option value="">Selecione o cliente...</option>
            <?php while ($c = $clientes->fetch_assoc()): ?>
                <option value="<?php echo $c['id']; ?>">
                    <?php echo htmlspecialchars($c['nome']); ?>
                    — Deve: R$ <?php echo number_format($c['saldo_devedor'], 2, ',', '.'); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="number" name="valor" placeholder="Valor pago (R$)" step="0.01" min="0.01" required>

        <button type="submit">Registrar Pagamento</button>
    </form>

    <a href="dashboard.php"><button>Voltar</button></a>
</div>

</body>
</html>
<?php $conn->close(); ?>
