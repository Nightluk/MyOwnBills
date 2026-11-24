<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$uid = usuario_atual()['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        flash('Sessão expirada. Tente de novo.', 'danger');
    } elseif (($_POST['acao'] ?? '') === 'excluir') {
        try {
            excluir_cartao($pdo, $uid, (int) ($_POST['id'] ?? 0));
            flash('Cartão excluído.');
        } catch (RuntimeException $ex) {
            flash($ex->getMessage(), 'danger');
        }
    }
    redirecionar('cartoes.php');
}

$cartoes = listar_cartoes($pdo, $uid);
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0">Meus cartões</h2>
            <a href="cartao_form.php" class="btn btn-success btn-sm rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i>Novo cartão</a>
        </div>

        <?= exibir_flash() ?>

        <?php if (!$cartoes): ?>
            <div class="text-center text-body-secondary py-5">
                Nenhum cartão cadastrado ainda.<br>
                <a href="cartao_form.php" class="btn btn-success mt-3">Cadastrar meu primeiro cartão</a>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <?php foreach ($cartoes as $k):
                $limite = (float) $k['vl_limite'];
                $usado = (float) $k['vl_comprometido'];
                $pct = $limite > 0 ? min(100, round($usado / $limite * 100)) : 0;
                $barra = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold"><i class="bi bi-credit-card me-2"></i><?= e($k['nm_cartao']) ?></span>
                            <?php if ($k['nr_ultimos4']): ?>
                                <span class="small text-body-secondary">•••• <?= e($k['nr_ultimos4']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-body-secondary mb-1">
                            Comprometido: <?= e(moeda($usado)) ?> de <?= e(moeda($limite)) ?>
                            <span class="text-body-tertiary">(disponível <?= e(moeda(max(0, $limite - $usado))) ?>)</span>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar <?= $barra ?>" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="small text-body-secondary mb-3">
                            Fecha dia <?= (int) $k['nr_dia_fechamento'] ?> • Vence dia <?= (int) $k['nr_dia_vencimento'] ?>
                        </div>
                        <div class="d-flex gap-2 mt-auto">
                            <a href="fatura.php?cartao=<?= (int) $k['cd_cartao'] ?>" class="btn btn-sm btn-success flex-grow-1">Faturas</a>
                            <a href="cartao_form.php?id=<?= (int) $k['cd_cartao'] ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="cartoes.php" class="m-0" onsubmit="return confirm('Excluir o cartão <?= e(addslashes($k['nm_cartao'])) ?>?')">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?= (int) $k['cd_cartao'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
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
