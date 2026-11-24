<?php
/**
 * Cliente mínimo da API de autenticação do Supabase (GoTrue) via cURL.
 * A senha é guardada e verificada pelo Supabase; o PHP nunca vê o hash.
 */
require_once __DIR__ . '/db.php';

function supabase_request(string $caminho, array $corpo): array
{
    carregar_env(__DIR__ . '/../.env');
    $url = rtrim((string) env('SUPABASE_URL'), '/') . $caminho;
    $chave = (string) env('SUPABASE_ANON_KEY');

    if ($url === $caminho || $chave === '') {
        return ['status' => 0, 'dados' => ['msg' => 'SUPABASE_URL / SUPABASE_ANON_KEY não configurados no .env']];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($corpo),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $chave],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resposta = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($resposta === false) {
        error_log('Supabase cURL: ' . curl_error($ch));
    }
    curl_close($ch);

    return ['status' => $status, 'dados' => json_decode((string) $resposta, true) ?: []];
}

/** Retorna ['ok'=>bool, 'erro'=>string|null, 'id'=>?string, 'email'=>?string, 'nome'=>?string] */
function supabase_login(string $email, string $senha): array
{
    $r = supabase_request('/auth/v1/token?grant_type=password', ['email' => $email, 'password' => $senha]);
    $d = $r['dados'];

    if ($r['status'] === 200 && isset($d['user']['id'])) {
        return [
            'ok'    => true,
            'erro'  => null,
            'id'    => $d['user']['id'],
            'email' => $d['user']['email'] ?? $email,
            'nome'  => $d['user']['user_metadata']['nm_usuario'] ?? null,
        ];
    }

    $codigo = $d['error_code'] ?? '';
    $erro = match (true) {
        $r['status'] === 0                    => 'Serviço de login não configurado.',
        $codigo === 'email_not_confirmed'     => 'Confirme seu e-mail antes de entrar (veja sua caixa de entrada).',
        $codigo === 'invalid_credentials'     => 'E-mail ou senha incorretos.',
        $r['status'] === 429                  => 'Muitas tentativas. Aguarde um pouco e tente de novo.',
        $r['status'] >= 400 && $r['status'] < 500 => 'E-mail ou senha incorretos.',
        default                               => 'Não foi possível entrar agora. Tente novamente.',
    };
    return ['ok' => false, 'erro' => $erro];
}

/**
 * Cria a conta. Se o projeto exige confirmação de e-mail, 'confirmar' = true
 * e o usuário só consegue entrar depois de clicar no link recebido.
 */
function supabase_cadastrar(string $nome, string $email, string $senha): array
{
    $r = supabase_request('/auth/v1/signup', [
        'email'    => $email,
        'password' => $senha,
        'data'     => ['nm_usuario' => $nome],
    ]);
    $d = $r['dados'];
    $codigo = $d['error_code'] ?? '';

    if ($codigo === 'user_already_exists' || $codigo === 'email_exists') {
        return ['ok' => false, 'erro' => 'Já existe uma conta com esse e-mail.'];
    }
    if ($codigo === 'weak_password') {
        return ['ok' => false, 'erro' => 'Senha fraca. Use pelo menos 6 caracteres, com letras e números.'];
    }
    if ($r['status'] === 429 || $codigo === 'over_email_send_rate_limit') {
        return ['ok' => false, 'erro' => 'Muitas tentativas. Aguarde um pouco e tente de novo.'];
    }
    if ($r['status'] !== 200) {
        error_log('Supabase signup falhou (HTTP ' . $r['status'] . '): ' . json_encode($d));
        $erro = match (true) {
            $r['status'] === 0                   => 'Configuração ausente: confira SUPABASE_URL e SUPABASE_ANON_KEY no .env.',
            in_array($r['status'], [401, 403], true) || $codigo === 'no_authorization'
                                                 => 'Chave do Supabase inválida: confira SUPABASE_ANON_KEY no .env.',
            $codigo === 'signup_disabled'        => 'Cadastros estão desativados no Supabase (Authentication → Sign In / Providers).',
            $codigo === 'email_address_invalid'  => 'O Supabase rejeitou esse e-mail. Use outro endereço.',
            $r['status'] >= 500                  => 'O Supabase falhou ao enviar o e-mail de confirmação (SMTP). Desligue "Confirm email" para testar.',
            default                              => 'Não foi possível criar a conta agora.',
        };
        if (env('APP_DEBUG') === '1') {
            $erro .= ' [HTTP ' . $r['status'] . ' ' . ($codigo ?: ($d['msg'] ?? $d['message'] ?? '')) . ']';
        }
        return ['ok' => false, 'erro' => $erro];
    }

    // Com autoconfirm: o usuário vem em $d['user'] e há access_token.
    // Com confirmação por e-mail: o usuário vem direto em $d.
    $usuario = $d['user'] ?? $d;
    // E-mail já cadastrado (e confirmação ligada): a API devolve um usuário "falso" sem identities.
    if (isset($usuario['identities']) && $usuario['identities'] === []) {
        return ['ok' => false, 'erro' => 'Já existe uma conta com esse e-mail.'];
    }
    if (!isset($usuario['id'])) {
        return ['ok' => false, 'erro' => 'Resposta inesperada do serviço de login.'];
    }

    return ['ok' => true, 'id' => $usuario['id'], 'confirmar' => !isset($d['access_token'])];
}

/** Garante a linha do usuário em tb_usuarios e devolve o nome salvo. */
function garantir_usuario(PDO $pdo, string $id, string $nomePadrao): string
{
    $st = $pdo->prepare('select nm_usuario from tb_usuarios where cd_usuario = :id');
    $st->execute([':id' => $id]);
    $nome = $st->fetchColumn();
    if ($nome !== false) {
        return $nome;
    }
    $st = $pdo->prepare('insert into tb_usuarios (cd_usuario, nm_usuario) values (:id, :nome)');
    $st->execute([':id' => $id, ':nome' => $nomePadrao]);
    return $nomePadrao;
}
