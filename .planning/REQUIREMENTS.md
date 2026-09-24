# Requirements: LandingPageMentores

**Defined:** 2026-09-24
**Core Value:** O lead preenche nome, telefone, e-mail e mensagem, e esse registro fica gravado com segurança no banco e visível/exportável em CSV pelo dono da mentoria.

## v1 Requirements

### Landing Page

- [ ] **LAND-01**: Visitante vê uma landing page responsiva (mobile-first) com hero, sobre o mentor, produtos/ofertas, depoimentos, FAQ e formulário de contato
- [ ] **LAND-02**: Marca do mentor (nome, logo, cores primária/secundária, fontes, textos, produtos, depoimentos, WhatsApp) vem de um único arquivo de configuração, sem editar HTML
- [ ] **LAND-03**: Visual moderno e autoral (tipografia forte, sem gradientes roxos genéricos/emojis/cards clonados), com boa performance (Lighthouse ≥ 90 mobile) e SEO básico (title, meta description, Open Graph)

### Captura de Leads

- [ ] **LEAD-01**: Lead precisa preencher nome, telefone, e-mail e mensagem (todos obrigatórios, validados no cliente e no servidor)
- [ ] **LEAD-02**: Lead precisa marcar o consentimento LGPD; o sistema registra data/hora, IP e texto da política aceita
- [ ] **LEAD-03**: Lead enviado é gravado no MySQL e o visitante vê uma confirmação clara
- [ ] **LEAD-04**: Após o envio, o visitante vê um botão que abre o WhatsApp do mentor com mensagem pré-preenchida
- [ ] **LEAD-05**: Envios de spam/bots são bloqueados (honeypot, token CSRF e limite de envios por IP)

### Painel do Dono

- [ ] **PAIN-01**: Dono da mentoria faz login com e-mail e senha (hash com password_hash, sessão segura, proteção contra força bruta)
- [ ] **PAIN-02**: Dono vê a lista de leads (mais recentes primeiro) com busca e todos os campos captados
- [ ] **PAIN-03**: Dono exporta todos os leads em CSV (UTF-8 com BOM, abre corretamente no Excel)
- [ ] **PAIN-04**: Dono faz logout; páginas do painel são inacessíveis sem sessão válida

### Instalação e Deploy

- [ ] **INST-01**: Script de setup cria as tabelas e o usuário do dono em uma nova cópia (e se desativa/bloqueia depois de usado)
- [ ] **INST-02**: Credenciais do banco ficam em arquivo de config fora do acesso público (ou protegido por .htaccess)
- [ ] **INST-03**: Guia passo a passo para publicar uma nova cópia na Hostinger (criar banco, subir arquivos, rodar setup, configurar marca)

## v2 Requirements

### Painel

- **PAIN-05**: Dono edita marca/conteúdo da landing pelo painel
- **PAIN-06**: Dono marca status do lead (novo, contatado, fechado)
- **PAIN-07**: Notificação por e-mail a cada novo lead

### Tracking

- **TRAK-01**: Captura de UTMs, referrer e página de entrada
- **TRAK-02**: Pixel Meta / GA4 configurável

## Out of Scope

| Feature | Reason |
|---------|--------|
| Multi-tenant (vários mentores em uma instalação) | Decisão: uma cópia por mentor |
| Frameworks JS / build step | HTML estático + PHP puro, compatível com hospedagem compartilhada |
| Checkout/pagamentos | Produtos são divulgados; venda ocorre fora (links/WhatsApp) |
| Múltiplos usuários por painel | Um dono por cópia é suficiente na v1 |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|

**Coverage:**
- v1 requirements: 15 total
- Mapped to phases: 0
- Unmapped: 15 ⚠️

---
*Requirements defined: 2026-09-24*
*Last updated: 2026-09-24 after initial definition*
