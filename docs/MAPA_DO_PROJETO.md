# Mapa do projeto

Revendedora de Carros é um sistema interno de estoque. O Angular fala com scripts PHP, e o PHP grava no MySQL `bd_revendedoracarros`. Cada conta vê só os próprios cadastros. Placa e chassi continuam únicos no sistema inteiro.

O frontend sobe em `http://127.0.0.1:4200`. A API sobe com `php -S 127.0.0.1:9090 -t backend`. O banco e as regras de negócio estão em `database/schema.sql`, `database/seed.sql`, `database/queries.sql` e `docs/DECISOES_NEGOCIO.md`.

## Banco

| Tabela | Para que serve |
| --- | --- |
| `tb_usuario` | Login e senha com `password_hash`. |
| `tb_sessao` | Token da sessão (hash SHA-256) e validade de 12 horas. |
| `tb_marca` | Marcas da conta. O nome não se repete para o mesmo usuário. |
| `tb_modelo` | Modelos. Cada um pertence a uma marca. |
| `tb_acessorio` | Acessórios da conta. O mesmo acessório pode ir em vários carros. |
| `tb_cor` | Cores da conta. Apagar a cor não apaga o carro: `id_cor` fica nulo. |
| `tb_carro` | Veículo. `ano` é o ano de fabricação. `situacao` é `D`, `R` ou `V`. |
| `tb_carro_acessorio` | Ligação de vários acessórios com vários carros. |
| `tb_config_classificacao` | Faixas de novo, seminovo e velho daquela conta. |
| `tb_trabalheconosco` | Tabela antiga, sem tela e sem API. |

`database/migrate.php` não existe: a migração idempotente fica em `backend/migrate.php`. Ela cria o que falta e não apaga os veículos já gravados. `database/queries.sql` tem as consultas de apoio (acessórios do carro, estoque com situação, classificação).

## Backend

Todos os scripts em `backend/` usam `config.php` (conexão PDO, CORS, erro de banco só no log) e `lib.php` (token, JSON, validação e classificação). O cliente manda `Authorization: Bearer`. Sem token válido, a resposta é 401.

| Arquivo | O que faz |
| --- | --- |
| `backend/config.php` | Abre o MySQL e responde o preflight CORS. |
| `backend/lib.php` | Autenticação, leitura do JSON, placa, chassi, anos, valor, quilometragem, situação `D`/`R`/`V` e a classificação novo, seminovo, usado ou velho. |
| `backend/login.php` | `POST` com usuário e senha. Devolve o token. |
| `backend/logout.php` | `POST` apaga a sessão desse token. |
| `backend/usuarios.php` | `POST` cria a conta, a paleta de cores e as regras padrão. Senha com no mínimo 6 caracteres. |
| `backend/senha.php` | `POST` troca a senha. Senha atual errada responde 400. As sessões da conta são encerradas. |
| `backend/carros.php` | Lista, cria, atualiza e exclui veículos da conta. `PUT ?acao=situacao` muda só disponível, reservado ou vendido. |
| `backend/auxiliares.php` | CRUD de marca, modelo, acessório e cor. `acao` na URL: `marcas`, `modelos`, `acessorios`, `cores` e o singular no POST/PUT/DELETE. |
| `backend/stats.php` | `GET` com total, disponíveis, reservados, vendidos e a contagem por classificação. |
| `backend/configuracoes.php` | `GET` e `PUT` das faixas de classificação da conta. |
| `backend/migrate.php` | Aplica no banco existente as colunas e a situação `R`, sem reclassificar os carros. |
| `backend/popular.php` | Completa o catálogo e a frota do usuário `admin` sem apagar o que já existe. |
| `backend/tests/regras_test.php` | Confere placa, anos e a sugestão de classificação. |
| `backend/tests/fluxos_test.php` | Percorre login, catálogo, veículo, reserva e o bloqueio entre contas. |
| `backend/logs/app.log` | Erros de PDO. A mensagem interna não volta para o navegador. |

## Frontend

Rotas em `frontend/src/app/app.routes.ts`. O menu fica em `app.html` e `app.ts`. O visual comum está em `frontend/src/styles.css`.

| Rota | Tela | Arquivos | O que a tela faz |
| --- | --- | --- | --- |
| `/` | Entrar | `login/login.ts`, `login/login.component.html` | Pede usuário e senha, guarda o token e abre o inventário. |
| `/register` | Criar conta | `register/register.ts`, `register/register.html` | Cadastra o usuário e volta ao login. |
| `/dashboard` | Painel | `painel/painel.ts`, `painel/painel.html` | Mostra quantidade, valor médio, valor da coleção e gráficos de marcas, modelos, cores e acessórios. O aviso de cadastro incompleto abre a lista de carros e o que falta em cada um. |
| `/veiculos` | Inventário | `dashboard/dashboard.ts`, `dashboard/dashboard.html` | Lista os carros com placa, veículo, ano, km, valor e situação. Filtra por texto, situação, classificação, marca, cor, km e ano. A opção vazia do filtro é Nenhum. Marca e cor vêm do catálogo inteiro. A placa é verde, amarela ou vermelha. O detalhe abre o restante dos dados. |
| `/cadastrar` | Veículo | `car-form/car-form.ts`, `car-form/car-form.html` | Cria ou edita o carro. Com `?id=`, carrega o registro. Máscaras de placa, chassi, km e valor. A classificação sugerida usa km, idade e conservação. |
| `/cadastros/marcas` | Marcas | `cadastros/cadastros.ts`, `cadastros/cadastros.html` | Uma seção por vez. Inclui, edita, exclui, filtra e ordena por nome ou por ID. |
| `/cadastros/modelos` | Modelos | os mesmos arquivos | O modelo exige uma marca. A coluna mostra `marca_nome`. |
| `/cadastros/acessorios` | Acessórios | os mesmos arquivos | O acessório pode ser ligado a vários carros. |
| `/cadastros/cores` | Cores | os mesmos arquivos | Cor em uso não é excluída. Renomear atualiza os veículos. |
| `/settings` | Configurações | `settings/settings.ts`, `settings/settings.html` | Ajusta as faixas de classificação e troca a senha. |

`/cadastros` redireciona para `/cadastros/marcas`. `guards/auth.guard.ts` manda para o login quem não tem token.

Arquivos que as telas compartilham:

| Arquivo | O que faz |
| --- | --- |
| `services/api.ts` | Chama cada PHP e envia o Bearer. Em 401, encerra a sessão. |
| `services/auth.service.ts` | Guarda usuário e token no navegador. |
| `services/erros.ts` | Traduz a resposta da API para a mensagem da tela. |
| `aviso/aviso.ts` | Caixa fechada de aviso. O detalhe aparece quando a pessoa abre. |
| `domain/vehicle-rules.ts` | As mesmas regras de placa, ano, km e classificação usadas antes de enviar o carro. |
| `domain/formato.ts` | Máscaras de placa, chassi, quilometragem (`1.000`) e valor (`1.000,00`). |
| `app.config.ts` | Locale `pt-BR`. |

Os testes do Angular ficam ao lado de cada arquivo, com o sufixo `.spec.ts`. `qa_test.sh`, na raiz, roda os dois testes PHP.
