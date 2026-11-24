<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$uid = usuario_atual()['id'];
$pdo = db();

$mes = $_GET['mes'] ?? $_POST['mes'] ?? '';
if (!mes_valido($mes)) {
    $mes = date('Y-m');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        flash('Sessão expirada. Tente de novo.', 'danger');
    } elseif (($_POST['acao'] ?? '') === 'excluir') {
        try {
            $ok = em_transacao($pdo, fn() => excluir_compra($pdo, $uid, (int) ($_POST['id'] ?? 0)));
            flash($ok ? 'Lançamento excluído.' : 'Lançamento não encontrado.', $ok ? 'success' : 'danger');
        } catch (RuntimeException $ex) {
            flash($ex->getMessage(), 'danger');
        }
    }
    redirecionar('historico.php?mes=' . $mes);
}

$transacoes = listar_transacoes_mes($pdo, $uid, $mes);
$tot = totais($transacoes);
$ativa = 'historico';
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Histórico</title>
    <script src="js/tema.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="bg-body-tertiary min-vh-100 d-flex flex-column">

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container my-4 flex-grow-1" style="max-width: 820px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0">Histórico</h2>
            <a href="transacao_form.php" class="btn btn-success btn-sm rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i>Novo lançamento</a>
        </div>

        <?= exibir_flash() ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <a class="btn btn-sm btn-outline-secondary" href="historico.php?mes=<?= e(mes_deslocado($mes, -1)) ?>"><i class="bi bi-chevron-left"></i></a>
            <span class="fw-semibold text-capitalize"><?= e(nome_mes($mes)) ?></span>
            <a class="btn btn-sm btn-outline-secondary" href="historico.php?mes=<?= e(mes_deslocado($mes, 1)) ?>"><i class="bi bi-chevron-right"></i></a>
        </div>

        <div class="row g-2 mb-3 text-center">
            <div class="col-4"><div class="bg-body rounded-4 shadow-sm p-2"><div class="small text-body-secondary">Receitas</div><div class="fw-bold text-success"><?= e(moeda($tot['receitas'])) ?></div></div></div>
            <div class="col-4"><div class="bg-body rounded-4 shadow-sm p-2"><div class="small text-body-secondary">Despesas</div><div class="fw-bold text-danger"><?= e(moeda($tot['despesas'])) ?></div></div></div>
            <div class="col-4"><div class="bg-body rounded-4 shadow-sm p-2"><div class="small text-body-secondary">Saldo</div><div class="fw-bold"><?= e(moeda($tot['saldo'])) ?></div></div></div>
        </div>

        <div class="d-flex flex-column gap-2">
            <?php if (!$transacoes): ?>
                <div class="text-center text-body-secondary py-5">Nenhum lançamento neste mês.</div>
            <?php endif; ?>
            <?php foreach ($transacoes as $t): ?>
                <?= linha_transacao($t, true, $mes) ?>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
