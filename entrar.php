<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/supabase.php';

if (usuario_logado()) {
    header('Location: home.php');
    exit;
}

$erro = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!csrf_valido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente de novo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        $erro = 'Informe um e-mail válido e a senha.';
    } else {
        $r = supabase_login($email, $senha);
        if ($r['ok']) {
            try {
                $nome = garantir_usuario(db(), $r['id'], $r['nome'] ?: explode('@', $r['email'])[0]);
            } catch (PDOException $ex) {
                error_log('Login: falha ao ler tb_usuarios: ' . $ex->getMessage());
                $nome = $r['nome'] ?: explode('@', $r['email'])[0];
            }
            iniciar_sessao($r['id'], $nome, $r['email']);
            header('Location: home.php');
            exit;
        }
        $erro = $r['erro'];
    }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Login</title>
    <script src="js/tema.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="d-flex align-items-center py-4 bg-body-tertiary vh-100">

    <main class="form-signin w-100 m-auto" style="max-width: 380px;">
        <form method="post" action="entrar.php" novalidate>
            <?= csrf_campo() ?>
            <div class="d-flex align-items-center mb-2">
                <img class="me-3" src="icon/ICON.png" alt="Logo" width="50" height="50">
                <h1 class="h3 mb-0 fw-bold">MyOwnBills</h1>
            </div>
            <h2 class="h6 mb-3 text-body-secondary fw-normal">Por favor, logue com suas credenciais:</h2>

            <?php if ($erro): ?>
                <div class="alert alert-danger py-2 small" role="alert"><?= e($erro) ?></div>
            <?php endif; ?>

            <div class="form-floating mb-2">
                <input type="email" class="form-control" id="floatingInput" name="email" placeholder="name@example.com" value="<?= e($email) ?>" required autofocus>
                <label for="floatingInput">Endereço de Email</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="floatingPassword" name="senha" placeholder="Password" required>
                <label for="floatingPassword">Senha</label>
            </div>

            <button class="btn btn-success w-100 py-2 mb-3" type="submit">Entrar</button>

            <div class="text-center">
                <a href="criarconta.php" class="text-decoration-none text-body-secondary small">Ainda não tem uma conta? Crie a sua já!</a>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
