<?php
// Arquivo só para testar a conexão. APAGUE depois de funcionar.
require __DIR__ . '/config/db.php';

$pdo = db();
echo "Conectado!<br>";

$tabelas = $pdo->query(
    "select table_name from information_schema.tables where table_schema = 'public' order by 1"
)->fetchAll(PDO::FETCH_COLUMN);

echo "Tabelas: " . htmlspecialchars(implode(', ', $tabelas));
