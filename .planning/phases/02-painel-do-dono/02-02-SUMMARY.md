---
phase: 02-painel-do-dono
plan: 02
subsystem: admin

tags: [php, pdo, csv, csrf, fetch, clipboard]

# Dependency graph
requires:
  - phase: 02-painel-do-dono (plan 01)
    provides: "auth_require/admin_headers/csrf_token/csrf_verify, admin_leads()/format_phone(), o painel com data-copy-csv e link de export.php como placeholders inertes, tests/e2e-admin.sh com login/busca/logout"
provides:
  - "public/admin/export.php: download de todos os leads em CSV UTF-8 com BOM, separador ';' e proteção contra formula injection"
  - "public/admin/status.php: troca leads.status via POST protegido por sessão + CSRF + whitelist"
  - "public/assets/js/admin.js: Copiar CSV para a área de transferência e salvar status via fetch, sem recarregar"
  - "app/lib/admin.php: csv_safe() e LEAD_STATUSES"
  - "tests/e2e-admin.sh estendido cobrindo export/status autenticado e não autenticado"
affects: []

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "csv_safe(?string) prefixa ' quando o primeiro caractere é = + - @ TAB ou CR, aplicada a toda célula do CSV (não ao HTML do painel, que já é neutralizado por e())"
    - "status.php segue o mesmo padrão de public/api/lead.php: $wantsJson = str_contains(Accept, 'application/json') decide entre JSON e redirect 303"
    - "Cada linha da Fila de trabalho vira um <form method=post> próprio (hidden csrf_token+id, select, botão Salvar) que funciona sem JS; admin.js esconde o botão e envia via fetch no change do select"

key-files:
  created:
    - public/admin/export.php
    - public/admin/status.php
    - public/assets/js/admin.js
  modified:
    - app/lib/admin.php
    - app/views/admin/panel.php
    - public/assets/css/admin.css
    - tests/e2e-admin.sh

key-decisions:
  - "status.php reaproveita o content-negotiation por header Accept já usado em public/api/lead.php, em vez de um parâmetro de query, para manter um único padrão de contrato JSON/redirect no projeto"
  - "O formulário de status por linha é a base (funciona sem JavaScript); admin.js apenas aprimora a experiência (esconde o botão Salvar, envia via fetch, mostra Salvo/Erro)"

patterns-established:
  - "csv_safe() é aplicada célula a célula em export.php, nunca no HTML do painel (que usa e() para escaping)"

requirements-completed: [PAIN-03, PAIN-07, PAIN-08, PAIN-09]

# Metrics
duration: 35min
completed: 2026-09-24
---

# Phase 2 Plan 2: Export CSV e troca de status na Fila de trabalho Summary

**CSV export (UTF-8 BOM, separador ';', proteção contra formula injection) e troca de status por lead (POST com CSRF + whitelist), com "Copiar CSV" e salvamento de status via fetch em `admin.js`.**

## Performance

- **Duration:** ~35 min
- **Started:** 2026-09-24 (ver timestamps dos commits)
- **Completed:** 2026-09-24
- **Tasks:** 2 tasks automáticas + 1 checkpoint visual
- **Files modified:** 6 (3 criados, 3 modificados)

## Accomplishments
- `GET /admin/export.php` (autenticado) baixa um CSV `leads-YYYY-MM-DD.csv` com BOM UTF-8 (`efbbbf`), separador `;`, cabeçalho `id;data;nome;telefone;email;mensagem;interesses;status;consent_at;consent_ip` e todos os leads (mais recente primeiro), com toda célula passando por `csv_safe()` — uma célula com `=HYPERLINK(1)` sai como `'=HYPERLINK(1)`, texto puro para o Excel.
- `POST /admin/status.php` troca `leads.status` com `UPDATE` parametrizado, exigindo sessão (`auth_require($json)` → 401 sem sessão em JSON), CSRF válido (403 sem token/token errado) e status dentro da whitelist `LEAD_STATUSES` (422 se inválido); responde JSON `{"ok":true}` quando `Accept: application/json`, ou 303 para `/admin/` no fallback sem JS.
- Na Fila de trabalho, a coluna Status virou um `<form>` por lead (csrf_token e id ocultos, select com Novo/Contatado/Fechado, botão "Salvar") que funciona mesmo sem JavaScript.
- `admin.js` (carregado com `defer`, sem inline handlers, compatível com a CSP `script-src 'self'`): o botão "Copiar CSV" busca `/admin/export.php` com `credentials: 'same-origin'`, remove o BOM e copia via `navigator.clipboard.writeText`, mostrando "Copiado!" por 2s (ou "Não foi possível copiar" em erro); nos forms de status, esconde o botão Salvar e envia a troca via fetch no evento `change` do select, mostrando "Salvo" ou revertendo o select com "Erro".
- `export.php`/`status.php` sem sessão não entregam dados nem alteram nada: export não-autenticado responde 302 (redirect ao login), status não-autenticado em JSON responde 401.

## Task Commits

Each task was committed atomically:

1. **Task 1: e2e de CSV e status (RED)** - `a20027f` (test) — confirmado RED: `tests/e2e-admin.sh` falhou com 404 em `POST /admin/status.php` (endpoint ainda não existia), exit code 1
2. **Task 2: export.php, status.php, admin.js e select de status** - `44dc58f` (feat) — confirmado GREEN: `tests/e2e-admin.sh` imprime `E2E ADMIN OK`, exit code 0

**Plan metadata:** (this commit, docs)

## Files Created/Modified
- `public/admin/export.php` - `admin_headers()` + `auth_require()`; escreve BOM, cabeçalho e todas as linhas com `fputcsv(..., ';', '"', '\\', "\r\n")`, cada célula passando por `csv_safe()`
- `public/admin/status.php` - só POST (405 senão); `$wantsJson` por `Accept`; `auth_require($wantsJson)`; `csrf_verify()` (403); `ctype_digit($id)` + whitelist `LEAD_STATUSES` (422); `UPDATE leads SET status = ? WHERE id = ?` parametrizado; JSON `{"ok":true}` ou 303
- `public/assets/js/admin.js` - `[data-copy-csv]` (fetch + remove BOM + `navigator.clipboard.writeText` + feedback); `[data-status-form]` (esconde Salvar, `fetch` no `change` do select, feedback Salvo/Erro com rollback do valor)
- `app/lib/admin.php` - `csv_safe(?string): string` (prefixa `'` se o valor começar com `= + - @ TAB CR`); constante `LEAD_STATUSES = ['novo','contatado','fechado']`
- `app/views/admin/panel.php` - coluna Status vira `<form method=post action=/admin/status.php>` por lead (csrf_token, id, select, botão Salvar, span de feedback); `<script src="/assets/js/admin.js" defer>` antes de `</body>`
- `public/assets/css/admin.css` - `.admin-status-form`, `.admin-status-form select`, `.admin-status-feedback`
- `tests/e2e-admin.sh` - novos passos (csv-1)–(csv-5): export/status sem sessão, lead com tentativa de formula injection, export autenticado (BOM/cabeçalho/conteúdo), troca de status (403 sem CSRF, 200 + persistência, 422 inválido), painel referenciando `data-copy-csv`/`admin.js`

## Decisions Made
- `status.php` usa o mesmo padrão de negociação de conteúdo por header `Accept` já estabelecido em `public/api/lead.php` (`$wantsJson = str_contains(Accept, 'application/json')`), em vez de inventar um novo contrato — mantém o projeto consistente.
- A base do controle de status é um `<form>` HTML por linha (funciona sem JavaScript); `admin.js` é estritamente um aprimoramento progressivo por cima dessa base, não um requisito para a funcionalidade existir.

## Deviations from Plan

None - plan executado exatamente como escrito. As únicas escolhas de nomenclatura (nomes de funções auxiliares internas, rótulos de passos no e2e) ficaram a critério do executor, conforme "Claude's Discretion" do CONTEXT.md da fase.

## Issues Encountered

None além do fluxo RED→GREEN esperado (Task 1 falhou por 404 antes da implementação, como previsto pelo plano).

## User Setup Required

None - nenhuma configuração de serviço externo necessária. Verificado localmente contra o stack Docker em `http://localhost:8091` (login do dono: `dono@example.com` / `SenhaForte123!`).

Para a conferência visual (Task 3), a stack foi deixada de pé com:
- setup já executado (usuário dono@example.com / SenhaForte123!);
- 3 leads de exemplo: Ana Souza (status "contatado", já demonstrando a persistência da troca de status), Bruno Lima (status "novo") e Carla Nunes (status "novo", inserida manualmente para a conferência);
- `rate_limits` limpa, para que o login manual não esbarre no rate limit de 5 tentativas/900s consumido pelo próprio e2e.

## Next Phase Readiness
- Fase 2 (Painel do Dono) está funcionalmente completa: login seguro, KPIs, Radar de demanda, Fila de trabalho com busca e status editável, export/copiar CSV — pendente apenas a aprovação visual do dono (Task 3, checkpoint).
- Nenhum bloqueio técnico identificado.

---
*Phase: 02-painel-do-dono*
*Completed: 2026-09-24*

## Self-Check: PASSED

All 7 files (3 created, 4 modified) verified present on disk; both task commits (`a20027f`, `44dc58f`) verified in `git log`.

## Checkpoint aprovado

O usuário respondeu "aprovado" ao checkpoint visual da Task 3, sem pedidos de ajuste. Painel aceito como está. Fase 2 (Painel do Dono) encerrada como completa (2/2 planos).
