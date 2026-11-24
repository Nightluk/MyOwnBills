# MyOwnBills

Controle financeiro pessoal: cartões de crédito, faturas e lançamentos (à vista ou parcelados).

Made by:
<a href=https://github.com/Nightluk>@Luccas Santos Barbosa</a> and <a href=https://github.com/Matheus-Rocha-2010>@Matheus Rocha</a>

## Como rodar

1. Copie `.env.example` para `.env` e preencha a senha do banco e a chave publishable do Supabase.
2. No **SQL Editor** do Supabase, rode `banco/setup.sql` (uma vez; pode repetir sem problema).
3. PHP 8.1+ com as extensões `pdo_pgsql`, `curl` e `mbstring`.
4. `php -S 0.0.0.0:8000` e abra `/entrar.php`.

O `.env` nunca deve ir para o Git (já está no `.gitignore`).
