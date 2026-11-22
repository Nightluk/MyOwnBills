<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/consultas.php';
exigir_login();

$pdo = db();
$u = usuario_atual();
$categorias = listar_categorias($pdo);
$formas = listar_formas_pagamento($pdo);
$cartoes = listar_cartoes($pdo, $u['id']);
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $ds = trim($_POST['ds_transacao'] ?? '');
        $vl = filter_input(INPUT_POST, 'vl_transacao', FILTER_VALIDATE_FLOAT);
        $dt = $_POST['dt_transacao'] ?? date('Y-m-d');
        $cat = filter_input(INPUT_POST, 'cd_categoria', FILTER_VALIDATE_INT);
        $fp = filter_input(INPUT_POST, 'cd_forma_pagamento', FILTER_VALIDATE_INT);
        $cartao = !empty($_POST['cd_cartao']) ? (int) $_POST['cd_cartao'] : null;
        $parcelas = filter_input(INPUT_POST, 'qt_parcelas', FILTER_VALIDATE_INT) ?: 1;

        if (mb_strlen($ds) < 2) {
            $erro = 'Informe a descrição da transação/dívida.';
        } elseif ($vl === false || $vl <= 0) {
            $erro = 'Informe um valor maior que zero.';
        } elseif (!$cat || !$fp) {
            $erro = 'Selecione categoria e forma de pagamento.';
        } else {
            criar_transacao($pdo, $u['id'], [
                'ds_transacao'       => $ds,
                'vl_transacao'       => $vl,
                'dt_transacao'       => $dt,
                'cd_categoria'       => $cat,
                'cd_forma_pagamento' => $fp,
                'cd_cartao'          => $cartao,
                'qt_parcelas'        => $parcelas,
            ]);
            header('Location: historico.php');
            exit;
        }
    }
}
$ativa = 'historico';