<?php
require_once "verifica_login.php";
require_once "conexao.php";

// Paginação
$por_pagina = 10;
$pagina     = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $por_pagina;

// Conta total
$total = $conn->query("SELECT COUNT(*) FROM clientes")->fetch_row()[0];
$total_paginas = ceil($total / $por_pagina);

/*
  Cálculo do score (0 a 100):
  - Começa em 100 pontos
  - Perde 5 pontos por cada dia acima de 7 que o cliente está devendo
  - Perde 10 pontos se o saldo devedor for maior que 50% do limite
  - Ganha 5 pontos se o cliente já pagou alguma vez (demonstra boa fé)
*/
$stmt = $conn->prepare("
    SELECT c.nome, c.limite_credito,
           COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id), 0)
           - COALESCE((SELECT SUM(p.valor)  FROM pagamentos p  WHERE p.cliente_id = c.id), 0)
           AS saldo_devedor,
           COALESCE(DATEDIFF(NOW(),
               (SELECT MIN(co2.data_compra) FROM compras co2 WHERE co2.cliente_id = c.id)
           ), 0) AS dias_devendo,
           COALESCE((SELECT COUNT(*) FROM pagamentos p2 WHERE p2.cliente_id = c.id), 0) AS total_pagamentos
    FROM clientes c
    ORDER BY c.nome
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $por_pagina, $offset);
$stmt->execute();
$result = $stmt->get_result();

$clientes = [];
while ($row = $result->fetch_assoc()) {
    $score = 100;

    // Penaliza por dias em atraso (a partir de 7 dias)
    $dias_extras = max(0, $row['dias_devendo'] - 7);
    $score -= ($dias_extras * 2);

    // Penaliza se a dívida supera 50% do limite
    if ($row['limite_credito'] > 0 && $row['saldo_devedor'] > ($row['limite_credito'] * 0.5)) {
        $score -= 10;
    }

    // Bônus por ter feito pagamentos anteriores
    if ($row['total_pagamentos'] > 0) {
        $score += 5;
    }

    // Mantém o score entre 0 e 100
    $score = max(0, min(100, $score));

    $row['score'] = $score;
    $clientes[] = $row;
}

// Ordena pelo score (maior para menor)
usort($clientes, fn($a, $b) => $b['score'] - $a['score']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ranking de Clientes — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Ranking de Confiabilidade</h1>

    <table>
        <tr>
            <th>#</th>
            <th>Cliente</th>
            <th>Saldo Devedor</th>
            <th>Dias Devendo</th>
            <th>Score</th>
        </tr>
        <?php foreach ($clientes as $i => $row): ?>
        <tr>
            <td><?php echo $offset + $i + 1; ?>°</td>
            <td><?php echo htmlspecialchars($row['nome']); ?></td>
            <td>R$ <?php echo number_format($row['saldo_devedor'], 2, ',', '.'); ?></td>
            <td><?php echo (int)$row['dias_devendo']; ?> dias</td>
            <td>
                <strong><?php echo $row['score']; ?>/100</strong>
                <?php if ($row['score'] >= 80): ?>
                    <span class="ok">✔ Confiável</span>
                <?php elseif ($row['score'] >= 50): ?>
                    <span class="aviso">⚠ Regular</span>
                <?php else: ?>
                    <span class="alerta">✖ Risco</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Paginação -->
    <div class="paginacao">
        <?php if ($pagina > 1): ?>
            <a href="?pagina=<?php echo $pagina - 1; ?>">« Anterior</a>
        <?php endif; ?>

        <span>Página <?php echo $pagina; ?> de <?php echo $total_paginas ?: 1; ?></span>

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
