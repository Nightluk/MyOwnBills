<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$cartoes = listar_cartoes(db(), usuario_atual()['id']);
$ativa = 'cartoes';
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Meus Cartões</title>
    <script src="js/tema.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="bg-body-tertiary min-vh-100 d-flex flex-column">

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container my-4 flex-grow-1">
        <h2 class="h5 fw-bold mb-3">Meus cartões</h2>

        <?php if (!$cartoes): ?>
            <div class="text-center text-body-secondary py-5">Nenhum cartão cadastrado ainda.</div>
        <?php endif; ?>

        <div class="row g-3">
            <?php foreach ($cartoes as $k):
                $limite = (float) $k['vl_limite'];
                $usado = (float) $k['vl_fatura_aberta'];
                $pct = $limite > 0 ? min(100, round($usado / $limite * 100)) : 0;
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold"><i class="bi bi-credit-card me-2"></i><?= e($k['nm_cartao']) ?></span>
                            <?php if ($k['nr_ultimos4']): ?>
                                <span class="small text-body-secondary">•••• <?= e($k['nr_ultimos4']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-body-secondary mb-1">Fatura aberta: <?= e(moeda($usado)) ?> de <?= e(moeda($limite)) ?></div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="small text-body-secondary">
                            Fecha dia <?= (int) $k['nr_dia_fechamento'] ?> • Vence dia <?= (int) $k['nr_dia_vencimento'] ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
