<?php
require_once "verifica_admin.php";
require_once "conexao.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = (int)$_POST['id_usuario'];
    $acao = $_POST['acao'];
    if ($acao === 'aprovar') {
        $id_cliente = (int)$_POST['id_cliente'];
        $stmt_cliente = $conn->prepare("SELECT id FROM clientes WHERE id = ?");
        $stmt_cliente->bind_param("i", $id_cliente);
        $stmt_cliente->execute();
        $stmt_cliente->store_result();
        $cliente_existe = $stmt_cliente->num_rows > 0;
        $stmt_cliente->close();
        if (!$cliente_existe) {
            definir_flash("Escolha a qual cliente esse acesso pertence antes de aprovar.", "erro");
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET status = 'aprovado', cliente_id = ? WHERE id = ? AND nivel = 'cliente' AND status = 'pendente'");
            $stmt->bind_param("ii", $id_cliente, $id_usuario);
            $stmt->execute();
            $stmt->close();
            definir_flash("Acesso aprovado com sucesso.");
        }
    } elseif ($acao === 'recusar') {
        $stmt = $conn->prepare("UPDATE usuarios SET status = 'recusado' WHERE id = ? AND nivel = 'cliente' AND status = 'pendente'");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();
        definir_flash("Solicitação recusada.");
    }
    $conn->close();
    header("Location: aprovar_acessos.php");
    exit();
}
$lista_clientes = [];
$resultado_clientes = $conn->query("SELECT id, nome, telefone FROM clientes ORDER BY nome");
while ($linha_cliente = $resultado_clientes->fetch_assoc()) {
    $lista_clientes[] = $linha_cliente;
}
$resultado = $conn->query("SELECT id, nome, usuario, telefone_solicitado, criado_em FROM usuarios WHERE nivel = 'cliente' AND status = 'pendente' ORDER BY criado_em");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Aprovar Acessos — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<div class="container">
    <h1>Solicitações de Acesso de Clientes</h1>
    <?php mostrar_flash(); ?>
    <table>
        <tr><th>Nome</th><th>Usuário</th><th>Telefone informado</th><th>Solicitado em</th><th>Cliente / Ação</th></tr>
        <?php $total_pendentes = 0; while ($row = $resultado->fetch_assoc()): $total_pendentes++; ?>
        <?php $telefone_solicitado_numeros = preg_replace('/\D/', '', $row['telefone_solicitado']); ?>
        <tr>
            <td><?php echo htmlspecialchars($row['nome']); ?></td>
            <td><?php echo htmlspecialchars($row['usuario']); ?></td>
            <td><?php echo htmlspecialchars($row['telefone_solicitado']); ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($row['criado_em'])); ?></td>
            <td>
                <form method="POST" action="aprovar_acessos.php">
                    <input type="hidden" name="id_usuario" value="<?php echo $row['id']; ?>">
                    <select name="id_cliente">
                        <option value="">Escolha o cliente...</option>
                        <?php foreach ($lista_clientes as $cliente): ?>
                            <?php $telefone_cliente_numeros = preg_replace('/\D/', '', $cliente['telefone'] ?? ''); ?>
                            <option value="<?php echo $cliente['id']; ?>" <?php if ($telefone_cliente_numeros !== '' && $telefone_cliente_numeros === $telefone_solicitado_numeros) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cliente['nome'] . ' — ' . ($cliente['telefone'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="acao" value="aprovar" class="btn-editar">Aprovar</button>
                    <button type="submit" name="acao" value="recusar" class="btn-excluir" onclick="return confirm('Recusar esta solicitação?')">Recusar</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total_pendentes === 0): ?>
        <tr><td colspan="5" style="text-align:center">Nenhuma solicitação pendente.</td></tr>
        <?php endif; ?>
    </table>
    <a href="dashboard.php"><button>Voltar</button></a>
</div>
</body>
</html>
<?php $conn->close(); ?>
