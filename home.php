<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$u = usuario_atual();
$pdo = db();
$mes = date('Y-m');
$tot = totais(listar_transacoes_mes($pdo, $u['id'], $mes));
$ultimas = listar_ultimas($pdo, $u['id'], 5);
$cartoes = listar_cartoes($pdo, $u['id']);
$ativa = 'home';
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Início</title>
    <script src="js/tema.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="bg-body-tertiary min-vh-100 d-flex flex-column">

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container-fluid my-4 px-4 flex-grow-1">
        <?= exibir_flash() ?>
        <div class="row">

            <div class="col-lg-8 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 fw-bold mb-0">Olá, <?= e(explode(' ', $u['nome'])[0]) ?>!</h2>
                    <a href="transacao_form.php" class="btn btn-success btn-sm rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i>Novo lançamento</a>
                </div>
                <div class="text-body-secondary small mb-2 text-capitalize">Resumo de <?= e(nome_mes($mes)) ?></div>
                <div class="row g-3 mb-4">
                    <div class="col-sm-4"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-body-secondary">Receitas</div><div class="fs-5 fw-bold text-success"><?= e(moeda($tot['receitas'])) ?></div></div></div>
                    <div class="col-sm-4"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-body-secondary">Despesas</div><div class="fs-5 fw-bold text-danger"><?= e(moeda($tot['despesas'])) ?></div></div></div>
                    <div class="col-sm-4"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-body-secondary">Saldo</div><div class="fs-5 fw-bold"><?= e(moeda($tot['saldo'])) ?></div></div></div>
                </div>

                <h3 class="h6 fw-bold">Meus cartões</h3>
                <?php if (!$cartoes): ?>
                    <div class="text-body-secondary small">Nenhum cartão ainda. <a href="cartao_form.php">Cadastre o primeiro</a>.</div>
                <?php endif; ?>
                <div class="row g-3">
                    <?php foreach ($cartoes as $k):
                        $limite = (float) $k['vl_limite'];
                        $pct = $limite > 0 ? min(100, round((float) $k['vl_comprometido'] / $limite * 100)) : 0;
                    ?>
                        <div class="col-sm-6">
                            <a href="fatura.php?cartao=<?= (int) $k['cd_cartao'] ?>" class="card border-0 shadow-sm rounded-4 p-3 text-decoration-none text-body">
                                <div class="fw-semibold"><i class="bi bi-credit-card me-2"></i><?= e($k['nm_cartao']) ?></div>
                                <div class="small text-body-secondary"><?= e(moeda((float) $k['vl_comprometido'])) ?> de <?= e(moeda($limite)) ?></div>
                                <div class="progress mt-2" style="height: 6px;"><div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-3">
                    <a href="historico.php" class="text-center mb-3 text-decoration-none">
                        <span class="bg-body px-4 py-1 rounded-pill fw-bold shadow-sm text-secondary small">Últimos lançamentos</span>
                    </a>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <?php if (!$ultimas): ?>
                            <div class="text-center text-body-secondary small py-3">Nenhum lançamento ainda.</div>
                        <?php endif; ?>
                        <?php foreach ($ultimas as $t): ?>
                            <?= linha_transacao($t) ?>
                        <?php endforeach; ?>
                    </div>
                    <a href="historico.php" class="text-center">
                        <button class="btn btn-secondary btn-sm w-50 rounded-pill text-white fw-semibold shadow-sm" style="background-color: #adb5bd; border: none;">Ver mais..</button>
                    </a>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
