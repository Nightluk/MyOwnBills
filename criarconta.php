<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/supabase.php';

if (usuario_logado()) {
    header('Location: home.php');
    exit;
}

$erro = '';
$sucesso = '';
$nome = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirma = $_POST['confirma'] ?? '';

    if (!csrf_valido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente de novo.';
    } elseif (mb_strlen($nome) < 2 || mb_strlen($nome) > 100) {
        $erro = 'Informe seu nome completo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirma) {
        $erro = 'As senhas não conferem.';
    } else {
        $r = supabase_cadastrar($nome, $email, $senha);
        if (!$r['ok']) {
            $erro = $r['erro'];
        } else {
            try {
                garantir_usuario(db(), $r['id'], $nome);
            } catch (PDOException $ex) {
                // Não bloqueia: o login tenta criar a linha de novo.
                error_log('Cadastro: falha ao gravar tb_usuarios: ' . $ex->getMessage());
            }
            if ($r['confirmar']) {
                $sucesso = 'Conta criada! Enviamos um link de confirmação para o seu e-mail. Confirme e depois faça login.';
            } else {
                iniciar_sessao($r['id'], $nome, $email);
                header('Location: home.php');
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Criar Conta</title>
    <script src="js/tema.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="d-flex align-items-center py-4 bg-body-tertiary vh-100">

    <main class="form-signin w-100 m-auto" style="max-width: 380px;">
        <form method="post" action="criarconta.php" novalidate>
            <?= csrf_campo() ?>
            <div class="d-flex align-items-center mb-2">
                <img class="me-3" src="icon/ICON.png" alt="Logo" width="50" height="50">
                <h1 class="h3 mb-0 fw-bold">MyOwnBills</h1>
            </div>

            <h2 class="h6 mb-3 text-body-secondary fw-normal">Preencha os dados para criar a sua conta:</h2>

            <?php if ($erro): ?>
                <div class="alert alert-danger py-2 small" role="alert"><?= e($erro) ?></div>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <div class="alert alert-success py-2 small" role="alert"><?= e($sucesso) ?></div>
            <?php endif; ?>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="floatingName" name="nome" placeholder="Seu Nome" value="<?= e($nome) ?>" required maxlength="100">
                <label for="floatingName">Nome Completo</label>
            </div>
            <div class="form-floating mb-2">
                <input type="email" class="form-control" id="floatingInput" name="email" placeholder="name@example.com" value="<?= e($email) ?>" required>
                <label for="floatingInput">Endereço de Email</label>
            </div>
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="floatingPassword" name="senha" placeholder="Password" required minlength="8">
                <label for="floatingPassword">Senha (mín. 8 caracteres)</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="floatingConfirmPassword" name="confirma" placeholder="Confirm Password" required>
                <label for="floatingConfirmPassword">Confirmar Senha</label>
            </div>

            <button class="btn btn-success w-100 py-2 mb-3" type="submit">Cadastrar</button>

            <div class="text-center">
                <a href="entrar.php" class="text-decoration-none text-body-secondary small">Já tem uma conta? Faça login</a>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
