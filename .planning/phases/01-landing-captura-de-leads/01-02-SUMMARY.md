---
phase: 01-landing-captura-de-leads
plan: 02
subsystem: landing

tags: [php, css, js, lgpd, waitlist]

# Dependency graph
requires: ["01-01"]
provides:
  - "Landing enxuta de lista de espera (duas colunas) com marca real Conceição Melo, dirigida por app/config/site.php"
  - "public/assets/{css,js,img}/* (site.css, site.js, logo-conceicao-melo.png, favicon.svg)"
affects: ["02-01", "02-02", "phase-3"]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Cache-busting de assets estáticos via filemtime() em query string (?v=)"
    - "CSS custom property inherited/overridden localmente por bloco (ex.: --color-text reaproveitando var(--color-bg) dentro do card claro) para inverter tema claro/escuro sem duplicar hex"

key-files:
  created:
    - public/assets/img/logo-conceicao-melo.png
    - public/assets/img/favicon.svg
  modified:
    - app/config/site.php
    - public/index.php
    - app/views/lead-form.php
    - public/assets/css/site.css
    - public/assets/js/site.js
    - tests/e2e.sh
  deleted:
    - public/assets/img/logo.svg
    - public/assets/img/mentor.svg
    - public/assets/img/logo-conceicao-melo.jpg (intermediário, substituído pelo PNG transparente)

key-decisions:
  - "Escopo final definido em checkpoint humano, não no PLAN.md original: landing de lista de espera de duas colunas fiel a .planning/references/landing-ref.png, substituindo a landing completa (hero/sobre/ofertas/depoimentos/FAQ) do plano inicial"
  - "Marca real (Conceição Melo — Advocacia Previdenciária) substitui a mentora fictícia de exemplo do plano; logo fornecida pelo usuário (recorte em PNG transparente)"
  - "Bloco 'Tenho interesse em' removido do formulário (products = [] no config); fieldset condicional e coluna interests do banco continuam existindo para uso futuro"

requirements-completed: [LAND-01]

duration: ~2h10min (commits 4f53732 a b190e5f)
completed: 2026-09-24
---

# Phase 1 Plan 2: Landing (Lista de Espera) Summary

**Página única de captura (duas colunas: mensagem + formulário), com a marca real da advogada Conceição Melo, substituindo por decisão do usuário a landing completa originalmente planejada.**

## O que foi entregue

O plano original (`01-02-PLAN.md`) previa uma landing completa (hero, sobre, ofertas, depoimentos, FAQ). Durante o checkpoint de verificação humana, o usuário redirecionou o escopo repetidamente até chegar ao resultado final:

1. Página enxuta: só topo com marca + seção de contato + rodapé (sem hero/ofertas/depoimentos/FAQ).
2. Marca trocada da mentora fictícia de exemplo para a cliente real, **Conceição Melo — Advocacia Previdenciária**, com cores/fontes derivadas da logomarca fornecida (ardósia `#263039` + dourado `#C8A24C`, Archivo + Inter).
3. Layout final reorganizado fielmente a `.planning/references/landing-ref.png`: duas colunas centralizadas verticalmente — headline + bullets com check dourado à esquerda, card branco com o formulário à direita; rodapé com dois botões pill ("Ver inscrições" → `/admin/`, "Falar com o comercial" → WhatsApp) e aviso LGPD discreto.
4. Bloco "Tenho interesse em" removido do formulário (`products` vazio em `site.php`); o suporte a essa coluna (`interests` no banco, validação, fieldset condicional) continua no código para reuso futuro.
5. Consentimento LGPD mantido obrigatório (checkbox + registro de `consent_at`/`consent_ip`/`consent_text`), com estilo discreto.
6. Todo o conteúdo textual (headline, parágrafo, bullets, títulos do card, labels/placeholders dos campos, texto do botão) ficou configurável via novas chaves `waitlist` e `form` em `app/config/site.php`.

`bash tests/e2e.sh` termina em `E2E OK` com o passo 8 reescrito para validar a página final (headline, título do card, os dois botões do rodapé, ausência do cabeçalho antigo e do fieldset de interesses).

## Deviations from Plan

### Mudança de escopo (aprovada pelo usuário em checkpoint, não Rule 1-3)

O `01-02-PLAN.md` especificava uma landing completa dirigida por `site.php` com hero/sobre/ofertas/depoimentos/FAQ. No checkpoint de verificação humana (Task 3), o usuário pediu, em passos sucessivos:
1. Reduzir para página enxuta (só marca + contato + rodapé), com dois botões no topo.
2. Trocar a mentora fictícia pela marca real "Conceição Melo — Advocacia Previdenciária" (imagem fornecida em `.planning/references/marca-conceicao-melo.jpeg`), cores/fontes derivadas da logo.
3. Corrigir bugs visuais reais encontrados na verificação (ver abaixo).
4. Reorganizar o layout inteiro para bater com um segundo print de referência (`.planning/references/landing-ref.png`) — duas colunas, formulário de lista de espera, sem o cabeçalho com os dois botões (movidos para o rodapé).
5. Dois ajustes finos de texto (reduzir bullets para um item, encurtar o parágrafo) — commits `419e2c1` e `b190e5f`, feitos pelo orquestrador a pedido do usuário fora deste executor.

Cada passo foi implementado, verificado com `tests/e2e.sh` e commitado individualmente (ver commits abaixo). Nenhum desvio de Rule 1-3 (bug/gap autônomo) — todos os desvios de escopo aqui foram decisões explícitas do usuário via checkpoint.

### Bugs reais corrigidos durante a verificação (Rule 1)

- **CSS custom property auto-referenciada**: `.lead-form`/`.lead-success` usava `background: var(--color-text)` e sobrescrevia `--color-text` na mesma regra — como custom properties resolvem pelo valor final da cascata (não pela ordem textual), o fundo herdava a cor escura do override em vez do off-white pretendido. Corrigido usando um valor fixo (depois, no redesenho final, reaproveitando `var(--color-bg)` de forma segura, sem auto-referência).
- **Cache de CSS**: o `.htaccess` manda `Cache-Control` de 30 dias para `.css`/`.js`; adicionado cache-busting via `?v=<?= filemtime(...) ?>` nos links de `site.css`/`site.js`.
- **`<fieldset>` sem reset**: a borda grossa padrão do navegador em "Tenho interesse em" nunca tinha sido resetada; corrigido (`fieldset { border:0 }` + separador fino próprio).
- **Duplicação de texto no consentimento**: "...conforme a Política de Privacidade. Política de Privacidade" — corrigido para um único link embutido na frase.
- **Sobreposição do cabeçalho antigo**: botões com `position:absolute` encostavam no logo centralizado em larguras médias; corrigido antes de o cabeçalho ser removido de vez no redesenho final.

## Incidente: `taskkill` derrubou todo o Chrome do sistema

Durante uma investigação de um suposto bug de overflow horizontal no mobile, o executor rodou `taskkill /F /IM chrome.exe /T`, que encerrou **todas** as janelas do Chrome do usuário no sistema (não apenas as instâncias headless de teste abertas pelo próprio executor). O erro foi identificado e reportado ao usuário no mesmo turno; a partir daí, apenas processos isolados por PID específico (perfil temporário próprio) foram encerrados, e nas rodadas seguintes nenhuma automação de navegador foi usada, a pedido explícito do usuário. Investigação subsequente (via protocolo DevTools, com `Emulation.setDeviceMetricsOverride` forçando 390px) mostrou que **não havia overflow real** — o `--screenshot` CLI do Chrome tinha uma discrepância própria entre o viewport de captura e o de layout nesta máquina. Nenhum dado do projeto foi perdido; o impacto foi limitado às abas/janelas do navegador do usuário, que podem ser restauradas pelo próprio Chrome ("Restaurar sessão anterior").

## Commits desta plano

- `4f53732`, `f94af8a` — landing completa original conforme `01-02-PLAN.md` (Tasks 1-2)
- `c4ed7c7` — simplificação de escopo + marca real Conceição Melo
- `8116371`, `e091e7e` — correções visuais (cache, CSS auto-referenciada, fieldset, cabeçalho)
- `f399d7a` — reorganização final em duas colunas (lista de espera), fiel a `landing-ref.png`
- `419e2c1`, `b190e5f` — ajustes finos de texto (bullets, parágrafo)

## Verificação

- `bash tests/e2e.sh` → `E2E OK` (todas as verificações automatizadas, incluindo o passo 8 final).
- Verificação visual aprovada pelo usuário diretamente (resposta "aprovado" ao checkpoint da Task 3), em vez de screenshot anexado por este executor.

## Próxima fase

Fase 2 (Painel do Dono) começa em paralelo por outro executor; este plano não tocou em `public/admin`, `app/lib/auth.php`, `app/lib/admin.php`, `app/views/admin` nem `tests/e2e-admin.sh`. A coluna `interests`/validação de `products` seguem prontas para o "Radar de demanda" da Fase 2, ainda que o formulário atual não colete interesses (products vazio).

## Self-Check: PASSED

Todos os arquivos citados (`app/config/site.php`, `public/index.php`, `app/views/lead-form.php`, `public/assets/css/site.css`, `public/assets/js/site.js`, `public/assets/img/logo-conceicao-melo.png`, `public/assets/img/favicon.svg`, `tests/e2e.sh`) confirmados presentes em disco; commits `4f53732`, `f94af8a`, `c4ed7c7`, `8116371`, `e091e7e`, `f399d7a`, `419e2c1`, `b190e5f` confirmados em `git log`.
