# Phase 1: Landing + Captura de Leads - Context

**Gathered:** 2026-09-24
**Status:** Ready for planning
**Mode:** Auto (discuss skipped for same-day deadline; decisions below set by orchestrator from user answers)

<domain>
## Phase Boundary

Visitante acessa a landing page com a marca do mentor, preenche o formulário (nome, telefone, e-mail, mensagem obrigatórios + interesses opcionais + consentimento LGPD) e o lead fica gravado com segurança no MySQL; após o envio, vê confirmação e botão de WhatsApp. Uma nova cópia é inicializada rodando o script de setup (cria tabelas + usuário do dono). O painel do dono (login, lista, CSV) é da Fase 2 — mas o schema do banco e a camada compartilhada (bootstrap/db/segurança) criados aqui DEVEM já atender a Fase 2 (tabela users, coluna status nos leads).

</domain>

<decisions>
## Implementation Decisions

### Stack e hospedagem
- Hostinger shared hosting: PHP 8.1+ (testar em 8.2) + MySQL/MariaDB via PDO (pdo_mysql). Sem Composer, sem Node, sem build step. Zero dependências externas de runtime além de Google Fonts.
- "HTML estático": a landing é HTML/CSS/JS puro servido por `index.php`, que apenas injeta os dados do arquivo de config (render server-side com escaping `htmlspecialchars`). JS vanilla só para máscara de telefone, validação e envio via fetch (com fallback de POST normal sem JS).
- Uma cópia do projeto por mentor.

### Estrutura de pastas (proposta — planner pode refinar)
```
public/                 → conteúdo de public_html na Hostinger
  index.php             → landing
  api/lead.php          → recebe POST do formulário (JSON resposta)
  setup.php             → instalação única (cria tabelas + dono); bloqueia após uso (arquivo lock em app/storage)
  assets/css/, assets/js/, assets/img/ (logo, foto do mentor)
  .htaccess             → headers de segurança, bloqueio de dotfiles, cache de assets
app/                    → FORA do webroot (na Hostinger: ao lado de public_html). Se não for possível, contém .htaccess "Require all denied"
  bootstrap.php         → carrega config, sessão, helpers
  config/site.php       → marca e conteúdo do mentor (editável)
  config/config.php     → credenciais do banco, app secret (NÃO versionado; existe config.example.php)
  lib/db.php, lib/security.php (csrf, rate limit, escape), lib/leads.php
  storage/              → setup.lock
database/schema.sql     → schema de referência
docker-compose.yml + docker/Dockerfile → ambiente local de teste (php:8.2-apache com pdo_mysql + mariadb:10.11)
```
- `public/` deve encontrar `app/` via caminho relativo configurável (constante APP_PATH com default `__DIR__ . '/../app'`).

### Configuração da marca (LAND-02)
- `app/config/site.php` retorna um array PHP com: nome do mentor, nome da mentoria, headline, subheadline, sobre (texto + foto), logo, cores (primary, secondary/accent, background, text), fontes (display + body do Google Fonts), lista de produtos/ofertas (título, descrição, preço opcional, link opcional, id/slug), depoimentos (nome, texto, foto opcional), FAQ, WhatsApp (número E.164 + mensagem padrão), texto de consentimento LGPD + link da política, SEO (title, description, og image), redes sociais.
- Cores aplicadas via CSS custom properties no `:root` geradas a partir do config. Validar hex antes de imprimir.
- Seções vazias no config simplesmente não são renderizadas.
- Preencher com um mentor de exemplo realista (em português, fictício, sem "lorem ipsum").

### Formulário e captura (LEAD-01..06)
- Campos obrigatórios: nome, telefone (máscara BR, aceita 10–11 dígitos; armazenar só dígitos), e-mail (filter_var), mensagem (texto livre, máx ~2000 chars). Validação no cliente (HTML5 + JS) e no servidor (fonte da verdade).
- Opcional "Tenho interesse em": checkboxes com os produtos do config; gravar os ids/títulos escolhidos (JSON em coluna `interests`) — alimenta "Demandas"/"Radar de demanda" da Fase 2. Validar no servidor contra a lista do config.
- Consentimento LGPD obrigatório: checkbox; gravar `consent_at` (DATETIME), `consent_ip`, `consent_text` (texto exato exibido).
- Anti-spam: honeypot (campo oculto), token CSRF (sessão), tempo mínimo de preenchimento (~3s), rate limit por IP (ex.: 5 envios / 10 min) em tabela `rate_limits`.
- Sucesso: substitui o formulário por mensagem de confirmação + botão "Falar no WhatsApp" (wa.me/{numero}?text= mensagem pré-preenchida com o nome do lead, urlencoded). Erros exibidos por campo, em português.

### Banco (já pensando na Fase 2)
- utf8mb4 / utf8mb4_unicode_ci, InnoDB.
- `leads`: id, name, phone, email, message, interests (JSON/TEXT), status ENUM('novo','contatado','fechado') DEFAULT 'novo', consent_at, consent_ip, consent_text, ip, user_agent, created_at (índices em created_at, email, status).
- `users`: id, email UNIQUE, password_hash, created_at, last_login_at.
- `rate_limits`: id, ip, action, created_at (índice ip+action+created_at) — reutilizado pelo login na Fase 2.
- Tudo com prepared statements. IP via REMOTE_ADDR (não confiar em X-Forwarded-For por padrão).

### Setup (INST-01/02)
- `setup.php`: formulário pede e-mail + senha (mín. 10 chars) do dono; testa conexão; cria tabelas (IF NOT EXISTS); cria o usuário com password_hash (PASSWORD_DEFAULT); grava `app/storage/setup.lock`; se o lock existe responde 403/"já instalado". Protegido por CSRF.
- Credenciais do banco em `app/config/config.php` (gitignored); `config.example.php` versionado. `.htaccess` negando acesso a `app/` caso fique dentro do webroot.

### Visual da landing
- Moderno e autoral, "sem cara de IA": nada de gradiente roxo/azul genérico, emojis, ícones-clichê em círculos, cards clonados em grid de 3. Preferir layout editorial: tipografia display grande, bom uso de espaço, foto do mentor em destaque, seções com ritmo variado, cores do mentor como acento sólido. Mobile-first. Acessível (contraste, labels, foco visível).
- Aviso LGPD discreto no rodapé (como na referência: "Seus dados são usados apenas para contato…").
- Acabamento fino/Lighthouse/SEO completo é Fase 3, mas a Fase 1 já entrega uma página bonita e usável.

### Claude's Discretion
- Nomes exatos de arquivos/funções, divisão em planos, detalhes de CSS.

</decisions>

<code_context>
## Existing Code Insights

Greenfield — repositório vazio além de `.planning/` e `CLAUDE.md`.
Ambiente local: Windows, sem PHP/MySQL instalados; Docker Desktop disponível (imagens php:8.2-apache e mariadb:10.11) → usar docker compose para verificar (subir, rodar setup, enviar lead via curl, consultar tabela).

</code_context>

<specifics>
## Specific Ideas

- Referência visual do painel (Fase 2): `.planning/references/painel-admin-ref.png` + `.planning/references/README.md` — a landing deve conversar com essa estética sóbria (grotesca, bordas finas, cantos arredondados), porém com as cores do mentor.
- Idioma: português do Brasil em toda a UI.

</specifics>

<deferred>
## Deferred Ideas

- Painel/login/CSV/KPIs/status → Fase 2
- Lighthouse ≥ 90, SEO/OG completos, guia de deploy Hostinger → Fase 3
- UTMs, Pixel/GA4, edição de conteúdo pelo painel, notificação por e-mail → v2

</deferred>
