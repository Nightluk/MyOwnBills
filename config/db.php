<?php
/**
 * Conexão com o Supabase (Postgres) via PDO.
 * Uso: $pdo = db();
 */

function carregar_env(string $arquivo): void
{
    if (!is_readable($arquivo)) {
        return;
    }
    foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#' || !str_contains($linha, '=')) {
            continue;
        }
        [$chave, $valor] = explode('=', $linha, 2);
        $valor = trim($valor, " \t\"'");
        $_ENV[trim($chave)] = $valor;
    }
}

function env(string $chave, ?string $padrao = null): ?string
{
    return $_ENV[$chave] ?? (getenv($chave) !== false ? getenv($chave) : $padrao);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    carregar_env(__DIR__ . '/../.env');

    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        env('DB_HOST'),
        env('DB_PORT', '5432'),
        env('DB_NAME', 'postgres')
    );

    try {
        $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Não vazar usuário/host/senha para o navegador
                error_log('Erro de conexão com o banco: ' . $e->getMessage());
        http_response_code(500);
        exit('Erro ao conectar ao banco de dados.'
            . (env('APP_DEBUG') === '1' ? ' [' . $e->getMessage() . ']' : ''));
    }

    return $pdo;
}
