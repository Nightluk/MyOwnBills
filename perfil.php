<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Meu Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Ícones do Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="bg-body-tertiary min-vh-100 d-flex flex-column">

    <!-- Barra de Navegação Superior -->
    <header class="bg-success text-white py-2 px-4 shadow-sm">
        <div class="container-fluid d-flex justify-content-between align-items-center">

            <!-- Logo e Nome -->
            <div class="d-flex align-items-center">
                <a href="home.php" class="d-flex align-items-center text-white text-decoration-none">
                    <img class="me-2" src="icon/ICON.png" alt="Logo" width="40" height="40">
                    <span class="fs-4 fw-bold">MyOwnBills</span>
                </a>
            </div>

            <!-- Links de Navegação do Meio -->
            <nav class="d-none d-md-flex gap-4">
                <a href="home.php" class="text-white text-decoration-none opacity-75">Início</a>
                <a href="historico.php" class="text-white text-decoration-none opacity-75">Histórico</a>
                <a href="cartoes.php" class="text-white text-decoration-none opacity-75">Meus Cartões</a>
            </nav>

            <!-- Perfil do Usuário -->
            <a href="perfil.php" class="d-flex align-items-center bg-white text-success px-3 py-1 rounded-pill text-decoration-none shadow-sm">
                <span class="fw-semibold me-2 text-dark">Luccas</span>
                <div class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center bg-success" style="width: 32px; height: 32px;">
                    <img src="icon/icone.png" alt="Perfil" class="w-100 h-100 object-fit-cover">
                </div>
            </a>

        </div>
    </header>

    <!-- Conteúdo Principal - Cartão de Perfil Centralizado -->
    <main class="container my-auto py-5 flex-grow-1 d-flex flex-column align-items-center justify-content-center">

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light w-100" style="max-width: 500px;">

            <!-- Foto de Perfil -->
            <div class="text-center position-relative mb-4">
                <div class="d-inline-block position-relative">
                    <img src="icon/icone.png" alt="Foto de Perfil" class="rounded-4 border shadow-sm bg-white" width="100" height="100" style="object-fit: cover;">
                    <button class="position-absolute bottom-0 end-0 btn btn-success btn-sm rounded-circle shadow-sm p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Alterar foto">
                        <i class="bi bi-pencil-fill small"></i>
                    </button>
                </div>
                <h2 class="h5 fw-bold text-dark mt-3 mb-0">Luccas</h2>
            </div>

            <!-- Formulário de Dados do Perfil -->
            <form>

                <!-- Nome do Usuário -->
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Nome Completo</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-white" value="Luccas" disabled>
                        <button class="btn btn-outline-secondary" type="button" title="Editar nome"><i class="bi bi-pencil"></i></button>
                    </div>
                </div>

                <!-- E-mail do Usuário -->
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">E-mail</label>
                    <div class="input-group">
                        <input type="email" class="form-control bg-white" value="luccas@example.com" disabled>
                        <button class="btn btn-outline-secondary" type="button" title="Editar e-mail"><i class="bi bi-pencil"></i></button>
                    </div>
                </div>

                <!-- Telefone do Usuário -->
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Telefone</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-white" value="(11) 99999-9999" disabled>
                        <button class="btn btn-outline-secondary" type="button" title="Editar telefone"><i class="bi bi-pencil"></i></button>
                    </div>
                </div>

                <!-- Senha do Usuário -->
                <div class="mb-3">
                    <label class="form-label small text-muted fw-semibold">Senha</label>
                    <div class="input-group">
                        <input type="password" class="form-control bg-white" value="suasenhasupersecreta" disabled>
                        <button class="btn btn-outline-secondary" type="button" title="Mostrar/Ocultar senha"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-outline-secondary" type="button" title="Editar senha"><i class="bi bi-pencil"></i></button>
                    </div>
                </div>

                <!-- Modo Escuro Switch -->
                <div class="form-check form-switch d-flex justify-content-between align-items-center bg-white p-3 rounded-3 shadow-sm mt-4">
                    <label class="form-check-label fw-semibold text-dark m-0" for="darkModeSwitch">Modo escuro</label>
                    <input class="form-check-input ms-0" type="checkbox" role="switch" id="darkModeSwitch" style="width: 2.5em; height: 1.25em; cursor: pointer;">
                </div>

            </form>
        </div>

        <!-- Rodapé de Suporte -->
        <footer class="text-center mt-4 text-muted small">
            <p class="mb-1">Algum problema?</p>
            <p class="mb-1">Entre em contato conosco!</p>
            <p class="fw-semibold text-dark mb-0">Número de suporte: <span class="text-success">0800-701-1234</span></p>
        </footer>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>