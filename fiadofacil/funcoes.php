<?php
/**
 * Substitui as stored procedures (que a InfinityFree não permite).
 *
 * Uso: require_once 'funcoes_fiado.php';
 * Ambas as funções recebem a conexão PDO que você já usa no projeto.
 * A conexão deve ter: PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
 */

/**
 * Antiga sp_relatorio_clientes.
 *
 * Exemplo de chamada (antes: CALL sp_relatorio_clientes(?, ?, ?)):
 *   $lista = relatorioClientes($pdo, '2026-10-01', '2026-10-31', 'todos');
 *
 * @param string|null $dataIni  'AAAA-MM-DD' ou null (sem filtro)
 * @param string|null $dataFim  'AAAA-MM-DD' ou null (sem filtro)
 * @param string      $status   'todos' | 'em_dia' | 'devendo' | 'inadimplente'
 */
function relatorioClientes(PDO $pdo, ?string $dataIni = null, ?string $dataFim = null, string $status = 'todos'): array
{
    $ini = $dataIni ?: '1000-01-01';
    $fim = $dataFim ?: '9999-12-31';
    $filtraPeriodo = ($dataIni !== null && $dataIni !== '') || ($dataFim !== null && $dataFim !== '');

    $sql = "
        SELECT r.*
        FROM (
            SELECT t.*,
                   CASE
                       WHEN t.saldo_devedor <= 0 THEN 'em_dia'
                       WHEN t.dias_devendo > 30  THEN 'inadimplente'
                       ELSE 'devendo'
                   END AS situacao
            FROM (
                SELECT c.id, c.nome, c.telefone, c.limite_credito,
                       COALESCE((SELECT SUM(co.valor) FROM compras co
                                 WHERE co.cliente_id = c.id
                                   AND DATE(co.data_compra) BETWEEN :ini1 AND :fim1), 0) AS compras_periodo,
                       COALESCE((SELECT SUM(p.valor) FROM pagamentos p
                                 WHERE p.cliente_id = c.id
                                   AND DATE(p.data_pagamento) BETWEEN :ini2 AND :fim2), 0) AS pagamentos_periodo,
                       COALESCE((SELECT SUM(co2.valor) FROM compras co2 WHERE co2.cliente_id = c.id), 0)
                       - COALESCE((SELECT SUM(p2.valor) FROM pagamentos p2 WHERE p2.cliente_id = c.id), 0)
                       AS saldo_devedor,
                       COALESCE(DATEDIFF(NOW(),
                           (SELECT MIN(co3.data_compra) FROM compras co3 WHERE co3.cliente_id = c.id)), 0)
                       AS dias_devendo
                FROM clientes c
            ) t
        ) r
        WHERE (:status1 = 'todos' OR r.situacao = :status2)
          AND (:filtra = 0 OR r.compras_periodo > 0 OR r.pagamentos_periodo > 0)
        ORDER BY r.saldo_devedor DESC, r.nome
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ini1'    => $ini,
        ':fim1'    => $fim,
        ':ini2'    => $ini,
        ':fim2'    => $fim,
        ':status1' => $status,
        ':status2' => $status,
        ':filtra'  => $filtraPeriodo ? 1 : 0,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Antiga sp_registrar_compra.
 *
 * Exemplo de chamada (antes: CALL sp_registrar_compra(?, ?, ?, @ok, @msg)):
 *   [$ok, $msg] = registrarCompra($pdo, 1, 'Arroz', 25.50);
 *
 * Retorna [bool $ok, string $mensagem].
 */
function registrarCompra(PDO $pdo, int $clienteId, string $descricao, float $valor): array
{
    if ($valor <= 0) {
        return [false, 'O valor da compra deve ser maior que zero.'];
    }

    try {
        $pdo->beginTransaction();

        // Trava a linha do cliente para duas compras simultâneas não estourarem o limite
        $stmt = $pdo->prepare('SELECT limite_credito FROM clientes WHERE id = ? FOR UPDATE');
        $stmt->execute([$clienteId]);
        $limite = $stmt->fetchColumn();

        if ($limite === false || $limite === null) {
            $pdo->rollBack();
            return [false, 'Cliente não encontrado.'];
        }
        $limite = (float) $limite;

        $stmt = $pdo->prepare('
            SELECT COALESCE((SELECT SUM(valor) FROM compras    WHERE cliente_id = ?), 0)
                 - COALESCE((SELECT SUM(valor) FROM pagamentos WHERE cliente_id = ?), 0)
        ');
        $stmt->execute([$clienteId, $clienteId]);
        $saldo = (float) $stmt->fetchColumn();

        if ($saldo + $valor > $limite) {
            $pdo->rollBack();
            return [false, 'Limite de crédito excedido. Limite: R$ ' . number_format($limite, 2, ',', '.')
                         . ' | Dívida atual: R$ ' . number_format($saldo, 2, ',', '.')];
        }

        $stmt = $pdo->prepare('INSERT INTO compras (cliente_id, descricao, valor) VALUES (?, ?, ?)');
        $stmt->execute([$clienteId, $descricao, $valor]);

        $pdo->commit();
        return [true, 'Compra registrada com sucesso!'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [false, 'Erro interno ao registrar a compra. Nada foi gravado.'];
    }
}