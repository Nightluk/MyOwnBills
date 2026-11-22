<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$pdo = db();
$usuarioId = usuario_atual()['id'];
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $nmCartao = trim($_POST['nm_cartao'] ?? '');
        $vlLimite = filter_input(INPUT_POST, 'vl_limite', FILTER_VALIDATE_FLOAT);
        $diaFech  = filter_input(INPUT_POST, 'nr_dia_fechamento', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]);
        $diaVenc  = filter_input(INPUT_POST, 'nr_dia_vencimento', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]);
        $ultimos4 = preg_replace('/\D/', '', $_POST['nr_ultimos4'] ?? '');

        if (mb_strlen($nmCartao) < 2 || mb_strlen($nmCartao) > 60) {
            $erro = 'Informe um nome válido para o cartão.';
        } elseif ($vlLimite === false || $vlLimite <= 0) {
            $erro = 'Informe um limite maior que zero.';
        } elseif (!$diaFech || !$diaVenc) {
            $erro = 'Dias de fechamento e vencimento devem estar entre 1 e 31.';
        } elseif ($ultimos4 !== '' && strlen($ultimos4) !== 4) {
            $erro = 'Os últimos dígitos devem ter exatamente 4 números.';
        } else {
            criar_cartao($pdo, $usuarioId, [
                'nm_cartao'         => $nmCartao,
                'vl_limite'         => $vlLimite,
                'nr_dia_fechamento' => $diaFech,
                'nr_dia_vencimento' => $diaVenc,
                'nr_ultimos4'       => $ultimos4,
            ]);
            header('Location: cartoes.php?ok=1');
            exit;
        }
    }
}

if (isset($_GET['ok'])) {
    $sucesso = 'Cartão adicionado com sucesso!';
}

$cartoes = listar_cartoes($pdo, $usuarioId);
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

        <?php if ($erro): ?>
            <div class="alert alert-danger py-2 small" role="alert"><?= e($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alert alert-success py-2 small" role="alert"><?= e($sucesso) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
            <h3 class="h6 fw-bold mb-3"><i class="bi bi-plus-circle me-1"></i>Novo cartão</h3>
            <form method="post" action="cartoes.php" class="row g-2">
                <?= csrf_campo() ?>
                <div class="col-md-4">
                    <input type="text" name="nm_cartao" class="form-control" placeholder="Nome (ex: Nubank, Inter)" value="<?= e($_POST['nm_cartao'] ?? '') ?>" required maxlength="60">
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" min="0.01" name="vl_limite" class="form-control" placeholder="Limite (R$)" value="<?= e($_POST['vl_limite'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <input type="number" min="1" max="31" name="nr_dia_fechamento" class="form-control" placeholder="Dia fecha" value="<?= e($_POST['nr_dia_fechamento'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <input type="number" min="1" max="31" name="nr_dia_vencimento" class="form-control" placeholder="Dia vence" value="<?= e($_POST['nr_dia_vencimento'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="nr_ultimos4" class="form-control" placeholder="Final (ex: 1234)" value="<?= e($_POST['nr_ultimos4'] ?? '') ?>" maxlength="4">
                </div>
                <div class="col-12 text-end mt-2">
                    <button type="submit" class="btn btn-success btn-sm px-4 rounded-pill">Adicionar cartão</button>
                </div>
            </form>
        </div>

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