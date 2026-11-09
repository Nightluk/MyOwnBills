<?php
/** Barra superior. Defina $ativa = 'home' | 'historico' | 'cartoes' antes de incluir. */
$ativa = $ativa ?? '';
$u = usuario_atual();
$primeiroNome = explode(' ', trim($u['nome'] ?? ''))[0] ?: 'Perfil';
$cls = fn(string $pagina) => $ativa === $pagina ? 'fw-semibold' : 'opacity-75';
?>
<header class="bg-success text-white py-2 px-4 shadow-sm">
    <div class="container-fluid d-flex justify-content-between align-items-center">

        <div class="d-flex align-items-center">
            <a href="home.php" class="d-flex align-items-center text-white text-decoration-none">
                <img class="me-2" src="icon/ICON.png" alt="Logo" width="40" height="40">
                <span class="fs-4 fw-bold">MyOwnBills</span>
            </a>
        </div>

        <nav class="d-none d-md-flex gap-4">
            <a href="home.php" class="text-white text-decoration-none <?= $cls('home') ?>">Início</a>
            <a href="historico.php" class="text-white text-decoration-none <?= $cls('historico') ?>">Histórico</a>
            <a href="cartoes.php" class="text-white text-decoration-none <?= $cls('cartoes') ?>">Meus Cartões</a>
        </nav>

        <a href="perfil.php" class="d-flex align-items-center bg-white text-success px-3 py-1 rounded-pill text-decoration-none shadow-sm">
            <span class="fw-semibold me-2 text-dark"><?= e($primeiroNome) ?></span>
            <div class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center bg-success" style="width: 32px; height: 32px;">
                <img src="icon/icone.png" alt="Perfil" class="w-100 h-100 object-fit-cover">
            </div>
        </a>

    </div>
</header>
