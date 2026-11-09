<?php
require __DIR__ . '/includes/auth.php';

// Logout só por POST com CSRF, para ninguém deslogar o usuário por um link.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido()) {
    encerrar_sessao();
}
header('Location: entrar.php');
exit;
