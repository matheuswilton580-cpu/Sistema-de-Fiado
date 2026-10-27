<?php
// Somente administrador, e somente via POST (formulário da listagem)
require_once "verifica_admin.php";
require_once "conexao.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: listar_clientes.php");
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    // REGRA: não excluir cliente que ainda tem dívida em aberto
    $stmt_saldo = $conn->prepare("
        SELECT COALESCE((SELECT SUM(valor) FROM compras    WHERE cliente_id = ?), 0)
             - COALESCE((SELECT SUM(valor) FROM pagamentos WHERE cliente_id = ?), 0) AS saldo
    ");
    $stmt_saldo->bind_param("ii", $id, $id);
    $stmt_saldo->execute();
    $saldo = (float)$stmt_saldo->get_result()->fetch_assoc()['saldo'];
    $stmt_saldo->close();

    if ($saldo > 0) {
        definir_flash("Não é possível excluir: o cliente ainda deve R$ " . number_format($saldo, 2, ',', '.') . ".", "erro");
    } else {
        // Compras e pagamentos são excluídos pelo ON DELETE CASCADE
        $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        definir_flash("Cliente excluído com sucesso.");
    }
}

$conn->close();
header("Location: listar_clientes.php");
exit();
?>
