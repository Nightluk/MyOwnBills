<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$uid = usuario_atual()['id'];
$pdo = db();

$cdCartao = (int) ($_GET['cartao'] ?? $_POST['cartao'] ?? 0);
$cartao = buscar_cartao($pdo, $uid, $cdCartao);
if (!$cartao) {
    flash('Cartão não encontrado.', 'danger');
    redirecionar('cartoes.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        flash('Sessão expirada. Tente de novo.', 'danger');
    } else {
        try {
            $paga = ($_POST['acao'] ?? '') === 'pagar';
            alterar_status_fatura($pdo, $uid, (int) ($_POST['fatura'] ?? 0), $paga);
            flash($paga ? 'Fatura marcada como paga.' : 'Fatura reaberta.');
        } catch (RuntimeException $ex) {
            flash($ex->getMessage(), 'danger');
        }
    }
    redirecionar('fatura.php?cartao=' . $cdCartao);
}

$faturas = listar_faturas($pdo, $cdCartao);
$ativa = 'cartoes';
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Faturas</title>
    <script src="js/tema.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="bg-body-tertiary min-vh-100 d-flex flex-column">

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container my-4 flex-grow-1" style="max-width: 820px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0"><i class="bi bi-credit-card me-2"></i>Faturas — <?= e($cartao['nm_cartao']) ?></h2>
            <a href="cartoes.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>

        <?= exibir_flash() ?>

        <?php if (!$faturas): ?>
            <div class="text-center text-body-secondary py-5">Nenhuma fatura ainda. Elas aparecem quando você lança compras no crédito deste cartão.</div>
        <?php endif; ?>

        <div class="accordion" id="faturas">
            <?php foreach ($faturas as $f):
                $paga = mb_strtolower($f['ds_status']) === 'paga';
                $itens = transacoes_da_fatura($pdo, (int) $f['cd_fatura']);
                $idAcc = 'f' . (int) $f['cd_fatura'];
            ?>
                <div class="accordion-item border-0 shadow-sm rounded-4 mb-2 overflow-hidden">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $idAcc ?>">
                            <span class="me-auto">
                                <span class="fw-semibold">Fecha em <?= e(data_br($f['dt_fechamento'])) ?></span>
                                <span class="small text-body-secondary d-block">Vence em <?= e(data_br($f['dt_vencimento'])) ?></span>
                            </span>
                            <span class="fw-bold me-3"><?= e(moeda((float) $f['vl_total'])) ?></span>
                            <span class="badge <?= $paga ? 'text-bg-success' : 'text-bg-warning' ?> me-2"><?= e($f['ds_status']) ?></span>
                        </button>
                    </h3>
                    <div id="<?= $idAcc ?>" class="accordion-collapse collapse" data-bs-parent="#faturas">
                        <div class="accordion-body">
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php foreach ($itens as $t): ?>
                                    <?= linha_transacao($t) ?>
                                <?php endforeach; ?>
                            </div>
                            <form method="post" class="m-0">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="cartao" value="<?= $cdCartao ?>">
                                <input type="hidden" name="fatura" value="<?= (int) $f['cd_fatura'] ?>">
                                <input type="hidden" name="acao" value="<?= $paga ? 'reabrir' : 'pagar' ?>">
                                <button class="btn btn-sm <?= $paga ? 'btn-outline-secondary' : 'btn-success' ?>">
                                    <?= $paga ? 'Reabrir fatura' : 'Marcar como paga' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
