<?php
/** Consultas ao banco usadas pelas páginas. Todas filtram pelo usuário logado. */

/** Receita/entrada soma; qualquer outro tipo é despesa. Ajuste conforme os valores de tb_categorias.ds_tipo. */
function eh_receita(?string $tipo): bool
{
    return in_array(mb_strtolower(trim((string) $tipo)), ['receita', 'entrada'], true);
}

function listar_transacoes(PDO $pdo, string $idUsuario, ?int $limite = null): array
{
    $sql = 'select t.cd_transacao, t.ds_transacao, t.vl_transacao, t.dt_transacao,
                   t.nr_parcela, t.qt_parcelas,
                   c.nm_categoria, c.ds_tipo,
                   f.nm_forma_pagamento,
                   k.nm_cartao
            from tb_transacoes t
            join tb_categorias c on c.cd_categoria = t.cd_categoria
            join tb_formas_pagamento f on f.cd_forma_pagamento = t.cd_forma_pagamento
            left join tb_cartoes_credito k on k.cd_cartao = t.cd_cartao
            where t.cd_usuario = :u
            order by t.dt_transacao desc, t.cd_transacao desc';
    if ($limite !== null) {
        $sql .= ' limit ' . (int) $limite;
    }
    $st = $pdo->prepare($sql);
    $st->execute([':u' => $idUsuario]);
    return $st->fetchAll();
}

function listar_cartoes(PDO $pdo, string $idUsuario): array
{
    $st = $pdo->prepare(
        "select k.cd_cartao, k.nm_cartao, k.vl_limite, k.nr_dia_fechamento, k.nr_dia_vencimento, k.nr_ultimos4,
                coalesce((select sum(fa.vl_total) from tb_faturas fa
                          where fa.cd_cartao = k.cd_cartao and fa.ds_status = 'Aberta'), 0) as vl_fatura_aberta
         from tb_cartoes_credito k
         where k.cd_usuario = :u
         order by k.nm_cartao"
    );
    $st->execute([':u' => $idUsuario]);
    return $st->fetchAll();
}

/** HTML de uma linha de transação (usado na home e no histórico). */
function linha_transacao(array $t): string
{
    $receita = eh_receita($t['ds_tipo']);
    $cor = $receita ? 'text-success' : 'text-danger';
    $sinal = $receita ? '+' : '-';
    $origem = $t['nm_cartao'] ? $t['nm_cartao'] . ' - ' : '';
    $parcela = (int) $t['qt_parcelas'] > 1 ? " • {$t['nr_parcela']}/{$t['qt_parcelas']}" : '';
    $data = date('d/m/Y', strtotime($t['dt_transacao']));

    return '<div class="bg-white p-2 px-3 rounded-pill d-flex align-items-center justify-content-between shadow-sm">'
        . '<div><div class="small fw-bold text-dark" style="font-size: 13px;">' . e($origem . $t['ds_transacao']) . '</div>'
        . '<div class="text-muted" style="font-size: 11px;">' . e($data . ' • ' . $t['nm_forma_pagamento'] . ' • ' . $t['nm_categoria'] . $parcela) . '</div></div>'
        . '<span class="' . $cor . ' fw-bold small ms-3 text-nowrap">' . $sinal . ' ' . e(moeda((float) $t['vl_transacao'])) . '</span>'
        . '</div>';
}
