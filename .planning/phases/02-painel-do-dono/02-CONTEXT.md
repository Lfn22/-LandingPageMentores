# Phase 2: Painel do Dono - Context

**Gathered:** 2026-09-24
**Status:** Ready for planning
**Mode:** Auto (discuss skipped for same-day deadline)

<domain>
## Phase Boundary

Dono da mentoria faz login seguro em `/admin/`, vê KPIs, Radar de demanda e a Fila de trabalho (busca + status editável), copia/baixa CSV e faz logout. Reutiliza 100% a camada da Fase 1 (`app/bootstrap.php`, `app/lib/{db,session,security,leads}.php`, tabelas `users`, `leads.status`, `rate_limits`).

</domain>

<decisions>
## Implementation Decisions

### Diretriz do usuário
- "Sem muita complicação, coisa simples, sucinta e segura." Poucos arquivos, PHP renderizado no servidor, JS vanilla mínimo. Nada além do escopo.

### Arquivos (proposta)
- `public/admin/index.php` → se sem sessão: formulário de login; com sessão: painel (KPIs, radar, fila).
- `public/admin/login.php` (POST), `public/admin/logout.php` (POST com CSRF), `public/admin/export.php` (GET → download CSV), `public/admin/status.php` (POST com CSRF → atualiza status; responde JSON ou redirect).
- Pode consolidar em menos arquivos se ficar mais simples (ex.: um `index.php` com `action`), a critério do planner.
- `app/lib/auth.php` → `auth_login()`, `auth_user()`, `auth_require()`, `auth_logout()`.
- `app/views/admin/*.php` se necessário; `public/assets/css/admin.css`, `public/assets/js/admin.js` (copiar CSV p/ clipboard, busca client-side opcional, troca de status).

### Autenticação (PAIN-01, PAIN-04)
- E-mail + senha contra `users` com `password_verify`; `session_regenerate_id(true)` no login; atualiza `last_login_at`.
- Força bruta: `rate_limit_hit($pdo, 'login', $ip, max, window)` usando a chave de config existente `rate_limit.login` (ver config); mensagem genérica "E-mail ou senha inválidos".
- CSRF em login, logout e troca de status. Cookie de sessão HttpOnly/SameSite=Lax/Secure quando HTTPS (já em `session_start_secure`).
- Timeout de inatividade simples (ex.: 2h) guardado na sessão.
- Todas as páginas/endpoints do admin chamam `auth_require()`; export/status sem sessão → 302 p/ login ou 401.
- `public/admin/` com `<meta name="robots" content="noindex">` e header `X-Robots-Tag: noindex`.

### Painel (PAIN-02, PAIN-05..09) — seguir `.planning/references/painel-admin-ref.png`
- Fundo azul-ardósia escuro (~#263039), card branco grande, cantos ~16px, bordas finas; botões com borda "Copiar CSV", "Baixar CSV", "Sair".
- KPIs: Inscritos no total, Novos nos últimos 7 dias, Inscritos hoje (datas em America/Sao_Paulo, coerente com `now_db()`).
- Radar de demanda: contagem por produto a partir de `leads.interests` (JSON) cruzando com títulos de `site.php`; vazio → "Sem demandas registradas ainda."
- Fila de trabalho: busca por nome, e-mail ou demanda (GET `q`, server-side com LIKE parametrizado; pode ter filtro client-side extra); colunas Nome, Contato (telefone formatado + e-mail, links tel:/mailto:/WhatsApp), Demandas, Status (select novo/contatado/fechado que salva via POST); mostrar também mensagem e data (ex.: linha expansível ou coluna/tooltip). Mais recentes primeiro. Paginação simples (ex.: 50 por página) ou limite, a critério.
- Rodapé: "Seus dados são usados apenas para contato…" (texto do config, se houver).

### CSV (PAIN-03, PAIN-08)
- Download: `export.php` gera UTF-8 com BOM, separador `;` (Excel pt-BR), todos os campos (id, data, nome, telefone, e-mail, mensagem, interesses, status, consent_at, consent_ip). Proteger contra CSV/formula injection (prefixar `'` em células que começam com `= + - @`).
- "Copiar CSV": JS busca o mesmo CSV (fetch com credenciais) e copia via `navigator.clipboard.writeText`, com feedback "Copiado!".

### Verificação
- Estender `tests/e2e.sh` (Docker, LPM_PORT=8091 pois 8080 está ocupada): login inválido → falha; login válido → painel com KPIs; export sem sessão → bloqueado; export com sessão → BOM + cabeçalho; troca de status persiste; logout invalida sessão; rate limit de login.

### Claude's Discretion
- Nomes exatos, consolidação de arquivos, CSS.

</decisions>

<code_context>
## Existing Code Insights

- `app/lib/db.php`: `db(): PDO`, `now_db()`.
- `app/lib/security.php`: `e()`, `csrf_token()`, `csrf_verify()`, `client_ip()`, `rate_limit_hit()`, `csp_nonce()`, `send_security_headers()`, `valid_hex_color()`.
- `app/lib/session.php`: `session_start_secure()`, `flash_set()`, `flash_take()`.
- `app/lib/leads.php`: `lead_validate()`, `lead_insert()`, `whatsapp_url()`.
- `public/_bootstrap.php` localiza `app/`. `tests/e2e.sh` já sobe o stack e roda setup.
- Ver `.planning/phases/01-landing-captura-de-leads/01-01-SUMMARY.md` (gotchas do harness no Windows).
- Plano 01-02 (landing completa) pode estar em execução em paralelo: NÃO tocar em `public/index.php`, `public/assets/css/site.css`/`app.css`, `public/assets/js` da landing, `app/views/lead-form.php`, `app/config/site.php`. Criar arquivos próprios do admin.

</code_context>

<specifics>
## Specific Ideas

- Referência: `.planning/references/painel-admin-ref.png` + `README.md`.

</specifics>

<deferred>
## Deferred Ideas

- Edição de marca pelo painel, notificação por e-mail, múltiplos usuários → v2.

</deferred>
