<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Início</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Ícones do Bootstrap (para o bonequinho do perfil) -->
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
                <a href="home.php" class="text-white text-decoration-none fw-semibold">Início</a>
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

    <!-- Conteúdo Principal -->
    <main class="container-fluid my-4 px-4 flex-grow-1">
        <div class="row h-100">

            <!-- Espaço à esquerda (para gráficos, extratos ou telas futuras) -->
            <div class="col-lg-8 d-flex align-items-center justify-content-center">
                <!-- Área reservada para o conteúdo principal do dashboard -->
            </div>

            <!-- Painel Lateral de Histórico -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">

                    <!-- Cabeçalho do Histórico -->
                    <a href="historico.php" class="text-center mb-3">
                        <span class="bg-white px-4 py-1 rounded-pill fw-bold shadow-sm text-secondary small">Histórico</span>
                    </a>

                    <!-- Lista de Transações -->
                    <div class="d-flex flex-column gap-2 mb-3">

                        <!-- Item base -->
                        <div class="bg-white p-2 px-3 rounded-pill d-flex align-items-center justify-content-between shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"></div>
                                <div>
                                    <div class="small fw-bold text-dark" style="font-size: 13px;">Cartão Nubank - Vendinha do seu Zé</div>
                                    <div class="text-muted" style="font-size: 11px;">Às 00:00 • Débito</div>
                                </div>
                            </div>
                            <span class="text-danger fw-bold small">- R$ 00,00</span>
                        </div>

                    </div>

                    <!-- Botão Ver Mais -->
                    <a href="historico.php" class="text-center">
                        <button class="btn btn-secondary btn-sm w-50 rounded-pill text-white fw-semibold shadow-sm" style="background-color: #adb5bd; border: none;">Ver mais..</button>
                    </a>

                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>