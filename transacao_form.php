<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$uid = usuario_atual()['id'];
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$editando = $id > 0;
$dados = [
    'ds_transacao' => '', 'valor' => '', 'data' => date('Y-m-d'),
    'cd_categoria' => '', 'cd_forma_pagamento' => '', 'cd_cartao' => '', 'parcelas' => 1,
];

if ($editando) {
    $compra = carregar_compra($pdo, $uid, $id);
    if (!$compra) {
        flash('Lançamento não encontrado.', 'danger');
        redirecionar('historico.php');
    }
    $primeira = $compra[0];
    $total = 0;
    foreach ($compra as $p) {
        $total += (int) round((float) $p['vl_transacao'] * 100);
    }
    $dados = [
        'ds_transacao' => $primeira['ds_transacao'],
        'valor' => centavos_para_input($total),
        'data' => $primeira['dt_transacao'],
        'cd_categoria' => $primeira['cd_categoria'],
        'cd_forma_pagamento' => $primeira['cd_forma_pagamento'],
        'cd_cartao' => $primeira['cd_cartao'] ?? '',
        'parcelas' => $primeira['qt_parcelas'],
    ];
}

$categorias = listar_categorias($pdo);
$formas = listar_formas($pdo);
$cartoes = listar_cartoes($pdo, $uid);
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'ds_transacao' => trim($_POST['ds_transacao'] ?? ''),
        'valor' => trim($_POST['valor'] ?? ''),
        'data' => trim($_POST['data'] ?? ''),
        'cd_categoria' => (int) ($_POST['cd_categoria'] ?? 0),
        'cd_forma_pagamento' => (int) ($_POST['cd_forma_pagamento'] ?? 0),
        'cd_cartao' => (int) ($_POST['cd_cartao'] ?? 0),
        'parcelas' => (int) ($_POST['parcelas'] ?? 1),
    ];
    $centavos = parse_valor($dados['valor']);

    if (!csrf_valido()) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } elseif (mb_strlen($dados['ds_transacao']) < 2 || mb_strlen($dados['ds_transacao']) > 120) {
        $erro = 'Descreva o lançamento (2 a 120 caracteres).';
    } elseif ($centavos === null || $centavos <= 0) {
        $erro = 'Informe um valor maior que zero, por exemplo 1200,00.';
    } elseif (!data_valida($dados['data'])) {
        $erro = 'Informe uma data válida.';
    } else {
        $payload = [
            'cd_categoria' => $dados['cd_categoria'],
            'cd_forma_pagamento' => $dados['cd_forma_pagamento'],
            'cd_cartao' => $dados['cd_cartao'] ?: null,
            'ds_transacao' => $dados['ds_transacao'],
            'total' => $centavos,
            'parcelas' => $dados['parcelas'],
            'data' => $dados['data'],
        ];
        try {
            if ($editando) {
                atualizar_compra($pdo, $uid, $id, $payload);
                flash('Lançamento atualizado.');
            } else {
                em_transacao($pdo, fn() => criar_compra($pdo, $uid, $payload));
                flash($payload['parcelas'] > 1 && eh_credito(array_column($formas, 'nm_forma_pagamento', 'cd_forma_pagamento')[$dados['cd_forma_pagamento']] ?? '')
                    ? "Compra lançada em {$payload['parcelas']}x!" : 'Lançamento salvo!');
            }
            redirecionar('historico.php?mes=' . substr($dados['data'], 0, 7));
        } catch (RuntimeException $ex) {
            $erro = $ex->getMessage();
        }
    }
}

$ativa = 'historico';
$titulo = $editando ? 'Editar lançamento' : 'Novo lançamento';
$idsCredito = array_values(array_map(fn($f) => (int) $f['cd_forma_pagamento'], array_filter($formas, fn($f) => eh_credito($f['nm_forma_pagamento']))));
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - <?= e($titulo) ?></title>
    <script src="js/tema.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="bg-body-tertiary min-vh-100 d-flex flex-column">

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container my-4 flex-grow-1" style="max-width: 560px;">
        <h2 class="h5 fw-bold mb-3"><?= e($titulo) ?></h2>

        <?php if ($erro): ?><div class="alert alert-danger py-2 small"><?= e($erro) ?></div><?php endif; ?>
        <?php if ($editando && (int) $dados['parcelas'] > 1): ?>
            <div class="alert alert-info py-2 small">Esta compra tem <?= (int) $dados['parcelas'] ?> parcelas. Ao salvar, todas são recalculadas com os dados abaixo (valor <b>total</b> e data da 1ª parcela).</div>
        <?php endif; ?>

        <form method="post" class="card border-0 shadow-sm rounded-4 p-4" novalidate>
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="mb-3">
                <label class="form-label small fw-semibold" for="ds_transacao">Descrição</label>
                <input class="form-control" id="ds_transacao" name="ds_transacao" value="<?= e($dados['ds_transacao']) ?>" maxlength="120" placeholder="Ex.: Notebook, Mercado, Salário" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold" for="valor">Valor total (R$)</label>
                    <input class="form-control" id="valor" name="valor" value="<?= e((string) $dados['valor']) ?>" inputmode="decimal" placeholder="1200,00" required>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold" for="data">Data da compra</label>
                    <input class="form-control" type="date" id="data" name="data" value="<?= e($dados['data']) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" for="cd_categoria">Categoria</label>
                <select class="form-select" id="cd_categoria" name="cd_categoria" required>
                    <option value="">Selecione...</option>
                    <?php
                    $grupo = null;
                    foreach ($categorias as $c):
                        if ($c['ds_tipo'] !== $grupo) {
                            if ($grupo !== null) echo '</optgroup>';
                            $rotulos = ['entrada' => 'Entradas (receitas)', 'gasto' => 'Gastos (despesas)'];
                            echo '<optgroup label="' . e($rotulos[mb_strtolower($c['ds_tipo'])] ?? ucfirst($c['ds_tipo'])) . '">';
                            $grupo = $c['ds_tipo'];
                        }
                    ?>
                        <option value="<?= (int) $c['cd_categoria'] ?>" <?= (int) $dados['cd_categoria'] === (int) $c['cd_categoria'] ? 'selected' : '' ?>><?= e($c['nm_categoria']) ?></option>
                    <?php endforeach; if ($grupo !== null) echo '</optgroup>'; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" for="cd_forma_pagamento">Forma de pagamento</label>
                <select class="form-select" id="cd_forma_pagamento" name="cd_forma_pagamento" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($formas as $f): ?>
                        <option value="<?= (int) $f['cd_forma_pagamento'] ?>" <?= (int) $dados['cd_forma_pagamento'] === (int) $f['cd_forma_pagamento'] ? 'selected' : '' ?>><?= e($f['nm_forma_pagamento']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Só aparece quando a forma de pagamento é crédito -->
            <div id="blocoCredito" class="border rounded-3 p-3 mb-3 bg-body-tertiary" hidden>
                <?php if (!$cartoes): ?>
                    <div class="small">Você ainda não tem cartão. <a href="cartao_form.php">Cadastre um cartão</a> para lançar no crédito.</div>
                <?php else: ?>
                    <div class="row g-3">
                        <div class="col-sm-7">
                            <label class="form-label small fw-semibold" for="cd_cartao">Cartão</label>
                            <select class="form-select" id="cd_cartao" name="cd_cartao">
                                <option value="">Selecione...</option>
                                <?php foreach ($cartoes as $k): ?>
                                    <option value="<?= (int) $k['cd_cartao'] ?>" <?= (int) $dados['cd_cartao'] === (int) $k['cd_cartao'] ? 'selected' : '' ?>><?= e($k['nm_cartao']) ?><?= $k['nr_ultimos4'] ? ' •••• ' . e($k['nr_ultimos4']) : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label small fw-semibold" for="parcelas">Parcelas</label>
                            <select class="form-select" id="parcelas" name="parcelas">
                                <?php for ($i = 1; $i <= 48; $i++): ?>
                                    <option value="<?= $i ?>" <?= (int) $dados['parcelas'] === $i ? 'selected' : '' ?>><?= $i ?>x</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div id="previa" class="small text-body-secondary mt-2"></div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-success flex-grow-1" type="submit">Salvar</button>
                <a href="historico.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const idsCredito = <?= json_encode($idsCredito) ?>;
        const forma = document.getElementById('cd_forma_pagamento');
        const bloco = document.getElementById('blocoCredito');
        const valor = document.getElementById('valor');
        const parcelas = document.getElementById('parcelas');
        const previa = document.getElementById('previa');

        function lerValor(t) {
            t = (t || '').replace(/[^\d,.]/g, '');
            if (t.includes(',')) t = t.replace(/\./g, '').replace(',', '.');
            const n = parseFloat(t);
            return isNaN(n) ? 0 : n;
        }
        function atualizar() {
            const credito = idsCredito.includes(parseInt(forma.value));
            bloco.hidden = !credito;
            if (!credito && parcelas) parcelas.value = 1;
            if (credito && parcelas && previa) {
                const n = parseInt(parcelas.value), v = lerValor(valor.value);
                previa.textContent = (n > 1 && v > 0)
                    ? n + ' parcelas de aproximadamente ' + (v / n).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'})
                    : '';
            }
        }
        [forma, valor, parcelas].forEach(el => el && el.addEventListener('input', atualizar));
        atualizar();
    </script>
</body>

</html>