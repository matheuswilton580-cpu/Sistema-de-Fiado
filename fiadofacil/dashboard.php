<?php
require_once "verifica_login.php";
require_once "conexao.php";
$total_pendentes = 0;
if (eh_admin()) {
    $total_pendentes = $conn->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'cliente' AND status = 'pendente'")->fetch_row()[0];
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Menu — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<div class="container">
    <h1>Menu Principal</h1>
    <p class="boas-vindas">
        Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!
        (Perfil: <strong><?php echo eh_admin() ? "Administrador" : "Usuário comum"; ?></strong>)
    </p>

    <?php mostrar_flash(); ?>

    <a href="listar_clientes.php"><button>Listar Clientes</button></a>
    <a href="cadastro_cliente.php"><button>Cadastrar Cliente</button></a>
    <a href="registrar_compra.php"><button>Registrar Compra (Fiado)</button></a>
    <a href="registrar_pagamento.php"><button>Registrar Pagamento</button></a>
    <a href="relatorio.php"><button>Relatório de Dívidas (com filtros)</button></a>
    <a href="ranking.php"><button>Ranking de Clientes</button></a>

    <?php if (eh_admin()): ?>
        <!-- Opções exclusivas do administrador -->
        <a href="resumo.php"><button>Resumo Financeiro (Dashboard)</button></a>
        <a href="usuarios.php"><button>Gerenciar Usuários</button></a>
        <a href="acessos_clientes.php"><button>Gerenciar Acessos de Clientes (bloquear / trocar senha)</button></a>
        <a href="aprovar_acessos.php"><button>Aprovar Acessos de Clientes<?php if ($total_pendentes > 0) echo " ($total_pendentes pendente(s))"; ?></button></a>
    <?php endif; ?>

    <a href="logout.php" onclick="return confirm('Deseja sair do sistema?')">
        <button class="btn-sair">Sair</button>
    </a>
</div>

</body>
</html>
