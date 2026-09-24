---
phase: 03-acabamento-e-deploy
plan: 01
subsystem: landing+deploy

tags: [php, seo, lgpd, deploy, hostinger, svg]

# Dependency graph
requires:
  - phase: 01-landing-captura-de-leads (plan 02)
    provides: "public/index.php dirigido por site.php, app/lib/security.php (e/), assets/css/site.css"
  - phase: 02-painel-do-dono (plan 02)
    provides: "painel completo com noindex já aplicado"
provides:
  - "public/privacidade.php: política de privacidade LGPD no visual do site"
  - "public/robots.txt: bloqueia /admin/ e /setup.php"
  - "public/assets/img/logo-conceicao-melo.svg: logo vetorial oficial (fundo transparente), usado em index.php e privacidade.php"
  - "DEPLOY.md: guia passo a passo para publicar uma cópia na Hostinger"
  - "scripts/build-deploy.sh: gera dist/hostinger-public_html.zip e dist/hostinger-app.zip"
affects: []

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "privacidade.php replica o setup de <head> de index.php (fontes, CSS custom properties, csp_nonce()) para reaproveitar site.css sem duplicar HTML de layout inteiro"
    - "scripts/build-deploy.sh usa uma pasta de staging (.deploy-stage/, removida no fim) para poder excluir arquivos sensíveis antes de compactar, funcionando tanto com zip (Linux/Mac) quanto com Compress-Archive (Windows/Git Bash) sem duplicar a lógica de exclusão"

key-files:
  created:
    - public/privacidade.php
    - public/robots.txt
    - public/assets/img/logo-conceicao-melo.svg
    - DEPLOY.md
    - scripts/build-deploy.sh
  modified:
    - app/config/site.php
    - public/index.php
    - .gitignore

key-decisions:
  - "Logo oficial fornecida pelo usuário em SVG (CorelDRAW, fundo transparente) substitui o PNG como imagem principal em index.php e privacidade.php; o PNG continua existindo só como og:image (redes sociais não aceitam SVG) e não foi reprocessado/otimizado, por instrução explícita do usuário"
  - "app/.htaccess (Require all denied) já existia no repo e foi apenas referenciado no DEPLOY.md como parte do caminho alternativo (app/ dentro de public_html/), sem necessidade de recriá-lo"

requirements-completed: [LAND-03, INST-03]

# Metrics
duration: ~50min
completed: 2026-09-24
---

# Phase 3 Plan 1: Acabamento e Deploy Summary

**Política de privacidade LGPD, logo oficial em SVG, SEO básico (theme-color/robots.txt), guia de deploy para a Hostinger e script que empacota os arquivos prontos para publicar.**

## Performance

- **Duration:** ~50 min
- **Started/Completed:** 2026-09-24
- **Tasks:** 5 tasks automáticas (+ 1 mudança de escopo a pedido do usuário durante a execução: logo em SVG)
- **Files modified:** 8 (5 criados, 3 modificados)

## Accomplishments

- `public/privacidade.php`: página no mesmo visual do site (reaproveita `site.css`, cores/fontes de `site_config()`), cobrindo controlador, dados coletados (nome, e-mail, telefone, mensagem, IP, data/hora do consentimento), finalidade, base legal (consentimento), retenção (até pedido de exclusão ou 24 meses), direitos do titular com contato via `lgpd.contact_email`, ausência de compartilhamento/venda e medidas de segurança.
- `site.php`: `lgpd.policy_url` agora aponta para `/privacidade.php` (o link "Política de Privacidade" no formulário de consentimento passou a apontar para a página real); nova chave `lgpd.contact_email`.
- Logo oficial (SVG vetorial fornecido pelo usuário, fundo transparente) substituiu o PNG em `index.php` e `privacidade.php` (`brand.logo` em `site.php`), com `decoding="async"`/`fetchpriority="high"` no elemento principal da landing; o PNG antigo permanece intocado, usado só como `og:image`.
- SEO básico: `theme-color` adicionado em `index.php` e `privacidade.php`; `lang="pt-BR"`, `title`/`meta description`/`og:title`/`og:description`/`og:image` e `display=swap` já existiam e foram confirmados; `public/robots.txt` bloqueando `/admin/` e `/setup.php`; páginas do painel já tinham `noindex, nofollow`.
- `DEPLOY.md`: guia passo a passo em português (criar banco → enviar arquivos, com alternativa `app/` dentro de `public_html/` usando o `app/.htaccess` já existente → `config.php` → SSL → `setup.php` → testar → nova cópia por mentor), com troubleshooting (erro 500, rate limit de login, `setup.lock`, permissão de `app/storage`) e nota para revisar a política de privacidade com orientação jurídica própria.
- `scripts/build-deploy.sh`: gera `dist/hostinger-public_html.zip` (conteúdo de `public/`) e `dist/hostinger-app.zip` (`app/` sem `config/config.php` nem `storage/*.lock`), usando `zip` quando disponível ou `Compress-Archive` via PowerShell como fallback no Windows/Git Bash. Executado uma vez: `hostinger-public_html.zip` = 364.184 bytes (~356 KB), `hostinger-app.zip` = 13.998 bytes (~14 KB). Conteúdo do zip de `app/` conferido: `config.php` e `storage/setup.lock` ausentes, `.htaccess` presente.

## Task Commits

1. **Logo oficial em SVG** (mudança pedida pelo usuário a meio da execução) - `ff1da32` (feat) — troca `brand.logo` em `site.php` e o `<img>` em `index.php` para o SVG; PNG mantido intocado como og:image
2. **Task 1: `public/privacidade.php`** - `cff56fe` (feat) — confirmado: `php -l` sem erros, `GET /privacidade.php` → 200, link do formulário aponta para `/privacidade.php`
3. **Task 2: SEO básico + robots.txt** - `562dd85` (feat) — confirmado: `theme-color` presente, `GET /robots.txt` → 200 com `Disallow: /admin/` e `/setup.php`
4. **Task 3: `DEPLOY.md`** - `6a424e6` (docs) — confirmado: arquivo existe, 78 linhas
5. **Task 4: `scripts/build-deploy.sh`** - `e5c063a` (chore) — confirmado: rodado, gera os dois zips em `dist/`, conteúdo inspecionado
6. **Ajuste: logo no cabeçalho de `privacidade.php`** - `7f13fde` (feat) — consistência visual com a landing

## Files Created/Modified

- `public/privacidade.php` - política de privacidade completa, mesmo `<head>`/CSS de `index.php`
- `public/robots.txt` - `Disallow: /admin/` e `/setup.php`
- `public/assets/img/logo-conceicao-melo.svg` - logo vetorial oficial (recebido do usuário, sem modificações)
- `DEPLOY.md` - guia de deploy Hostinger + troubleshooting
- `scripts/build-deploy.sh` - empacotamento `dist/*.zip`
- `app/config/site.php` - `brand.logo` → `.svg`; `lgpd.policy_url` → `/privacidade.php`; nova `lgpd.contact_email`
- `public/index.php` - `<img>` do logo trocado para `.svg` (`decoding`/`fetchpriority`); `<meta name="theme-color">`
- `.gitignore` - `dist/` e `.deploy-stage/` ignorados

## Deviations from Plan

### Mudança de escopo (a pedido do usuário durante a execução, não Rule 1-3)

O plano original (Task 2) previa otimizar `logo-conceicao-melo.png` via GD (redimensionar para ~600px, recompactar). Durante a execução, o usuário interrompeu com a marca vetorial oficial (SVG, CorelDRAW, fundo transparente) e pediu explicitamente para: (1) usá-la como logo principal em vez do PNG, sem reprocessá-la; (2) cancelar a otimização do PNG (que ficou só como `og:image`, intocado). O `PLAN.md` foi atualizado para refletir essa decisão antes da execução da Task 2, e a troca de logo foi commitada separadamente (`ff1da32`), como pedido.

Nenhum desvio de Rule 1-3 além disso — o restante das tarefas seguiu o plano.

## Threat Flags

Nenhuma superfície nova de segurança introduzida: `privacidade.php` é uma página estática de leitura (sem formulário, sem escrita no banco), `robots.txt` é público por natureza, e o script de deploy roda apenas localmente (não é exposto por HTTP).

## Issues Encountered

Nenhum. `scripts/build-deploy.sh` precisou de um ajuste (converter caminhos para o formato Windows antes de chamar `Compress-Archive` via PowerShell, já que `MSYS_NO_PATHCONV=1` desativa a conversão automática do Git Bash) — corrigido e verificado antes do commit, dentro do mesmo ciclo de execução da Task 4 (não conta como um novo desvio, apenas ajuste de implementação antes de confirmar o `<done>`).

## User Setup Required

Nenhuma configuração de serviço externo necessária para esta fase. Recomenda-se que o usuário revise o texto de `public/privacidade.php` com orientação jurídica própria antes de publicar (nota também deixada em `DEPLOY.md`).

## Next Phase Readiness

- v1 completo: Fases 1, 2 e 3 concluídas. Todos os requisitos v1 (`LAND-01..03`, `LEAD-01..06`, `PAIN-01..09`, `INST-01..03`) marcados como completos em `REQUIREMENTS.md`.
- Projeto pronto para publicação: seguir `DEPLOY.md` e usar `dist/hostinger-public_html.zip` / `dist/hostinger-app.zip` (gerados por `scripts/build-deploy.sh`) para enviar à Hostinger.
- Nenhum bloqueio técnico identificado.

---
*Phase: 03-acabamento-e-deploy*
*Completed: 2026-09-24*

## Self-Check

- `public/privacidade.php` → FOUND
- `public/robots.txt` → FOUND
- `public/assets/img/logo-conceicao-melo.svg` → FOUND
- `DEPLOY.md` → FOUND
- `scripts/build-deploy.sh` → FOUND
- `dist/hostinger-public_html.zip` (364.184 bytes) → FOUND
- `dist/hostinger-app.zip` (13.998 bytes) → FOUND
- Commits `ff1da32`, `cff56fe`, `562dd85`, `6a424e6`, `e5c063a`, `7f13fde` → FOUND em `git log`

## Self-Check: PASSED

Todos os arquivos citados confirmados presentes em disco; todos os commits confirmados em `git log --oneline`. `php -l` sem erros em `index.php` e `privacidade.php`; `curl` retornou 200 em `/`, `/privacidade.php` e `/robots.txt`; link de consentimento confirmado apontando para `/privacidade.php`.
