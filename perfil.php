<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
exigir_login();

$u = usuario_atual();
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novoNome = trim($_POST['nome'] ?? '');
    if (!csrf_valido()) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } elseif (mb_strlen($novoNome) < 2 || mb_strlen($novoNome) > 100) {
        $erro = 'Informe um nome válido.';
    } else {
        $st = db()->prepare('update tb_usuarios set nm_usuario = :n where cd_usuario = :id');
        $st->execute([':n' => $novoNome, ':id' => $u['id']]);
        $_SESSION['usuario']['nome'] = $novoNome;
        $u['nome'] = $novoNome;
        $msg = 'Nome atualizado.';
    }
}
$ativa = '';
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Meu Perfil</title>
    <script src="js/tema.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="bg-body-tertiary min-vh-100 d-flex flex-column">

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container my-auto py-5 flex-grow-1 d-flex flex-column align-items-center justify-content-center">

        <div class="card border-0 shadow-sm rounded-4 p-4 w-100" style="max-width: 500px;">

            <div class="text-center mb-4">
                <img src="icon/icone.png" alt="Foto de Perfil" class="rounded-4 border shadow-sm bg-white" width="100" height="100" style="object-fit: cover;">
                <h2 class="h5 fw-bold mt-3 mb-0"><?= e($u['nome']) ?></h2>
            </div>

            <?php if ($msg): ?><div class="alert alert-success py-2 small"><?= e($msg) ?></div><?php endif; ?>
            <?php if ($erro): ?><div class="alert alert-danger py-2 small"><?= e($erro) ?></div><?php endif; ?>

            <form method="post" action="perfil.php" class="mb-3">
                <?= csrf_campo() ?>
                <label class="form-label small text-muted fw-semibold" for="nome">Nome Completo</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="nome" name="nome" value="<?= e($u['nome']) ?>" maxlength="100" required>
                    <button class="btn btn-success" type="submit">Salvar</button>
                </div>
            </form>

            <div class="mb-3">
                <label class="form-label small text-muted fw-semibold">E-mail</label>
                <input type="email" class="form-control" value="<?= e($u['email']) ?>" disabled>
            </div>

            <div class="form-check form-switch d-flex justify-content-between align-items-center bg-body p-3 rounded-3 shadow-sm mt-3">
                <label class="form-check-label fw-semibold m-0" for="darkModeSwitch">Modo escuro</label>
                <input class="form-check-input ms-0" type="checkbox" role="switch" id="darkModeSwitch" style="width: 2.5em; height: 1.25em; cursor: pointer;">
            </div>

            <form method="post" action="sair.php" class="mt-3">
                <?= csrf_campo() ?>
                <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-box-arrow-right me-1"></i>Sair</button>
            </form>
        </div>

        <footer class="text-center mt-4 text-muted small">
            <p class="mb-1">Algum problema?</p>
            <p class="mb-1">Entre em contato conosco!</p>
            <p class="fw-semibold mb-0">Número de suporte: <span class="text-success">0800-701-1234</span></p>
        </footer>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const sw = document.getElementById('darkModeSwitch');
        sw.checked = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        sw.addEventListener('change', () => {
            const tema = sw.checked ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', tema);
            try { localStorage.setItem('tema', tema); } catch (e) {}
        });
    </script>
</body>

</html>
