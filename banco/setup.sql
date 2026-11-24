-- MyOwnBills: preparação do banco para o CRUD.
-- Rode UMA vez no SQL Editor do Supabase. É seguro rodar de novo (idempotente).

-- 1) Garante que as chaves bigint se auto-incrementem (só mexe nas que ainda não têm).
do $$
declare
  r record;
  seq text;
begin
  for r in
    select * from (values
      ('tb_cartoes_credito', 'cd_cartao'),
      ('tb_categorias',      'cd_categoria'),
      ('tb_faturas',         'cd_fatura'),
      ('tb_formas_pagamento','cd_forma_pagamento'),
      ('tb_transacoes',      'cd_transacao')
    ) as t(tbl, col)
  loop
    if exists (
      select 1 from information_schema.columns
      where table_schema = 'public' and table_name = r.tbl and column_name = r.col
        and column_default is null and is_identity = 'NO'
    ) then
      seq := r.tbl || '_' || r.col || '_seq';
      execute format('create sequence if not exists public.%I owned by public.%I.%I', seq, r.tbl, r.col);
      execute format('select setval(%L, coalesce((select max(%I) from public.%I), 0) + 1, false)',
                     'public.' || seq, r.col, r.tbl);
      execute format('alter table public.%I alter column %I set default nextval(%L)',
                     r.tbl, r.col, 'public.' || seq);
    end if;
  end loop;
end $$;

-- 2) Identifica as parcelas de uma mesma compra.
alter table public.tb_transacoes add column if not exists cd_compra uuid;
create index if not exists ix_transacoes_compra  on public.tb_transacoes (cd_compra);
create index if not exists ix_transacoes_usuario on public.tb_transacoes (cd_usuario, dt_transacao);

-- 3) Formas de pagamento iniciais (só se a tabela estiver vazia).
insert into public.tb_formas_pagamento (nm_forma_pagamento)
select v from (values ('Crédito'), ('Débito'), ('Pix'), ('Dinheiro'), ('Boleto')) as t(v)
where not exists (select 1 from public.tb_formas_pagamento);

-- 4) Categorias iniciais (só se a tabela estiver vazia).
insert into public.tb_categorias (nm_categoria, ds_tipo)
select nm, tp from (values
  ('Alimentação', 'Despesa'), ('Mercado', 'Despesa'), ('Moradia', 'Despesa'),
  ('Transporte', 'Despesa'), ('Saúde', 'Despesa'), ('Educação', 'Despesa'),
  ('Lazer', 'Despesa'), ('Compras', 'Despesa'), ('Assinaturas', 'Despesa'),
  ('Outras despesas', 'Despesa'),
  ('Salário', 'Receita'), ('Rendimentos', 'Receita'), ('Outras receitas', 'Receita')
) as t(nm, tp)
where not exists (select 1 from public.tb_categorias);
