<?php
// RELATÓRIO 1 — Dashboard com cards de resumo (somente administrador)
require_once "verifica_admin.php";
require_once "conexao.php";

// Executa uma consulta (sem parâmetros) e devolve a primeira linha
function linha($conn, $sql) {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

// COUNT de clientes cadastrados
$total_clientes = linha($conn, "SELECT COUNT(*) AS qtd FROM clientes")['qtd'];

// SUM e AVG das compras (fiado)
$compras = linha($conn, "SELECT COUNT(*) AS qtd, COALESCE(SUM(valor), 0) AS total, COALESCE(AVG(valor), 0) AS media FROM compras");

// SUM dos pagamentos recebidos
$pagamentos = linha($conn, "SELECT COALESCE(SUM(valor), 0) AS total FROM pagamentos");

// COUNT, SUM e AVG dos clientes que estão devendo
$devedores = linha($conn, "
    SELECT COUNT(*) AS qtd,
           COALESCE(SUM(saldo), 0) AS total,
           COALESCE(AVG(saldo), 0) AS media,
           COALESCE(SUM(CASE WHEN dias > 30 THEN 1 ELSE 0 END), 0) AS inadimplentes
    FROM (
        SELECT COALESCE((SELECT SUM(co.valor) FROM compras co WHERE co.cliente_id = c.id), 0)
             - COALESCE((SELECT SUM(p.valor)  FROM pagamentos p  WHERE p.cliente_id = c.id), 0) AS saldo,
               COALESCE(DATEDIFF(NOW(), (SELECT MIN(co2.data_compra) FROM compras co2 WHERE co2.cliente_id = c.id)), 0) AS dias
        FROM clientes c
    ) t
    WHERE saldo > 0
");

function reais($v) { return "R$ " . number_format($v, 2, ',', '.'); }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resumo Financeiro — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Resumo Financeiro</h1>

    <div class="cards">
        <div class="card">
            <span class="card-titulo">Clientes cadastrados</span>
            <span class="card-valor"><?php echo (int)$total_clientes; ?></span>
        </div>
        <div class="card">
            <span class="card-titulo">Clientes devendo</span>
            <span class="card-valor"><?php echo (int)$devedores['qtd']; ?></span>
        </div>
        <div class="card card-alerta">
            <span class="card-titulo">Inadimplentes (+30 dias)</span>
            <span class="card-valor"><?php echo (int)$devedores['inadimplentes']; ?></span>
        </div>
        <div class="card card-alerta">
            <span class="card-titulo">Total a receber</span>
            <span class="card-valor"><?php echo reais($devedores['total']); ?></span>
        </div>
        <div class="card card-ok">
            <span class="card-titulo">Total já recebido</span>
            <span class="card-valor"><?php echo reais($pagamentos['total']); ?></span>
        </div>
        <div class="card">
            <span class="card-titulo">Total vendido no fiado</span>
            <span class="card-valor"><?php echo reais($compras['total']); ?></span>
        </div>
        <div class="card">
            <span class="card-titulo">Ticket médio por compra</span>
            <span class="card-valor"><?php echo reais($compras['media']); ?></span>
        </div>
        <div class="card">
            <span class="card-titulo">Dívida média por devedor</span>
            <span class="card-valor"><?php echo reais($devedores['media']); ?></span>
        </div>
    </div>

    <a href="relatorio.php"><button>Ver relatório detalhado</button></a>
    <a href="dashboard.php"><button class="btn-sair">Voltar</button></a>
</div>

</body>
</html>
<?php $conn->close(); ?>
