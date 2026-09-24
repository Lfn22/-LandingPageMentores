---
phase: 03-acabamento-e-deploy
fixed_at: 2026-09-24T17:40:00Z
review_path: .planning/phases/03-acabamento-e-deploy/03-REVIEW.md
iteration: 1
findings_in_scope: 13
fixed: 13
skipped: 0
status: all_fixed
---

# Fase 03: Correções do Code Review

Pulados por decisão do usuário (fora do escopo): IN-05, IN-06, IN-07.

| ID | Status | Commit | O que mudou |
|----|--------|--------|-------------|
| CR-01 | fixed | 24772e2 | `fputcsv` com escape `''` nas duas chamadas (`public/admin/export.php`) |
| WR-01 | fixed | 24b7840 | `lead_respond` usa `../index.php#contato` como destino padrão e grava flash de erro; GET sem JSON devolve 405 em texto puro |
| WR-02 | fixed | 1da005d | `errors['form']` exibido (escapado) em `app/views/lead-form.php` |
| WR-03 | fixed: requires human verification | 7bd2525 | depois do timeout: `auth_logout()` + `session_start_secure()` + `session_regenerate_id(true)` |
| WR-04 | fixed | 8097384 | chave opcional `trusted_proxy_header` (padrão `''`); `client_ip()` só aceita o valor se for um IP válido |
| WR-05 | fixed | 2242701 | `.htaccess` redireciona para HTTPS (exceto localhost/127.0.0.1); HSTS enviado via PHP quando há HTTPS; o cookie `secure` já estava condicionado ao HTTPS |
| WR-06 | fixed | 6b20c1c | `session.gc_maxlifetime = 7200` |
| WR-07 | fixed | 93f4c92 | `Cache-Control: no-store, private` em `index.php` e `api/lead.php` |
| WR-08 | fixed | eb38ade | política: dados guardados "até a solicitação de exclusão"; pedidos atendidos manualmente pelo e-mail de contato |
| IN-01 | fixed | abc199f | `lead_validate` troca entradas que não são string por `''`; a view também não quebra com `old` em array |
| IN-02 | fixed | 6228c7a | `hostinger-app.zip` traz a pasta `app/` na raiz; `DEPLOY.md` explica como extrair |
| IN-03 | fixed | 33e42b6 | setup avisa "Usuário já existente" e mostra mensagem amigável se o schema falhar |
| IN-04 | fixed | 55f836e | e-mail de fallback vazio; a linha de contato fica oculta quando não está configurado |

**Verificação:** rodei `php -l` em todos os arquivos PHP alterados e fiz estes testes com curl:
- `/` responde 200 com `Cache-Control: no-store, private`;
- `export.php` sem login redireciona (302) para `/admin/`;
- POST no lead com CSRF inválido e sem JSON responde 303 para `../index.php#contato` e a mensagem aparece no formulário;
- GET em `/api/lead.php` responde 405;
- com Host não-local, a resposta é 301 para HTTPS.

`dist/` foi regenerado: `hostinger-app.zip` com 14547 bytes e `hostinger-public_html.zip` com 364715 bytes.

**Falta verificar manualmente:** o WR-03, ou seja, se o login funciona na primeira tentativa depois de 2h de inatividade.
