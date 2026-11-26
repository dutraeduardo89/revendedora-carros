# Revendedora de Carros

Sistema interno de estoque. O frontend é Angular, a API é PHP e o banco é MySQL (`bd_revendedoracarros`). Cada conta administra o próprio catálogo. Placa e chassi são únicos no sistema.

## Executar

Requisitos: PHP 8, MySQL e Node.js.

```bash
mysql -u admin_carros -p < database/schema.sql
mysql -u admin_carros -p < database/seed.sql
php -S 127.0.0.1:9090 -t backend
cd frontend && npm install && npx ng serve --host 127.0.0.1 --port 4200
```

Aplicação: http://127.0.0.1:4200  
API: http://127.0.0.1:9090

Em um banco que já existe, use `php backend/migrate.php` em vez de recriar o schema. A conexão lê `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS`. Sem essas variáveis, o padrão local está em `backend/config.php`.

O seed cria o usuário `admin`. A senha inicial está comentada em `database/seed.sql`.

## Branches

`main` é a linha estável. `develop` é a linha de integração.

## Documentação

- `docs/MAPA_DO_PROJETO.md` descreve telas, endpoints e tabelas.
- `docs/DECISOES_NEGOCIO.md` registra as regras de cadastro e classificação.
- `database/schema.sql` é o schema. `database/queries.sql` reúne as consultas de apoio.
