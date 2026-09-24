# LandingPageMentores

## What This Is

Um template genérico de landing page para os mentores da nossa liga de mentores. Cada mentor recebe **uma cópia própria** (pasta/domínio e banco próprios na Hostinger), com a marca e as cores dele, para vender ou divulgar os próprios produtos e captar leads. O dono da mentoria acessa um painel protegido por login, onde vê os leads e exporta um CSV.

## Core Value

O lead preenche nome, telefone, e-mail e mensagem, e esse registro fica gravado com segurança no banco e visível/exportável em CSV pelo dono da mentoria.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Landing page simples, moderna e sem "cara de IA", com a marca/cores/conteúdo do mentor definidos em um arquivo de configuração
- [ ] Formulário com nome, telefone, e-mail e texto livre obrigatórios + consentimento LGPD obrigatório
- [ ] Leads gravados em MySQL (com data/hora, IP e registro do consentimento)
- [ ] Após o envio, botão/redirecionamento para o WhatsApp do mentor
- [ ] Painel com login seguro (sessão, senha com hash) para o dono ver os leads
- [ ] Exportação dos leads em CSV pelo painel
- [ ] Instalação simples por cópia (script de setup cria tabelas e usuário do dono)
- [ ] Deploy na Hostinger (hospedagem compartilhada: PHP + MySQL)

### Out of Scope

- Multi-tenant (uma instalação para vários mentores) — decisão: uma cópia por mentor
- Pixel Meta / GA4 — não solicitado para v1
- Captura de UTMs/origem — não solicitado para v1
- Editor visual de conteúdo no painel — conteúdo via arquivo de config para ir ao ar hoje
- Frameworks JS / build step — HTML estático + PHP puro, compatível com hospedagem compartilhada

## Context

- A liga de mentores tem vários mentores; cada um terá sua instância duplicada do template.
- Hospedagem: Hostinger (shared hosting) — PHP 8.x + MySQL/MariaDB disponíveis, sem Node.
- Visual: referências modernas de landing pages de mentoria/criadores; evitar estética genérica de IA (gradientes roxos, emojis, cards idênticos).
- Idioma da página e do painel: português do Brasil.

## Constraints

- **Timeline**: precisa ir ao ar hoje (2026-09-24) — escopo mínimo, poucas fases
- **Tech stack**: HTML/CSS/JS estático + endpoints PHP (PDO, prepared statements) + MySQL — é o que a Hostinger oferece sem serviço externo
- **Security**: password_hash/password_verify, sessões seguras, CSRF no formulário e no login, proteção contra spam (honeypot + rate limit simples), config e credenciais fora do webroot quando possível
- **Compliance**: LGPD — consentimento explícito registrado com data/hora e IP
- **Portability**: cada cópia deve ser configurada só editando um arquivo de config + rodando o setup

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Uma cópia por mentor | Escolha do usuário; isola dados e marca de cada mentor | — Pending |
| PHP + MySQL da Hostinger | Já incluso na hospedagem, sem dependência externa | — Pending |
| Conteúdo/marca via arquivo de config | Permite ir ao ar hoje; editor no painel fica para v2 | — Pending |
| Capturar consentimento LGPD + CTA WhatsApp | Escolha do usuário | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd:transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd:complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-09-24 after initialization*
