<!-- GSD:project-start source:PROJECT.md -->
## Project

**LandingPageMentores**

Um template genérico de landing page para os mentores da nossa liga de mentores. Cada mentor recebe **uma cópia própria** (pasta/domínio e banco próprios na Hostinger), com a marca e as cores dele, para vender ou divulgar os próprios produtos e captar leads. O dono da mentoria acessa um painel protegido por login, onde vê os leads e exporta um CSV.

**Core Value:** O lead preenche nome, telefone, e-mail e mensagem, e esse registro fica gravado com segurança no banco e visível/exportável em CSV pelo dono da mentoria.

### Constraints

- **Timeline**: precisa ir ao ar hoje (2026-09-24) — escopo mínimo, poucas fases
- **Tech stack**: HTML/CSS/JS estático + endpoints PHP (PDO, prepared statements) + MySQL — é o que a Hostinger oferece sem serviço externo
- **Security**: password_hash/password_verify, sessões seguras, CSRF no formulário e no login, proteção contra spam (honeypot + rate limit simples), config e credenciais fora do webroot quando possível
- **Compliance**: LGPD — consentimento explícito registrado com data/hora e IP
- **Portability**: cada cópia deve ser configurada só editando um arquivo de config + rodando o setup
<!-- GSD:project-end -->

<!-- GSD:stack-start source:STACK.md -->
## Technology Stack

Technology stack not yet documented. Will populate after codebase mapping or first phase.
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

Conventions not yet established. Will populate as patterns emerge during development.
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

Architecture not yet mapped. Follow existing patterns found in the codebase.
<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->
## Project Skills

No project skills found. Add skills to any of: `.claude/skills/`, `.agents/skills/`, `.cursor/skills/`, `.github/skills/`, or `.codex/skills/` with a `SKILL.md` index file.
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->



<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
