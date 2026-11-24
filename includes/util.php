<?php
/** Funções utilitárias: mensagens, valores, datas. */

function flash(string $mensagem, string $tipo = 'success'): void
{
    $_SESSION['flash'][] = ['m' => $mensagem, 't' => $tipo];
}

function exibir_flash(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alert alert-' . e($f['t']) . ' alert-dismissible py-2 small" role="alert">'
            . e($f['m'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function redirecionar(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** "1.234,56" / "1234,5" / "1234.56" -> centavos (int). Retorna null se inválido. */
function parse_valor(string $texto): ?int
{
    $s = str_replace(['R$', ' ', "\u{00A0}"], '', trim($texto));
    if ($s === '') {
        return null;
    }
    if (str_contains($s, ',')) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    }
    if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $s)) {
        return null;
    }
    [$inteiro, $dec] = array_pad(explode('.', $s), 2, '0');
    return (int) $inteiro * 100 + (int) str_pad($dec, 2, '0');
}

function centavos_para_decimal(int $c): string
{
    return sprintf('%d.%02d', intdiv($c, 100), $c % 100);
}

function centavos_para_input(int $c): string
{
    return sprintf('%d,%02d', intdiv($c, 100), $c % 100);
}

function data_valida(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

function mes_valido(?string $m): bool
{
    return $m !== null && (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m);
}

function nome_mes(string $mes): string
{
    $nomes = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
              'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    [$ano, $m] = explode('-', $mes);
    return $nomes[(int) $m] . ' de ' . $ano;
}

function mes_deslocado(string $mes, int $delta): string
{
    return (new DateTimeImmutable($mes . '-01'))->modify(($delta >= 0 ? '+' : '') . $delta . ' months')->format('Y-m');
}

/** Soma n meses mantendo o dia (ou o último dia do mês, se não existir). */
function somar_meses(string $data, int $n): string
{
    $d = new DateTimeImmutable($data);
    $dia = (int) $d->format('j');
    $p = $d->modify('first day of this month')->modify(($n >= 0 ? '+' : '') . $n . ' months');
    $ultimo = (int) $p->format('t');
    return $p->setDate((int) $p->format('Y'), (int) $p->format('n'), min($dia, $ultimo))->format('Y-m-d');
}

function uuid_v4(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    $h = bin2hex($b);
    return sprintf('%s-%s-%s-%s-%s', substr($h, 0, 8), substr($h, 8, 4), substr($h, 12, 4), substr($h, 16, 4), substr($h, 20));
}

function data_br(string $iso): string
{
    return date('d/m/Y', strtotime($iso));
}
