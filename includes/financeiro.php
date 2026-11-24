<?php
/**
 * Regras de negócio: compras parceladas, faturas de cartão.
 * Todas as funções recebem o id do usuário e só mexem em dados dele.
 * Erros de regra de negócio lançam RuntimeException com mensagem para o usuário.
 */
require_once __DIR__ . '/util.php';

function eh_credito(?string $nomeForma): bool
{
    $n = mb_strtolower(trim((string) $nomeForma));
    return str_contains($n, 'crédito') || str_contains($n, 'credito');
}

function eh_receita(?string $tipo): bool
{
    return in_array(mb_strtolower(trim((string) $tipo)), ['receita', 'entrada'], true);
}

function em_transacao(PDO $pdo, callable $fn): mixed
{
    $pdo->beginTransaction();
    try {
        $r = $fn();
        $pdo->commit();
        return $r;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Divide total em n parcelas (centavos); a sobra vai para as primeiras parcelas. */
function dividir_parcelas(int $totalCentavos, int $n): array
{
    $base = intdiv($totalCentavos, $n);
    $sobra = $totalCentavos - $base * $n;
    $partes = [];
    for ($i = 0; $i < $n; $i++) {
        $partes[] = $base + ($i < $sobra ? 1 : 0);
    }
    return $partes;
}

/** Datas de fechamento e vencimento da fatura em que uma compra cai. */
function datas_fatura(array $cartao, string $dataCompra): array
{
    $fecha = (int) $cartao['nr_dia_fechamento'];
    $vence = (int) $cartao['nr_dia_vencimento'];
    $d = new DateTimeImmutable($dataCompra);
    $ref = $d->modify('first day of this month');
    if ((int) $d->format('j') > $fecha) {          // comprou depois do fechamento: próxima fatura
        $ref = $ref->modify('+1 month');
    }
    $dia = fn(DateTimeImmutable $m, int $x) => $m->setDate((int) $m->format('Y'), (int) $m->format('n'), min($x, (int) $m->format('t')));
    $fechamento = $dia($ref, $fecha);
    $refVenc = $vence > $fecha ? $ref : $ref->modify('+1 month');
    $vencimento = $dia($refVenc, $vence);
    return [$fechamento->format('Y-m-d'), $vencimento->format('Y-m-d')];
}

function buscar_cartao(PDO $pdo, string $uid, int $cdCartao): ?array
{
    $st = $pdo->prepare('select * from tb_cartoes_credito where cd_cartao = :c and cd_usuario = :u');
    $st->execute([':c' => $cdCartao, ':u' => $uid]);
    return $st->fetch() ?: null;
}

/** Devolve [cd_fatura, status], criando a fatura se ainda não existir. */
function obter_ou_criar_fatura(PDO $pdo, array $cartao, string $dataCompra): array
{
    [$fech, $venc] = datas_fatura($cartao, $dataCompra);
    $st = $pdo->prepare('select cd_fatura, ds_status from tb_faturas where cd_cartao = :c and dt_fechamento = :f');
    $st->execute([':c' => $cartao['cd_cartao'], ':f' => $fech]);
    if ($f = $st->fetch()) {
        return [(int) $f['cd_fatura'], $f['ds_status']];
    }
    $st = $pdo->prepare(
        "insert into tb_faturas (cd_cartao, dt_fechamento, dt_vencimento, vl_total, ds_status)
         values (:c, :f, :v, 0, 'Aberta') returning cd_fatura"
    );
    $st->execute([':c' => $cartao['cd_cartao'], ':f' => $fech, ':v' => $venc]);
    return [(int) $st->fetchColumn(), 'Aberta'];
}

/** Recalcula o total da fatura; remove a fatura se ficou sem lançamentos. */
function recalcular_fatura(PDO $pdo, int $cdFatura): void
{
    $st = $pdo->prepare('select count(*) from tb_transacoes where cd_fatura = :f');
    $st->execute([':f' => $cdFatura]);
    if ((int) $st->fetchColumn() === 0) {
        $pdo->prepare('delete from tb_faturas where cd_fatura = :f')->execute([':f' => $cdFatura]);
        return;
    }
    $pdo->prepare(
        'update tb_faturas set vl_total = (select coalesce(sum(vl_transacao), 0) from tb_transacoes where cd_fatura = :f1)
         where cd_fatura = :f2'
    )->execute([':f1' => $cdFatura, ':f2' => $cdFatura]);
}

/**
 * Cria uma compra/receita (e todas as parcelas). Use dentro de em_transacao().
 * $d: cd_categoria, cd_forma_pagamento, cd_cartao (?int), ds_transacao, total (centavos), parcelas, data (Y-m-d)
 */
function criar_compra(PDO $pdo, string $uid, array $d): void
{
    $st = $pdo->prepare('select nm_forma_pagamento from tb_formas_pagamento where cd_forma_pagamento = :f');
    $st->execute([':f' => $d['cd_forma_pagamento']]);
    $forma = $st->fetchColumn();
    if ($forma === false) {
        throw new RuntimeException('Forma de pagamento inválida.');
    }
    $st = $pdo->prepare('select ds_tipo from tb_categorias where cd_categoria = :c');
    $st->execute([':c' => $d['cd_categoria']]);
    $tipo = $st->fetchColumn();
    if ($tipo === false) {
        throw new RuntimeException('Categoria inválida.');
    }

    $credito = eh_credito($forma);
    $parcelas = $credito ? (int) $d['parcelas'] : 1;
    if ($parcelas < 1 || $parcelas > 48) {
        throw new RuntimeException('O número de parcelas deve ficar entre 1 e 48.');
    }
    if ($d['total'] <= 0) {
        throw new RuntimeException('O valor deve ser maior que zero.');
    }

    $cartao = null;
    if ($credito) {
        if (eh_receita($tipo)) {
            throw new RuntimeException('Receita não pode ser lançada no crédito.');
        }
        $cartao = $d['cd_cartao'] ? buscar_cartao($pdo, $uid, (int) $d['cd_cartao']) : null;
        if (!$cartao) {
            throw new RuntimeException('Escolha um cartão de crédito para compras no crédito.');
        }
    }

    $compra = uuid_v4();
    $valores = dividir_parcelas((int) $d['total'], $parcelas);
    $faturas = [];
    $ins = $pdo->prepare(
        'insert into tb_transacoes (cd_usuario, cd_categoria, cd_forma_pagamento, cd_cartao, cd_fatura,
                                    ds_transacao, vl_transacao, dt_transacao, nr_parcela, qt_parcelas, cd_compra)
         values (:u, :cat, :forma, :cartao, :fatura, :ds, :vl, :dt, :np, :qp, :compra)'
    );

    foreach ($valores as $i => $centavos) {
        $dataParcela = somar_meses($d['data'], $i);
        $cdFatura = null;
        if ($cartao) {
            [$cdFatura, $status] = obter_ou_criar_fatura($pdo, $cartao, $dataParcela);
            if (mb_strtolower($status) === 'paga') {
                throw new RuntimeException('A fatura de ' . data_br($dataParcela) . ' já está paga. Reabra a fatura antes de lançar.');
            }
            $faturas[$cdFatura] = true;
        }
        $ins->execute([
            ':u' => $uid, ':cat' => $d['cd_categoria'], ':forma' => $d['cd_forma_pagamento'],
            ':cartao' => $cartao ? $cartao['cd_cartao'] : null, ':fatura' => $cdFatura,
            ':ds' => $d['ds_transacao'], ':vl' => centavos_para_decimal($centavos), ':dt' => $dataParcela,
            ':np' => $i + 1, ':qp' => $parcelas, ':compra' => $compra,
        ]);
    }
    foreach (array_keys($faturas) as $f) {
        recalcular_fatura($pdo, $f);
    }
}

/** Todas as parcelas da compra a que a transação pertence (ordenadas). Vazio se não for do usuário. */
function carregar_compra(PDO $pdo, string $uid, int $cdTransacao): array
{
    $st = $pdo->prepare('select * from tb_transacoes where cd_transacao = :t and cd_usuario = :u');
    $st->execute([':t' => $cdTransacao, ':u' => $uid]);
    $t = $st->fetch();
    if (!$t) {
        return [];
    }
    if (empty($t['cd_compra'])) {
        return [$t];
    }
    $st = $pdo->prepare('select * from tb_transacoes where cd_compra = :c and cd_usuario = :u order by nr_parcela');
    $st->execute([':c' => $t['cd_compra'], ':u' => $uid]);
    return $st->fetchAll();
}

/** Exclui a compra inteira. Use dentro de em_transacao(). Retorna false se não encontrada. */
function excluir_compra(PDO $pdo, string $uid, int $cdTransacao): bool
{
    $linhas = carregar_compra($pdo, $uid, $cdTransacao);
    if (!$linhas) {
        return false;
    }
    $faturas = array_values(array_unique(array_filter(array_map(fn($l) => $l['cd_fatura'], $linhas))));
    if ($faturas) {
        $marcas = implode(',', array_fill(0, count($faturas), '?'));
        $st = $pdo->prepare("select count(*) from tb_faturas where cd_fatura in ($marcas) and lower(ds_status) = 'paga'");
        $st->execute($faturas);
        if ((int) $st->fetchColumn() > 0) {
            throw new RuntimeException('Esta compra tem parcelas em fatura já paga. Reabra a fatura antes de alterar.');
        }
    }
    $ids = array_column($linhas, 'cd_transacao');
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("delete from tb_transacoes where cd_transacao in ($marcas) and cd_usuario = ?");
    $st->execute([...$ids, $uid]);
    foreach ($faturas as $f) {
        recalcular_fatura($pdo, (int) $f);
    }
    return true;
}

/** Edita = apaga as parcelas antigas e recria com os novos dados, de forma atômica. */
function atualizar_compra(PDO $pdo, string $uid, int $cdTransacao, array $d): void
{
    em_transacao($pdo, function () use ($pdo, $uid, $cdTransacao, $d) {
        if (!excluir_compra($pdo, $uid, $cdTransacao)) {
            throw new RuntimeException('Lançamento não encontrado.');
        }
        criar_compra($pdo, $uid, $d);
    });
}

/** Exclui o cartão, se não houver lançamentos nele. */
function excluir_cartao(PDO $pdo, string $uid, int $cdCartao): void
{
    if (!buscar_cartao($pdo, $uid, $cdCartao)) {
        throw new RuntimeException('Cartão não encontrado.');
    }
    $st = $pdo->prepare('select count(*) from tb_transacoes where cd_cartao = :c');
    $st->execute([':c' => $cdCartao]);
    if ((int) $st->fetchColumn() > 0) {
        throw new RuntimeException('Este cartão tem lançamentos. Exclua os lançamentos dele antes de remover o cartão.');
    }
    em_transacao($pdo, function () use ($pdo, $cdCartao) {
        $pdo->prepare('delete from tb_faturas where cd_cartao = :c')->execute([':c' => $cdCartao]);
        $pdo->prepare('delete from tb_cartoes_credito where cd_cartao = :c')->execute([':c' => $cdCartao]);
    });
}

function alterar_status_fatura(PDO $pdo, string $uid, int $cdFatura, bool $paga): void
{
    $st = $pdo->prepare(
        'update tb_faturas set ds_status = :s
         where cd_fatura = :f and cd_cartao in (select cd_cartao from tb_cartoes_credito where cd_usuario = :u)'
    );
    $st->execute([':s' => $paga ? 'Paga' : 'Aberta', ':f' => $cdFatura, ':u' => $uid]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Fatura não encontrada.');
    }
}
