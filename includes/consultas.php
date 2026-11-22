<?php
// Adicione estas funções ao final de includes/consultas.php

function listar_categorias(PDO $pdo): array
{
    $st = $pdo->query('select cd_categoria, nm_categoria, ds_tipo from tb_categorias order by nm_categoria');
    return $st->fetchAll();
}

function listar_formas_pagamento(PDO $pdo): array
{
    $st = $pdo->query('select cd_forma_pagamento, nm_forma_pagamento from tb_formas_pagamento order by nm_forma_pagamento');
    return $st->fetchAll();
}

function criar_cartao(PDO $pdo, string $idUsuario, array $dados): void
{
    $sql = 'insert into tb_cartoes_credito
                (cd_usuario, nm_cartao, vl_limite, nr_dia_fechamento, nr_dia_vencimento, nr_ultimos4)
            values
                (:u, :nm, :lim, :fech, :venc, :u4)';
    $st = $pdo->prepare($sql);
    $st->execute([
        ':u'    => $idUsuario,
        ':nm'   => $dados['nm_cartao'],
        ':lim'  => $dados['vl_limite'],
        ':fech' => $dados['nr_dia_fechamento'],
        ':venc' => $dados['nr_dia_vencimento'],
        ':u4'   => $dados['nr_ultimos4'] ?: null,
    ]);
}

/**
 * Registra uma transação à vista (1x) ou parcelada (ex: até 12x).
 * Se $qtParcelas > 1, divide o valor total e avança 1 mês por parcela.
 */
function criar_transacao(PDO $pdo, string $idUsuario, array $dados): void
{
    $qtParcelas = max(1, min(60, (int) ($dados['qt_parcelas'] ?? 1)));
    $valorTotal = (float) $dados['vl_transacao'];
    $valorParcela = round($valorTotal / $qtParcelas, 2);
    $cdCartao = !empty($dados['cd_cartao']) ? (int) $dados['cd_cartao'] : null;

    $pdo->beginTransaction();
    try {
        $sql = 'insert into tb_transacoes
                    (cd_usuario, ds_transacao, vl_transacao, dt_transacao,
                     nr_parcela, qt_parcelas, cd_categoria, cd_forma_pagamento, cd_cartao)
                values
                    (:u, :ds, :vl, :dt, :nr_p, :qt_p, :cat, :fp, :cart)';
        $st = $pdo->prepare($sql);

        $dataBase = new DateTimeImmutable($dados['dt_transacao']);

        for ($i = 1; $i <= $qtParcelas; $i++) {
            // Ajusta centavos residuais na última parcela para bater o total
            $vlAtual = ($i === $qtParcelas)
                ? round($valorTotal - ($valorParcela * ($qtParcelas - 1)), 2)
                : $valorParcela;

            $dtParcela = $dataBase->modify('+' . ($i - 1) . ' months')->format('Y-m-d');

            $st->execute([
                ':u'    => $idUsuario,
                ':ds'   => $dados['ds_transacao'],
                ':vl'   => $vlAtual,
                ':dt'   => $dtParcela,
                ':nr_p' => $i,
                ':qt_p' => $qtParcelas,
                ':cat'  => (int) $dados['cd_categoria'],
                ':fp'   => (int) $dados['cd_forma_pagamento'],
                ':cart' => $cdCartao,
            ]);
        }

        // Se houver vínculo com cartão e tb_faturas aberta, atualiza ou cria conforme seu schema
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function excluir_transacao(PDO $pdo, string $idUsuario, int $cdTransacao): void
{
    $st = $pdo->prepare('delete from tb_transacoes where cd_transacao = :id and cd_usuario = :u');
    $st->execute([':id' => $cdTransacao, ':u' => $idUsuario]);
}