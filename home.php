<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$u = usuario_atual();
$transacoes = listar_transacoes(db(), $u['id'], 5);

$receitas = $despesas = 0.0;
foreach (listar_transacoes(db(), $u['id']) as $t) {
    if (eh_receita($t['ds_tipo'])) {
        $receitas += (float) $t['vl_transacao'];
    } else {
        $despesas += (float) $t['vl_transacao'];
    }
}
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
        <div class="row">

            <!-- Resumo -->
            <div class="col-lg-8 mb-4">
                <h2 class="h5 fw-bold mb-3">Olá, <?= e(explode(' ', $u['nome'])[0]) ?>!</h2>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="card border-0 shadow-sm rounded-4 p-3">
                            <div class="small text-body-secondary">Receitas</div>
                            <div class="fs-5 fw-bold text-success"><?= e(moeda($receitas)) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card border-0 shadow-sm rounded-4 p-3">
                            <div class="small text-body-secondary">Despesas</div>
                            <div class="fs-5 fw-bold text-danger"><?= e(moeda($despesas)) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card border-0 shadow-sm rounded-4 p-3">
                            <div class="small text-body-secondary">Saldo</div>
                            <div class="fs-5 fw-bold"><?= e(moeda($receitas - $despesas)) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Painel lateral de histórico -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-3">
                    <a href="historico.php" class="text-center mb-3 text-decoration-none">
                        <span class="bg-body px-4 py-1 rounded-pill fw-bold shadow-sm text-secondary small">Histórico</span>
                    </a>

                    <div class="d-flex flex-column gap-2 mb-3">
                        <?php if (!$transacoes): ?>
                            <div class="text-center text-body-secondary small py-3">Nenhuma transação ainda.</div>
                        <?php endif; ?>
                        <?php foreach ($transacoes as $t): ?>
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
