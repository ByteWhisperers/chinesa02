# 🗺️ ROADMAP — Lógica Interna do Projeto

> Plataforma de cassino/apostas online (mercado brasileiro, PIX) — backend em **PHP procedural + MySQL** e frontend **SPA (Vite/React, compilado)** servido como assets estáticos.

---

## 1. Visão geral da arquitetura

```
┌──────────────────────────────────────────────────────────────────┐
│  NAVEGADOR (SPA já compilada)                                      │
│  index.php  →  /yq-br-prod/web1/assets/*.js  (React/Vite)         │
└───────────────┬──────────────────────────────────────────────────┘
                │ fetch JSON  (cookie token_user)
                ▼
┌──────────────────────────────────────────────────────────────────┐
│  ROTEAMENTO (.htaccess - Apache mod_rewrite)                       │
│   /api/*           → api/v1/api.php      (API principal)           │
│   /drakon_api*     → callback/drakon.php                           │
│   /gold_api/*      → callback/game_callback.php / user_balance.php │
│   resto            → index.php           (SPA fallback)            │
└───────────────┬──────────────────────────────────────────────────┘
                ▼
┌─────────────────────────┐   ┌──────────────────────────────────┐
│  API REST  (api/v1)      │   │  PAINEL ADMIN  (/dash)            │
│  ~50 rotas member/finance│   │  PHP + Bootstrap (Mannatthemes)   │
└───────┬─────────────────┘   └───────────────┬──────────────────┘
        │                                      │
        ▼                                      ▼
┌──────────────────────────────────────────────────────────────────┐
│  CAMADA DE SERVIÇOS (dash/services + services-prod)                │
│   database.php  funcao.php  crud.php  prod.php  CSRF_Protect.php    │
└───────┬───────────────────────┬───────────────────┬──────────────┘
        ▼                       ▼                   ▼
   MySQL (mysqli)        Gateways PIX          Provedores de jogos
   ~38 tabelas        (gateway/*.php)          (callback/*.php)
                      GeraPix, SuitPay,        Drakon, PlayFiver,
                      SyncPay, AkadPay,        Royal, ApiPhillyps
                      BsPay                    (FiverScan)
```

**Características técnicas importantes (estado atual do código):**
- PHP procedural, sem framework, sem composer/autoload — tudo via `include_once`.
- Mistura de **duas conexões de banco**: `mysqli` legado (`dash/services/database.php`) e um `PDO` mais novo (`app/config.php`). Credenciais divergem entre os dois arquivos.
- Mistura de queries: parte usa **prepared statements**, parte **concatena strings** (risco de SQL injection — ver §9).
- Autenticação por **cookie `token_user`** (token MD5 guardado na tabela `usuarios`), sem JWT.

---

## 2. Pontos de entrada (entry points)

| Arquivo | Papel |
|---|---|
| `index.php` | Bootstrap da SPA. Registra visita (`visita_site`), injeta meta tags, carrega `config.js`, `theme.php` e o bundle Vite. |
| `api/v1/api.php` | **Núcleo da API** (~8200 linhas). Um único arquivo com `switch($requestMethod)` + cadeia de `if ($requestURI === ...)`. |
| `dash/index.php` | Dashboard administrativo (~40 mil linhas/HTML). |
| `gateway/*.php` | Webhooks/IPN dos gateways de pagamento PIX. |
| `callback/*.php` | Callbacks dos provedores de jogos (saldo/aposta/ganho). |
| `member/*`, `finance/*` | **Stubs estáticos** com JSON fixo (ex.: `{"status":true,"data":"token","flag":1}`). Provavelmente fallbacks/placeholders — a lógica real vive em `api/v1/api.php`. |

---

## 3. Camada de serviços (o "core")

### `dash/services/database.php`
Conexão `mysqli` global (`$mysqli`). Timezone `America/Sao_Paulo`. Credenciais hardcoded.

### `dash/services/funcao.php`
Utilidades e constantes de URL. Funções-chave:
- `PHP_SEGURO($string)` — sanitização (escape SQL + `htmlspecialchars`).
- `CRIPT_AES($action,$string)` — AES-256-CBC (chave via env).
- `gerar_pass_key()`, `token_aff()`, `token_id_transacao()` — geração de tokens.
- `validarCPF()`, `Reais2()`, `somente_numeros()`, `ver_data*()` — helpers BR.
- `alertas_swall()`, `alertas_toaster()` — geração de alertas JS (SweetAlert/Toastr).

### `dash/services/crud.php` (~1335 linhas) — repositório de regras de negócio
Principais grupos de funções:

| Grupo | Funções |
|---|---|
| **Config/gateways** | `data_config`, `data_gerapix`, `data_syncpay`, `data_akadpay`, `data_edbanking`, `data_apiphill`, `data_fiverscanPanel`, `data_drakon`, `data_royal` |
| **Saldo** | `enviarSaldo`, `withdrawSaldo`, `adicionarSaldoRecusa`, `adicionarsaldo`, `retirarsaldo`, `saldo_user`, `att_saldo_user` |
| **Jogos (launch)** | `pegarLinkJogo` (FiverScan), `pegarLinkJogoPlayFiver`, `pegarLinkJogoApiPhillyps`, `pegarLinkDrakon`, `pegarLinkJogoRoyal`, `gameLaunchDrakon`, `gamecode`, `gameprovider`, `gametype` |
| **Tokens API jogos** | `generateTokenDrakon`, `generateTokenRoyal`, `criarUsuarioAPI` |
| **Afiliados** | `afiliado_de_quem`, `pegar_refer`, `count_refer_direto`, `data_afiliados_cpa_rev`, `total_CPA_REV_id`, `total_CPA_id`, `total_REV_id` |
| **Financeiro** | `criar_financeiro`, `financeiro_saldo_user`, `total_saques_id`, `total_dep_id`, `total_dep_pagos_id`, `insert_payment_adm` |
| **Pagamento PIX** | `request_paymentPIX`, `requestToken_PAYMENT`, `enviarRequest_PAYMENT`, `localizarchavepix` |
| **HTTP** | `enviarRequest`, `enviarRequestDrakon` (wrappers cURL) |

### `dash/services-prod/prod.php`
- `_qrcode($valor,$nome,$id,$cpf)` — **dispatcher de gateway**: percorre `edbanking → syncpay → versellpay → akadpay → gerapix`, escolhe o primeiro com `ativo=1` na tabela correspondente e chama `criarQrCode<Gateway>()`.
- `gerarPessoaAleatoria()` — gera nome/CPF fictícios quando faltam dados do pagador.

### `dash/services-gateway/`
- `payment_auto.php` — saque automático via API do gateway.
- `payment_manual.php` / `payment_manual_digito.php` — saque manual (aprovação no admin).

---

## 4. A API REST (`api/v1/api.php`)

### Como funciona o roteamento
1. Lê o corpo com `parse_str(file_get_contents("php://input"), $data)` → **espera `application/x-www-form-urlencoded`** (não JSON).
2. `switch ($_SERVER['REQUEST_METHOD'])` com cases `POST` e `GET`.
3. Dentro de cada case, cadeia de comparações:
   - POST: `if ($requestURI === '/api/...')`
   - GET: `if (parse_url($requestURI, PHP_URL_PATH) === '/api/...')`
4. Autenticação por rota: `if (isset($_COOKIE['token_user']))` → `SELECT * FROM usuarios WHERE token=...`.
5. Resposta JSON padronizada: `{"status":bool,"data":...,"msg":...}` (alguns retornam `code`/`time`).

### Mapa de rotas

**POST**
| Rota | Função |
|---|---|
| `/api/member/reg` | Cadastro (cria `usuarios` + `bau`, processa afiliado) |
| `/api/member/login` | Login (`password_verify`, seta cookie `token_user`) |
| `/api/member/platform/list` | Lista de plataformas/provedores |
| `/api/member/recall/balance` | Recarrega saldo |
| `/api/member/password/update` | Altera senha |
| `/api/member/wpw/check` | Checa senha de saque |
| `/api/member/bankcard/insert` | Cadastra chave PIX |
| `/api/member/bind/email` / `/api/member/update` | Atualiza perfil |
| `/api/finance/withdraw` | **Solicitação de saque** (valida senha, rollover, limite diário, saque automático) |
| `/api/finance/third/deposit` | **Depósito** — gera QR Code PIX via `_qrcode()` |
| `/api/member/agent/sub/member`, `/api/member/agency/*`, `/api/member/rebate/agency/brief` | Painel de afiliados/agência |
| `/api/member/history/save`, `/api/member/favorites/save` | Histórico/favoritos |
| `/api/promo/invite/*`, `/api/promo/sync/dept/promo` | Promoções/convites |
| `/api/member/slot/search` | Busca de slots |
| `/api/webtrack/addLogin` | Tracking |

**GET** (parse_url path)
| Rota | Função |
|---|---|
| `/api/member/info`, `/api/member/short/info` | Dados do usuário logado |
| `/api/member/award`, `/api/member/banner`, `/api/member/marquee`, `/api/member/notices` | Conteúdo de UI |
| `/api/member/slot/hotgame`, `/api/member/player/list` | Listagens de jogos |
| `/api/phillyps/launch/` | **Launch de jogo** → roteia por `gametype` (API30/DRAKON/ROYAL/PLAYFIVER) |
| `/api/finance/channel/type`, `/api/finance/channel/list` | Canais de depósito |
| `/api/finance/withdraw/fee`, `/api/finance/withdraw/processing` | Taxas/status de saque |
| `/api/member/record/trade`, `/api/member/record/game`, `/api/member/history/detail` | Extratos |
| `/api/member/bankcard/list`, `/api/member/bankcard/withdrawmethods` | Chaves PIX/métodos |
| `/api/member/vip/config`, `/api/member/rebate/config`, `/api/member/commission/config` | Configs VIP/rebate |
| `/api/promo/*` (list, detail, turntable, welfare, etc.) | Promoções/roleta |
| `/api/member/customer/list` | Atendimento/suporte |

---

## 5. Fluxos críticos de negócio

### 5.1 Cadastro (`POST /api/member/reg`)
1. Valida senha ≥ 6 chars e duplicidade por `mobile`.
2. Gera `token = md5(...)`, `invite_code = 'AF'+hash5`, hash de senha (`password_hash`, cost 10).
3. Lê `saldo_inicial` de `config`.
4. `INSERT usuarios` (prepared) + `INSERT bau` + incrementa `pessoas_convidadas` do afiliado.
5. Seta cookie `token_user` (30 dias) e header `id: f51:<token>`.

### 5.2 Login (`POST /api/member/login`)
`SELECT * FROM usuarios WHERE mobile=?` → `password_verify` → cookie. Códigos: `1000` ok, `1006` user inexistente, `1007` senha errada.

### 5.3 Depósito PIX (`POST /api/finance/third/deposit`)
1. Identifica usuário pelo cookie.
2. `_qrcode(amount, nome, id, cpf, gateway)` escolhe o gateway ativo e cria a cobrança.
3. Retorna `qr_code_image` (base64) + `qr_code_data` (copia-e-cola) + `id` da transação.
4. Crédito do saldo só ocorre no **webhook** (assíncrono).

### 5.4 Webhook de pagamento (ex.: `gateway/gerapix.php`)
1. Valida `Authorization: Bearer <secret>` contra `data_gerapix['secret']`.
2. Se `status === 'Aprovado'` → `att_paymentpix_gerapix(id_transacao)`:
   - **Proteção contra duplo crédito** (checa `status` atual da transação).
   - `busca_valor_ipn_gerapix` calcula **bônus de 1º depósito** (`porcentage_bonus_deposit`) e chama `enviarSaldo(mobile, valor+bonus)`.
   - `UPDATE transacoes SET status='1'`.
3. Responde `200 {"received":true}`.

> Outros webhooks: `gateway/webhook.php` (SuitPay — status `PAID_OUT` → `att_paymentpix`), `syncpay.php`, `akadpay.php`, `bspay.php`.

### 5.5 Saque (`POST /api/finance/withdraw`)
Validações em cascata:
1. Senha de saque (`senhaparasacar`, comparação **em texto plano** — ver §9).
2. Limite diário (`config.limite_saque`; regra extra: 2º saque + total > 500).
3. **Rollover**: `valor_saque ≥ total_depositos * config.rollover`.
4. Valor entre `config.minsaque` e `saldo`.
5. `withdrawSaldo()` debita → `INSERT solicitacao_saques`.
6. Se `valor ≤ config.saque_automatico` → chama `services-gateway/payment_auto.php` (PIX OUT automático) e marca status `1`; senão fica pendente para aprovação no admin.

### 5.6 Jogo — launch e callback
**Launch** (`GET /api/phillyps/launch/?code=&id=`):
`gamecode(code)` → `gameprovider()` + `gametype()` → roteia para o provedor certo (`API30`→ApiPhillyps, `DRAKON`, `ROYAL`, `PLAYFIVER`) → retorna `gameURL`.

**Callback de jogo** (`POST /gold_api/game_callback` → `callback/game_callback.php`):
- `method == "user_balance"` → retorna saldo atual (`SELECT saldo FROM usuarios WHERE mobile=?`).
- `method == "transaction"` / `game_type == "slot"`:
  1. Calcula `saldo - bet + win`.
  2. `INSERT historico_play`.
  3. `UPDATE usuarios SET saldo`.
  4. **Comissão de afiliado (RevShare)**: se o jogador tem `invitation_code` e o afiliado é REV/CPA+REV, credita `bet * revShareLvl1% - win` no saldo do afiliado.
- Todas as requisições são logadas em `callback/game_log.json`.

---

## 6. Modelo de dados (MySQL — ~38 tabelas)

| Domínio | Tabelas |
|---|---|
| **Usuários/auth** | `usuarios` (núcleo: saldo, token, invite_code, invitation_code, tipo_pagamento, senhaparasacar), `admin_users`, `tokens_recuperacoes` |
| **Financeiro** | `transacoes` (depósitos), `solicitacao_saques`, `financeiro`, `metodos_pagamentos`, `pay_valores_cassino` |
| **Gateways PIX** | `gerapix`, `syncpay`, `akadpay`, `edbanking`, `versellpay`, `digitopay` (cada um com flag `ativo`) |
| **Jogos** | `games`, `provedores`, `historico_play`, `apidrakon`, `apiphillyps`, `apiroyalgames`, `payigaming`, `fiverscan` |
| **Afiliados** | `afiliados_config`, `cupom`, `cupom_usados` |
| **Promo/gamificação** | `promocoes`, `bau`, `bonus_diario`, `bonus_history`, `popups`, `floats`, `banner`, `mensagens` |
| **Config/visual** | `config`, `tema` |
| **Logs/tracking** | `logs`, `visita_site` |
| **Legado/duplicadas** | `usuarioss`, `transacoess` (provavelmente backups — investigar) |

> A tabela `usuarios` é o centro de gravidade: saldo, autenticação, hierarquia de afiliados (`invite_code` próprio × `invitation_code` do indicador) e comissões (`rev`, `total_rev`).

---

## 7. Painel administrativo (`/dash`)

- **Auth**: `auth-login.php` + `services/checa_login_adm.php` (sessão). Credenciais demo em `PASSO A PASSO.txt`.
- **Páginas**: `usuarios.php`, `detalhes_usuario.php`, `depositos_{pendentes,pagos,expirados}.php`, `saques_{pendentes,aprovados,recusados}.php` (+ versões afiliados), `jogos.php`, `provedores.php`, `afiliados.php`, `gerenciamento-afiliados.php`, `configuracoes.php`, `gateway`/`suitpay.php`, `baus.php`, `jackpot.php`, editores de banner/popup/promoção/mensagens.
- **AJAX** (`dash/ajax/*`): listagens (DataTables) e ações (aprovar/recusar saque, atualizar games).
- **Fetch** (`dash/fetch/*`): `banir_usuario.php`, `editar_saldo.php`, `update_gateway.php`, `update_provedor.php`.
- **Proteção CSRF**: `services/CSRF_Protect.php`.

---

## 8. Integrações externas

| Tipo | Integração | Arquivos |
|---|---|---|
| **Gateways PIX (in/out)** | GeraPix, SuitPay, SyncPay, AkadPay, BsPay, EdBanking, VersellPay, DigitoPay | `gateway/*.php`, `prod.php`, `crud.php (data_*)` |
| **Provedores de jogos** | FiverScan, PlayFiver, Drakon, Royal, ApiPhillyps, PayiGaming | `crud.php (pegarLink*)`, `callback/*.php` |
| **Login social** | Google Sign-In, Facebook SDK | `index.php` (carregamento lazy), `gsi/`, `callback/` |
| **QR Code** | phpqrcode | `api/v1/phpqrcode.php`, `api/v1/pixqr.php` |

---

## 9. ⚠️ Riscos e dívida técnica observados

> Itens a tratar — relevantes por ser plataforma financeira.

1. **SQL Injection**: várias queries concatenam variáveis diretamente
   (`enviarSaldo`/`withdrawSaldo` em `crud.php`, login/reg em `api.php`,
   `third/deposit` usa `WHERE token='".$_COOKIE['token_user']."'`). Migrar **tudo** para prepared statements.
2. **Senha de saque em texto plano**: comparação `$senha_enviada === $senha_armazenada` (linha ~1035 de `api.php`). Deveria usar hash.
3. **Credenciais hardcoded** no repositório (`database.php`, `app/config.php`) — devem ir para variáveis de ambiente.
4. **Duas conexões de banco divergentes** (mysqli `u400138882_modelo2` vs PDO `sortebet_china2025`) — unificar.
5. **Arquivos sensíveis versionados**: `BancoDeDados.sql` (6 MB), `error_log`, `user_balance.json`, `game_log.json`, `database.db`. Remover do versionamento e do servidor público.
6. **Monólito de 8200 linhas** (`api.php`): difícil manutenção/teste → quebrar em controllers por domínio.
7. **CORS permissivo** (`Access-Control-Allow-Origin: *` em `app/get-link.php`).
8. **Concorrência de saldo**: updates de saldo sem transação/lock podem gerar condição de corrida em depósito/aposta/comissão simultâneos.
9. **Tabelas duplicadas** (`usuarioss`, `transacoess`) — confirmar e limpar.

---

## 10. Roteiro sugerido de evolução (fases)

| Fase | Objetivo | Itens |
|---|---|---|
| **F0 – Higiene/segurança** | Reduzir risco imediato | Hash da senha de saque; remover SQL dumps/logs do repo; mover credenciais p/ env; revisar CORS |
| **F1 – Hardening de dados** | Eliminar injeção e corrida | Prepared statements em 100% das queries; transações em saldo; idempotência reforçada nos webhooks |
| **F2 – Refatoração** | Manutenibilidade | Quebrar `api.php` em módulos por domínio; unificar conexão DB (PDO único); camada de repositório |
| **F3 – Observabilidade** | Operação | Logs estruturados (substituir JSON em arquivo); auditoria de saldo; métricas de gateway |
| **F4 – Testes/CI** | Confiabilidade | Testes dos fluxos críticos (depósito, saque, callback de jogo, comissão); pipeline CI |
| **F5 – Produto** | Crescimento | Documentar API (OpenAPI), versionar endpoints, padronizar respostas, internacionalização |

---

### Resumo em uma frase
É um cassino online PHP/MySQL onde **`api/v1/api.php`** centraliza a API (cadastro, login, depósito PIX, saque com rollover, launch de jogos), **`crud.php`/`prod.php`** concentram regras de negócio e integrações, os **webhooks de gateway** creditam saldo de forma assíncrona, os **callbacks de jogo** debitam apostas/creditam ganhos e comissões de afiliado, e o **painel `/dash`** administra tudo — com pontos de segurança importantes a endereçar antes de escalar.
