# Roadmap: LandingPageMentores

## Overview

Sair do zero até uma cópia publicável hoje mesmo: primeiro a landing page e a captura de leads ponta a ponta (com banco de dados e script de setup), depois o painel do dono com login seguro e exportação em CSV, e por fim o acabamento visual/performance e o guia de deploy na Hostinger. Cada fase entrega algo verificável por um humano usando o site.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

- [ ] **Phase 1: Landing + Captura de Leads** - Visitante vê a landing com a marca do mentor e envia o formulário, que é gravado com segurança no MySQL; setup cria banco/tabelas e usuário do dono
- [ ] **Phase 2: Painel do Dono** - Dono faz login seguro, vê a lista de leads e exporta em CSV, com KPIs, radar de demanda e status por lead
- [ ] **Phase 3: Acabamento e Deploy** - Landing com visual autoral, performance/SEO validados, e guia passo a passo para publicar uma nova cópia na Hostinger

## Phase Details

### Phase 1: Landing + Captura de Leads
**Goal**: Visitante acessa a landing page com a marca do mentor, preenche o formulário de contato com consentimento LGPD e o lead fica gravado com segurança no MySQL; uma nova cópia é inicializada rodando um script de setup
**Mode:** mvp
**Depends on**: Nothing (first phase)
**Requirements**: LAND-01, LAND-02, LEAD-01, LEAD-02, LEAD-03, LEAD-04, LEAD-05, LEAD-06, INST-01, INST-02
**Success Criteria** (what must be TRUE):
  1. Visitante vê uma página única responsiva (mobile-first) com topo (marca + botão do comercial + botão do painel do dono), formulário de contato e rodapé LGPD, com marca (nome, logo, cores, fontes, textos, WhatsApp) vinda de um único arquivo de configuração
  2. Visitante preenche nome, telefone, e-mail e mensagem (validados no cliente e no servidor), marca o consentimento LGPD e vê uma confirmação clara após o envio; o registro é gravado no MySQL com data/hora, IP e o texto da política aceita
  3. Após o envio, o visitante vê um botão que abre o WhatsApp do mentor com mensagem pré-preenchida
  4. Envios de spam/bots são bloqueados por honeypot, token CSRF e limite de envios por IP; o visitante pode marcar opcionalmente produtos de interesse (lista do config), gravados como demandas
  5. Rodar o script de setup em uma cópia nova cria as tabelas e o usuário do dono, e as credenciais do banco ficam fora do acesso público (ou protegidas por .htaccess)
**Plans**: 2 plans
Plans:
- [x] 01-01-PLAN.md — Docker + e2e, camada compartilhada (bootstrap/db/sessão/segurança), schema, setup.php e captura de lead ponta a ponta (API + formulário mínimo + WhatsApp)
- [ ] 01-02-PLAN.md — Landing completa dirigida por site.php (seções, CSS editorial, JS de envio/validação) + verificação humana
**UI hint**: yes

### Phase 2: Painel do Dono
**Goal**: Dono da mentoria acessa o painel com login seguro, visualiza todos os leads captados e exporta o conjunto completo em CSV
**Mode:** mvp
**Depends on**: Phase 1
**Requirements**: PAIN-01, PAIN-02, PAIN-03, PAIN-04, PAIN-05, PAIN-06, PAIN-07, PAIN-08, PAIN-09
**Success Criteria** (what must be TRUE):
  1. Dono faz login com e-mail e senha (hash com password_hash, sessão segura, proteção contra força bruta)
  2. Dono vê a lista de leads mais recentes primeiro, com busca e todos os campos captados
  3. Dono exporta todos os leads em CSV UTF-8 com BOM que abre corretamente no Excel
  4. Dono faz logout e as páginas do painel ficam inacessíveis sem sessão válida
  5. Painel reproduz a referência `.planning/references/painel-admin-ref.png`: KPIs (total, 7 dias, hoje), Radar de demanda, Fila de trabalho com status editável (novo/contatado/fechado) e botão "Copiar CSV"
**Plans**: 2 plans
Plans:
- [ ] 02-01-PLAN.md — Login seguro, logout, KPIs, Radar de demanda e Fila de trabalho com busca no visual da referência (+ tests/e2e-admin.sh)
- [ ] 02-02-PLAN.md — CSV (baixar/copiar, BOM, anti-fórmula), status editável e verificação visual
**UI hint**: yes

### Phase 3: Acabamento e Deploy
**Goal**: Landing recebe acabamento visual autoral com performance e SEO validados, e existe um guia passo a passo para publicar uma nova cópia na Hostinger
**Mode:** mvp
**Depends on**: Phase 1, Phase 2
**Requirements**: LAND-03, INST-03
**Success Criteria** (what must be TRUE):
  1. Landing tem visual moderno e autoral (tipografia forte, sem gradientes roxos genéricos, emojis ou cards clonados)
  2. Landing atinge Lighthouse ≥ 90 no mobile e tem SEO básico (title, meta description, Open Graph)
  3. Existe um guia passo a passo cobrindo criar o banco, subir os arquivos, rodar o setup e configurar a marca para publicar uma nova cópia na Hostinger
**Plans**: TBD
**UI hint**: yes

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Landing + Captura de Leads | 1/2 | In Progress|  |
| 2. Painel do Dono | 0/TBD | Not started | - |
| 3. Acabamento e Deploy | 0/TBD | Not started | - |
