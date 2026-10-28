<?php
require_once "verifica_admin.php";
require_once "conexao.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = (int)$_POST['id_usuario'];
    $acao = $_POST['acao'];
    if ($acao === 'bloquear') {
        $stmt = $conn->prepare("UPDATE usuarios SET status = 'bloqueado' WHERE id = ? AND nivel = 'cliente' AND status = 'aprovado'");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();
        definir_flash("Acesso bloqueado. O cliente não consegue mais entrar.");
    } elseif ($acao === 'desbloquear') {
        $stmt = $conn->prepare("UPDATE usuarios SET status = 'aprovado' WHERE id = ? AND nivel = 'cliente' AND status = 'bloqueado'");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();
        definir_flash("Acesso desbloqueado.");
    } elseif ($acao === 'trocar_senha') {
        $nova_senha = $_POST['nova_senha'];
        if (strlen($nova_senha) < 6) {
            definir_flash("A nova senha deve ter no mínimo 6 caracteres.", "erro");
        } else {
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ? AND nivel = 'cliente'");
            $stmt->bind_param("si", $hash, $id_usuario);
            $stmt->execute();
            $stmt->close();
            definir_flash("Senha alterada com sucesso.");
        }
    }
    $conn->close();
    header("Location: acessos_clientes.php");
    exit();
}
$resultado = $conn->query("SELECT u.id, u.nome, u.usuario, u.status, c.nome AS nome_cliente FROM usuarios u LEFT JOIN clientes c ON c.id = u.cliente_id WHERE u.nivel = 'cliente' AND u.status IN ('aprovado','bloqueado') ORDER BY u.nome");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Acessos de Clientes — Sistema Fiado</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<div class="container">
    <h1>Acessos de Clientes</h1>
    <?php mostrar_flash(); ?>
    <table>
        <tr><th>Usuário</th><th>Cliente vinculado</th><th>Situação</th><th>Ações</th></tr>
        <?php $total_acessos = 0; while ($row = $resultado->fetch_assoc()): $total_acessos++; ?>
        <tr>
            <td><?php echo htmlspecialchars($row['usuario']); ?></td>
            <td><?php echo htmlspecialchars($row['nome_cliente'] ?? ''); ?></td>
            <td><?php if ($row['status'] === 'bloqueado'): ?><span class="alerta">Bloqueado</span><?php else: ?><span class="ok">Ativo</span><?php endif; ?></td>
            <td>
                <form method="POST" action="acessos_clientes.php">
                    <input type="hidden" name="id_usuario" value="<?php echo $row['id']; ?>">
                    <input type="password" name="nova_senha" placeholder="Nova senha (mín. 6)">
                    <button type="submit" name="acao" value="trocar_senha" class="btn-editar">Trocar senha</button>
                    <?php if ($row['status'] === 'bloqueado'): ?>
                        <button type="submit" name="acao" value="desbloquear" class="btn-editar">Desbloquear</button>
                    <?php else: ?>
                        <button type="submit" name="acao" value="bloquear" class="btn-excluir" onclick="return confirm('Bloquear o acesso deste cliente?')">Bloquear</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total_acessos === 0): ?>
        <tr><td colspan="4" style="text-align:center">Nenhum cliente com acesso ainda.</td></tr>
        <?php endif; ?>
    </table>
    <a href="dashboard.php"><button>Voltar</button></a>
</div>
</body>
</html>
<?php $conn->close(); ?>
