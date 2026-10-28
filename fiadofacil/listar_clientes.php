<?php
require_once "verifica_login.php";
require_once "conexao.php";

// Paginação: 10 registros por página
$por_pagina = 10;
$pagina     = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $por_pagina;

// Busca por nome
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : "";

// Conta o total de registros para montar a paginação
if ($busca !== "") {
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE nome LIKE ?");
    $like = "%" . $busca . "%";
    $stmt_count->bind_param("s", $like);
} else {
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM clientes");
}
$stmt_count->execute();
$stmt_count->bind_result($total);
$stmt_count->fetch();
$stmt_count->close();

$total_paginas = ceil($total / $por_pagina);

// Busca os clientes da página atual com saldo devedor calculado
if ($busca !== "") {
    $stmt = $conn->prepare("
        SELECT c.id, c.nome, c.telefone,
               COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id), 0)
               - COALESCE((SELECT SUM(p.valor)  FROM pagamentos p  WHERE p.cliente_id = c.id), 0)
               AS saldo_devedor
        FROM clientes c
        WHERE c.nome LIKE ?
        ORDER BY c.nome
        LIMIT ? OFFSET ?
    ");
    $like = "%" . $busca . "%";
    $stmt->bind_param("sii", $like, $por_pagina, $offset);
} else {
    $stmt = $conn->prepare("
        SELECT c.id, c.nome, c.telefone,
               COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id), 0)
               - COALESCE((SELECT SUM(p.valor)  FROM pagamentos p  WHERE p.cliente_id = c.id), 0)
               AS saldo_devedor
        FROM clientes c
        ORDER BY c.nome
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $por_pagina, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Clientes — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Clientes Cadastrados</h1>

    <?php mostrar_flash(); ?>

    <!-- Formulário de busca -->
    <form method="GET" action="listar_clientes.php" class="form-busca">
        <input type="text" name="busca" placeholder="Buscar por nome..."
               value="<?php echo htmlspecialchars($busca); ?>">
        <button type="submit">Buscar</button>
    </form>

    <table>
        <tr>
            <th>Nome</th>
            <th>Telefone</th>
            <th>Saldo Devedor</th>
            <th>Ações</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['nome']); ?></td>
            <td><?php echo htmlspecialchars($row['telefone'] ?? ''); ?></td>
            <td>R$ <?php echo number_format($row['saldo_devedor'], 2, ',', '.'); ?></td>
            <td>
                <a href="editar_cliente.php?id=<?php echo $row['id']; ?>">
                    <button class="btn-editar">Editar</button>
                </a>
                <?php if (eh_admin()): ?>
                <!-- Exclusão (somente admin) via POST, com confirmação em JavaScript -->
                <form method="POST" action="excluir_cliente.php" style="display:inline"
                      onsubmit="return confirmarExclusao(<?php echo htmlspecialchars(json_encode($row['nome']), ENT_QUOTES); ?>)">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn-excluir">Excluir</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- Paginação -->
    <div class="paginacao">
        <?php if ($pagina > 1): ?>
            <a href="?pagina=<?php echo $pagina - 1; ?>&busca=<?php echo urlencode($busca); ?>">« Anterior</a>
        <?php endif; ?>

        <span>Página <?php echo $pagina; ?> de <?php echo $total_paginas ?: 1; ?></span>

        <?php if ($pagina < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina + 1; ?>&busca=<?php echo urlencode($busca); ?>">Próxima »</a>
        <?php endif; ?>
    </div>

    <a href="dashboard.php"><button>Voltar</button></a>
</div>

<script src="js/script.js"></script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
