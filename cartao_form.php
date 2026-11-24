<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$uid = usuario_atual()['id'];
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$cartao = null;
if ($id) {
    $cartao = buscar_cartao($pdo, $uid, $id);
    if (!$cartao) {
        flash('Cartão não encontrado.', 'danger');
        redirecionar('cartoes.php');
    }
}

$dados = [
    'nm_cartao' => $cartao['nm_cartao'] ?? '',
    'vl_limite' => $cartao ? centavos_para_input((int) round((float) $cartao['vl_limite'] * 100)) : '',
    'nr_dia_fechamento' => $cartao['nr_dia_fechamento'] ?? '',
    'nr_dia_vencimento' => $cartao['nr_dia_vencimento'] ?? '',
    'nr_ultimos4' => $cartao['nr_ultimos4'] ?? '',
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nm_cartao' => trim($_POST['nm_cartao'] ?? ''),
        'vl_limite' => trim($_POST['vl_limite'] ?? ''),
        'nr_dia_fechamento' => trim($_POST['nr_dia_fechamento'] ?? ''),
        'nr_dia_vencimento' => trim($_POST['nr_dia_vencimento'] ?? ''),
        'nr_ultimos4' => trim($_POST['nr_ultimos4'] ?? ''),
    ];
    $limite = parse_valor($dados['vl_limite']);
    $fecha = filter_var($dados['nr_dia_fechamento'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]);
    $vence = filter_var($dados['nr_dia_vencimento'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]);

    if (!csrf_valido()) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } elseif (mb_strlen($dados['nm_cartao']) < 2 || mb_strlen($dados['nm_cartao']) > 50) {
        $erro = 'Informe o nome do cartão (2 a 50 caracteres).';
    } elseif ($limite === null) {
        $erro = 'Informe o limite, por exemplo 5000,00.';
    } elseif ($fecha === false || $vence === false) {
        $erro = 'Os dias de fechamento e vencimento devem ficar entre 1 e 31.';
    } elseif ($dados['nr_ultimos4'] !== '' && !preg_match('/^\d{4}$/', $dados['nr_ultimos4'])) {
        $erro = 'Os últimos dígitos devem ser exatamente 4 números (ou deixe em branco).';
    } else {
        $ult4 = $dados['nr_ultimos4'] === '' ? null : $dados['nr_ultimos4'];
        $params = [
            ':n' => $dados['nm_cartao'], ':l' => centavos_para_decimal($limite),
            ':f' => $fecha, ':v' => $vence, ':q' => $ult4, ':u' => $uid,
        ];
        if ($cartao) {
            $st = $pdo->prepare('update tb_cartoes_credito set nm_cartao = :n, vl_limite = :l, nr_dia_fechamento = :f,
                                 nr_dia_vencimento = :v, nr_ultimos4 = :q where cd_cartao = :c and cd_usuario = :u');
            $st->execute($params + [':c' => $id]);
            flash('Cartão atualizado. As faturas já criadas mantêm as datas antigas; as próximas usam as novas.');
        } else {
            $st = $pdo->prepare('insert into tb_cartoes_credito (cd_usuario, nm_cartao, vl_limite, nr_dia_fechamento, nr_dia_vencimento, nr_ultimos4)
                                 values (:u, :n, :l, :f, :v, :q)');
            $st->execute($params);
            flash('Cartão cadastrado!');
        }
        redirecionar('cartoes.php');
    }
}
$ativa = 'cartoes';
$titulo = $cartao ? 'Editar cartão' : 'Novo cartão';
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

    <main class="container my-4 flex-grow-1" style="max-width: 520px;">
        <h2 class="h5 fw-bold mb-3"><?= e($titulo) ?></h2>

        <?php if ($erro): ?><div class="alert alert-danger py-2 small"><?= e($erro) ?></div><?php endif; ?>

        <form method="post" class="card border-0 shadow-sm rounded-4 p-4" novalidate>
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="mb-3">
                <label class="form-label small fw-semibold" for="nm_cartao">Nome do cartão</label>
                <input class="form-control" id="nm_cartao" name="nm_cartao" value="<?= e($dados['nm_cartao']) ?>" maxlength="50" placeholder="Ex.: Nubank" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold" for="vl_limite">Limite (R$)</label>
                <input class="form-control" id="vl_limite" name="vl_limite" value="<?= e((string) $dados['vl_limite']) ?>" inputmode="decimal" placeholder="5000,00" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col">
                    <label class="form-label small fw-semibold" for="nr_dia_fechamento">Dia do fechamento</label>
                    <input class="form-control" type="number" min="1" max="31" id="nr_dia_fechamento" name="nr_dia_fechamento" value="<?= e((string) $dados['nr_dia_fechamento']) ?>" required>
                </div>
                <div class="col">
                    <label class="form-label small fw-semibold" for="nr_dia_vencimento">Dia do vencimento</label>
                    <input class="form-control" type="number" min="1" max="31" id="nr_dia_vencimento" name="nr_dia_vencimento" value="<?= e((string) $dados['nr_dia_vencimento']) ?>" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold" for="nr_ultimos4">Últimos 4 dígitos <span class="text-body-secondary fw-normal">(opcional)</span></label>
                <input class="form-control" id="nr_ultimos4" name="nr_ultimos4" value="<?= e($dados['nr_ultimos4']) ?>" maxlength="4" inputmode="numeric" placeholder="1234">
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-success flex-grow-1" type="submit">Salvar</button>
                <a href="cartoes.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
