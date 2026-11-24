<?php
/** Consultas de leitura usadas pelas páginas. Todas filtram pelo usuário logado. */
require_once __DIR__ . '/financeiro.php';

const SQL_TRANSACOES = 'select t.cd_transacao, t.ds_transacao, t.vl_transacao, t.dt_transacao,
                   t.nr_parcela, t.qt_parcelas, t.cd_compra,
                   c.nm_categoria, c.ds_tipo,
                   f.nm_forma_pagamento,
                   k.nm_cartao
            from tb_transacoes t
            join tb_categorias c on c.cd_categoria = t.cd_categoria
            join tb_formas_pagamento f on f.cd_forma_pagamento = t.cd_forma_pagamento
            left join tb_cartoes_credito k on k.cd_cartao = t.cd_cartao';

function listar_transacoes_mes(PDO $pdo, string $uid, string $mes): array
{
    $st = $pdo->prepare(
        SQL_TRANSACOES . ' where t.cd_usuario = :u and t.dt_transacao >= :ini and t.dt_transacao < :fim
         order by t.dt_transacao desc, t.cd_transacao desc'
    );
    $st->execute([':u' => $uid, ':ini' => $mes . '-01', ':fim' => mes_deslocado($mes, 1) . '-01']);
    return $st->fetchAll();
}

function listar_ultimas(PDO $pdo, string $uid, int $limite): array
{
    $st = $pdo->prepare(
        SQL_TRANSACOES . ' where t.cd_usuario = :u order by t.dt_transacao desc, t.cd_transacao desc limit ' . (int) $limite
    );
    $st->execute([':u' => $uid]);
    return $st->fetchAll();
}

function totais(array $transacoes): array
{
    $r = $d = 0.0;
    foreach ($transacoes as $t) {
        if (eh_receita($t['ds_tipo'])) {
            $r += (float) $t['vl_transacao'];
        } else {
            $d += (float) $t['vl_transacao'];
        }
    }
    return ['receitas' => $r, 'despesas' => $d, 'saldo' => $r - $d];
}

function listar_cartoes(PDO $pdo, string $uid): array
{
    $st = $pdo->prepare(
        "select k.cd_cartao, k.nm_cartao, k.vl_limite, k.nr_dia_fechamento, k.nr_dia_vencimento, k.nr_ultimos4,
                coalesce((select sum(fa.vl_total) from tb_faturas fa
                          where fa.cd_cartao = k.cd_cartao and lower(fa.ds_status) <> 'paga'), 0) as vl_comprometido
         from tb_cartoes_credito k
         where k.cd_usuario = :u
         order by k.nm_cartao"
    );
    $st->execute([':u' => $uid]);
    return $st->fetchAll();
}

function listar_categorias(PDO $pdo): array
{
    return $pdo->query('select cd_categoria, nm_categoria, ds_tipo from tb_categorias order by ds_tipo, nm_categoria')->fetchAll();
}

function listar_formas(PDO $pdo): array
{
    return $pdo->query('select cd_forma_pagamento, nm_forma_pagamento from tb_formas_pagamento order by cd_forma_pagamento')->fetchAll();
}

function listar_faturas(PDO $pdo, int $cdCartao): array
{
    $st = $pdo->prepare('select * from tb_faturas where cd_cartao = :c order by dt_fechamento desc');
    $st->execute([':c' => $cdCartao]);
    return $st->fetchAll();
}

function transacoes_da_fatura(PDO $pdo, int $cdFatura): array
{
    $st = $pdo->prepare(
        SQL_TRANSACOES . ' where t.cd_fatura = :f order by t.dt_transacao, t.cd_transacao'
    );
    $st->execute([':f' => $cdFatura]);
    return $st->fetchAll();
}

/** HTML de uma linha de transação. $acoes = true mostra editar/excluir (precisa de $mes para voltar à tela). */
function linha_transacao(array $t, bool $acoes = false, string $mes = ''): string
{
    $receita = eh_receita($t['ds_tipo']);
    $cor = $receita ? 'text-success' : 'text-danger';
    $sinal = $receita ? '+' : '-';
    $origem = $t['nm_cartao'] ? $t['nm_cartao'] . ' - ' : '';
    $parcela = (int) $t['qt_parcelas'] > 1 ? " • {$t['nr_parcela']}/{$t['qt_parcelas']}" : '';
    $data = data_br($t['dt_transacao']);

    $html = '<div class="bg-body p-2 px-3 rounded-4 d-flex align-items-center justify-content-between shadow-sm gap-2">'
        . '<div class="min-w-0"><div class="small fw-bold" style="font-size: 13px;">' . e($origem . $t['ds_transacao']) . '</div>'
        . '<div class="text-body-secondary" style="font-size: 11px;">' . e($data . ' • ' . $t['nm_forma_pagamento'] . ' • ' . $t['nm_categoria'] . $parcela) . '</div></div>'
        . '<div class="d-flex align-items-center gap-2 flex-shrink-0">'
        . '<span class="' . $cor . ' fw-bold small text-nowrap">' . $sinal . ' ' . e(moeda((float) $t['vl_transacao'])) . '</span>';

    if ($acoes) {
        $id = (int) $t['cd_transacao'];
        $aviso = (int) $t['qt_parcelas'] > 1 ? 'Excluir esta compra e TODAS as ' . (int) $t['qt_parcelas'] . ' parcelas?' : 'Excluir este lançamento?';
        $html .= '<a class="btn btn-sm btn-outline-secondary py-0 px-2" href="transacao_form.php?id=' . $id . '" title="Editar"><i class="bi bi-pencil"></i></a>'
            . '<form method="post" action="historico.php" class="m-0" onsubmit="return confirm(' . e(json_encode($aviso)) . ')">'
            . csrf_campo()
            . '<input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="' . $id . '">'
            . '<input type="hidden" name="mes" value="' . e($mes) . '">'
            . '<button class="btn btn-sm btn-outline-danger py-0 px-2" title="Excluir"><i class="bi bi-trash"></i></button></form>';
    }
    return $html . '</div></div>';
}
