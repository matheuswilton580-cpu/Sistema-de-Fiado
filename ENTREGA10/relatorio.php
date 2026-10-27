<?php
// RELATÓRIO 2 — Listagem com filtros por período e situação (usa a stored procedure sp_relatorio_clientes)
require_once "verifica_login.php";
require_once "conexao.php";

$erro = "";

// Lê os filtros da URL (GET)
$data_ini = isset($_GET['data_ini']) ? trim($_GET['data_ini']) : "";
$data_fim = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : "";
$situacao = isset($_GET['situacao']) ? trim($_GET['situacao']) : "todos";

// Valida os filtros
if (!in_array($situacao, ['todos', 'devendo', 'inadimplente', 'em_dia'])) {
    $situacao = "todos";
}
if ($data_ini !== "" && !data_valida($data_ini)) { $erro = "Data inicial inválida."; }
if ($data_fim !== "" && !data_valida($data_fim)) { $erro = "Data final inválida."; }
if ($erro === "" && $data_ini !== "" && $data_fim !== "" && $data_ini > $data_fim) {
    $erro = "A data inicial não pode ser maior que a data final.";
}

$linhas = [];

if ($erro === "") {
    // Datas vazias viram NULL (sem filtro de período)
    $p_ini = ($data_ini !== "") ? $data_ini : null;
    $p_fim = ($data_fim !== "") ? $data_fim : null;

    $stmt = $conn->prepare("CALL sp_relatorio_clientes(?, ?, ?)");
    $stmt->bind_param("sss", $p_ini, $p_fim, $situacao);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $linhas[] = $row;
    }
    $result->free();
    $stmt->close();
}

// Paginação (10 por página) feita sobre o resultado da procedure
$por_pagina    = 10;
$pagina        = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$total         = count($linhas);
$total_paginas = max(1, (int)ceil($total / $por_pagina));
if ($pagina < 1) $pagina = 1;
if ($pagina > $total_paginas) $pagina = $total_paginas;
$pagina_atual = array_slice($linhas, ($pagina - 1) * $por_pagina, $por_pagina);

// Monta o link da paginação mantendo os filtros
function link_pagina($n, $data_ini, $data_fim, $situacao) {
    return "?" . http_build_query(['data_ini' => $data_ini, 'data_fim' => $data_fim, 'situacao' => $situacao, 'pagina' => $n]);
}

$rotulos = ['em_dia' => '✔ Em dia', 'devendo' => '⏳ Devendo', 'inadimplente' => '⚠ Inadimplente'];
$classes = ['em_dia' => 'ok', 'devendo' => 'aviso', 'inadimplente' => 'alerta'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório de Dívidas — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Relatório de Dívidas</h1>

    <!-- Filtros -->
    <form method="GET" action="relatorio.php" class="form-filtros">
        <label>De
            <input type="date" name="data_ini" value="<?php echo htmlspecialchars($data_ini); ?>">
        </label>
        <label>Até
            <input type="date" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>">
        </label>
        <label>Situação
            <select name="situacao">
                <option value="todos"        <?php if ($situacao === 'todos')        echo 'selected'; ?>>Todas</option>
                <option value="devendo"      <?php if ($situacao === 'devendo')      echo 'selected'; ?>>Devendo</option>
                <option value="inadimplente" <?php if ($situacao === 'inadimplente') echo 'selected'; ?>>Inadimplente (+30 dias)</option>
                <option value="em_dia"       <?php if ($situacao === 'em_dia')       echo 'selected'; ?>>Em dia</option>
            </select>
        </label>
        <button type="submit">Filtrar</button>
    </form>

    <?php if ($erro): ?>
        <p class="erro"><?php echo htmlspecialchars($erro); ?></p>
    <?php else: ?>
        <p class="boas-vindas"><?php echo $total; ?> cliente(s) encontrado(s).
            <?php if ($data_ini !== "" || $data_fim !== ""): ?>
                (Compras e pagamentos somados apenas no período escolhido)
            <?php endif; ?>
        </p>

        <table>
            <tr>
                <th>Cliente</th>
                <th>Compras no período</th>
                <th>Pagamentos no período</th>
                <th>Saldo devedor</th>
                <th>Dias</th>
                <th>Situação</th>
            </tr>
            <?php foreach ($pagina_atual as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['nome']); ?></td>
                <td>R$ <?php echo number_format($row['compras_periodo'], 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($row['pagamentos_periodo'], 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($row['saldo_devedor'], 2, ',', '.'); ?></td>
                <td><?php echo (int)$row['dias_devendo']; ?></td>
                <td><span class="<?php echo $classes[$row['situacao']]; ?>"><?php echo $rotulos[$row['situacao']]; ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($total === 0): ?>
            <tr><td colspan="6" style="text-align:center">Nenhum cliente encontrado com esses filtros.</td></tr>
            <?php endif; ?>
        </table>

        <!-- Paginação -->
        <div class="paginacao">
            <?php if ($pagina > 1): ?>
                <a href="<?php echo link_pagina($pagina - 1, $data_ini, $data_fim, $situacao); ?>">« Anterior</a>
            <?php endif; ?>

            <span>Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>

            <?php if ($pagina < $total_paginas): ?>
                <a href="<?php echo link_pagina($pagina + 1, $data_ini, $data_fim, $situacao); ?>">Próxima »</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <a href="dashboard.php"><button>Voltar</button></a>
</div>

</body>
</html>
<?php $conn->close(); ?>
