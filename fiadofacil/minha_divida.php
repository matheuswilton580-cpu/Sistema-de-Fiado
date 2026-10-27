<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['usuario_nivel'] !== 'cliente') {
    header("Location: dashboard.php");
    exit();
}
require_once "conexao.php";
$id_usuario = (int)$_SESSION['usuario_id'];
$stmt_usuario = $conn->prepare("SELECT status, cliente_id FROM usuarios WHERE id = ? AND nivel = 'cliente'");
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$dados_usuario = $stmt_usuario->get_result()->fetch_assoc();
$stmt_usuario->close();
if (!$dados_usuario || $dados_usuario['status'] !== 'aprovado' || !$dados_usuario['cliente_id']) {
    $conn->close();
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$id_cliente = (int)$dados_usuario['cliente_id'];
$stmt_cliente = $conn->prepare("SELECT nome, limite_credito FROM clientes WHERE id = ?");
$stmt_cliente->bind_param("i", $id_cliente);
$stmt_cliente->execute();
$cliente = $stmt_cliente->get_result()->fetch_assoc();
$stmt_cliente->close();
$stmt_compras = $conn->prepare("SELECT descricao, valor, data_compra FROM compras WHERE cliente_id = ? ORDER BY data_compra DESC");
$stmt_compras->bind_param("i", $id_cliente);
$stmt_compras->execute();
$resultado_compras = $stmt_compras->get_result();
$stmt_pagamentos = $conn->prepare("SELECT valor, data_pagamento FROM pagamentos WHERE cliente_id = ? ORDER BY data_pagamento DESC");
$stmt_pagamentos->bind_param("i", $id_cliente);
$stmt_pagamentos->execute();
$resultado_pagamentos = $stmt_pagamentos->get_result();
$total_compras = 0;
$lista_compras = [];
while ($linha = $resultado_compras->fetch_assoc()) { $total_compras += $linha['valor']; $lista_compras[] = $linha; }
$total_pagamentos = 0;
$lista_pagamentos = [];
while ($linha = $resultado_pagamentos->fetch_assoc()) { $total_pagamentos += $linha['valor']; $lista_pagamentos[] = $linha; }
$saldo_devedor = $total_compras - $total_pagamentos;
$limite_disponivel = $cliente['limite_credito'] - $saldo_devedor;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Minha Dívida — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<div class="container">
    <h1>Minha Dívida</h1>
    <p class="boas-vindas">Olá, <?php echo htmlspecialchars($cliente['nome']); ?>!</p>
    <div class="cards">
        <div class="card <?php echo $saldo_devedor > 0 ? 'card-alerta' : 'card-ok'; ?>">
            <span class="card-titulo">Quanto você deve</span>
            <span class="card-valor">R$ <?php echo number_format(max(0, $saldo_devedor), 2, ',', '.'); ?></span>
        </div>
        <div class="card">
            <span class="card-titulo">Limite de crédito</span>
            <span class="card-valor">R$ <?php echo number_format($cliente['limite_credito'], 2, ',', '.'); ?></span>
        </div>
        <div class="card">
            <span class="card-titulo">Limite disponível</span>
            <span class="card-valor">R$ <?php echo number_format(max(0, $limite_disponivel), 2, ',', '.'); ?></span>
        </div>
    </div>
    <h3>Minhas compras</h3>
    <table>
        <tr><th>Data</th><th>Descrição</th><th>Valor</th></tr>
        <?php foreach ($lista_compras as $compra): ?>
        <tr>
            <td><?php echo date('d/m/Y', strtotime($compra['data_compra'])); ?></td>
            <td><?php echo htmlspecialchars($compra['descricao'] ?? ''); ?></td>
            <td>R$ <?php echo number_format($compra['valor'], 2, ',', '.'); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (count($lista_compras) === 0): ?>
        <tr><td colspan="3" style="text-align:center">Nenhuma compra registrada.</td></tr>
        <?php endif; ?>
    </table>
    <h3>Meus pagamentos</h3>
    <table>
        <tr><th>Data</th><th>Valor</th></tr>
        <?php foreach ($lista_pagamentos as $pagamento): ?>
        <tr>
            <td><?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?></td>
            <td>R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (count($lista_pagamentos) === 0): ?>
        <tr><td colspan="2" style="text-align:center">Nenhum pagamento registrado.</td></tr>
        <?php endif; ?>
    </table>
    <a href="logout.php"><button class="btn-sair">Sair</button></a>
</div>
</body>
</html>
<?php
$stmt_compras->close();
$stmt_pagamentos->close();
$conn->close();
?>
