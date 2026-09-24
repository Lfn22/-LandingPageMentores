---
phase: 03-acabamento-e-deploy
reviewed: 2026-09-24T17:35:00Z
depth: standard
files_reviewed: 32
files_reviewed_list:
  - app/.htaccess
  - app/bootstrap.php
  - app/config/config.example.php
  - app/config/site.php
  - app/database/schema.sql
  - app/lib/admin.php
  - app/lib/auth.php
  - app/lib/db.php
  - app/lib/leads.php
  - app/lib/security.php
  - app/lib/session.php
  - app/views/admin/login.php
  - app/views/admin/panel.php
  - app/views/lead-form.php
  - public/.htaccess
  - public/_bootstrap.php
  - public/admin/export.php
  - public/admin/index.php
  - public/admin/logout.php
  - public/admin/status.php
  - public/api/lead.php
  - public/assets/js/admin.js
  - public/assets/js/site.js
  - public/index.php
  - public/privacidade.php
  - public/robots.txt
  - public/setup.php
  - scripts/build-deploy.sh
  - docker-compose.yml
  - docker/Dockerfile
  - .gitignore
  - CLAUDE.md
findings:
  critical: 1
  warning: 8
  info: 7
  total: 16
status: issues_found
---

# Fase 03: Relatório de Code Review (pré-deploy, v1 completo)

**Revisado em:** 2026-09-24T17:35:00Z
**Profundidade:** standard
**Arquivos revisados:** 32
**Status:** issues_found

## Resumo

A base está boa no geral. Todas as consultas usam prepared statements (inclusive o `LIMIT` com cast `(int)` e o `LIKE` com `ESCAPE`). Toda saída HTML passa por `e()`. O JS usa só `textContent`. CSRF existe em login, logout, status, lead e setup. `session_regenerate_id(true)` roda no login e `use_strict_mode` está ligado. O setup está protegido por token de 16+ caracteres, lock file e pela checagem `userCount === 0`. A CSP usa nonce. Os zips de `dist/` usam `/` como separador, não contêm `config.php` nem `setup.lock`, e incluem `.htaccess` e `storage/.gitkeep`.

Problemas encontrados:

- **1 BLOCKER:** dá para burlar a proteção contra fórmulas no CSV. Confirmei rodando `fputcsv` no PHP 8.2.
- **Redirect vazio:** vários caminhos sem JS mandam o navegador para um redirect vazio, o que gera loop de redirecionamento.
- **Login após inatividade:** depois do timeout de 2h, a primeira tentativa de login sempre falha.
- **IP atrás de CDN/proxy:** se houver CDN ou proxy na frente, o rate limit vira global e o IP do consentimento LGPD fica errado.
- **HTTPS:** o HTTPS não é forçado.

## Critical Issues

### CR-01: Proteção contra fórmulas no CSV é burlada pelo escape `\` do `fputcsv`

**Arquivo:** `public/admin/export.php:16` e `public/admin/export.php:44`
**Problema:** `fputcsv(..., ';', '"', '\\', ...)` usa `\` como caractere de escape. Com isso, o PHP **não dobra** uma aspa precedida de `\`. Um lead mal-intencionado envia a mensagem `x\";=HYPERLINK("http://evil","a")`. Como ela começa com `x`, `csv_safe()` não coloca o prefixo `'`. O PHP 8.2 gera esta saída (testado):

```
1;"x\";=HYPERLINK(""http://evil"",""a"")";z
```

Excel e LibreOffice seguem a RFC 4180, onde `\` não é escape. Eles fecham o campo em `"x\"`, e o trecho seguinte vira uma **nova célula que começa com `=`**: é injeção de fórmula na planilha do dono da mentoria. Isso também desalinha as colunas. O mesmo vale para o "Copiar CSV", que usa o mesmo endpoint.
**Correção:** desativar o escape proprietário do PHP (o padrão é compatível com a RFC a partir do PHP 7.4):

```php
fputcsv($out, [...], ';', '"', '', "\r\n");
```

Aplicar nas duas chamadas (cabeçalho e linhas).

## Warnings

### WR-01: `Location` vazio causa loop de redirecionamento em `/api/lead.php`

**Arquivo:** `public/api/lead.php:22`, `:27`, `:44`, `:85`
**Problema:** `lead_respond()` recebe `$redirectTo = null` nos casos 405 (GET), CSRF inválido e erro 500 no insert. Sem `Accept: application/json`, a resposta vira `303` com `Location:` vazio (confirmado no PHP 8.2). O navegador resolve a URL vazia para a própria `/api/lead.php` e faz GET, que cai de novo no 405 e redireciona de novo: `ERR_TOO_MANY_REDIRECTS`.
Isso acontece com qualquer pessoa que abra `/api/lead.php`. Acontece também com o lead sem JS cuja sessão expirou (CSRF) e quando o banco falha. Nesses casos o lead é perdido sem nenhuma mensagem.
**Correção:** usar um destino padrão e mandar uma mensagem via flash:

```php
function lead_respond(bool $wantsJson, int $status, array $payload, string $redirectTo = '../index.php#contato', ?array $flash = null): void
// nos casos csrf/500 sem flash explícito:
$flash ??= ['status' => 'error', 'errors' => ['form' => $payload['message'] ?? 'Tente novamente.']];
```

### WR-02: Erros gerais do formulário (`errors['form']`) nunca são exibidos

**Arquivo:** `app/views/lead-form.php:24` (em conjunto com `public/api/lead.php:39` e `:65`)
**Problema:** sem JS, as mensagens de rate limit ("Muitos envios...") e de envio rápido demais ("Aguarde alguns segundos...") ficam em `errors['form']`, mas a view só renderiza erros por campo. O `<p data-form-status>` está sempre vazio. O usuário volta ao formulário preenchido sem saber por que não enviou.
**Correção:**

```php
<p class="form-status" data-form-status role="status" aria-live="polite"><?= e($errors['form'] ?? '') ?></p>
```

### WR-03: Depois do timeout de inatividade, a primeira tentativa de login sempre falha com "Sessão expirada"

**Arquivo:** `app/lib/auth.php:47-49` e `:75-93`, e `public/admin/index.php:38-41`
**Problema:** quando passa de `AUTH_IDLE_SECONDS`, `auth_user()` chama `auth_logout()`, que roda `session_destroy()` e apaga o cookie. Logo em seguida `index.php` chama `csrf_token()`, que grava em um `$_SESSION` que **não está mais ativo** e por isso não é salvo. O formulário de login sai com um token que não existe em nenhuma sessão, e o próximo POST falha com 403. O mesmo acontece se o dono abrir `/admin/` depois de 2h.
**Correção:** no timeout, limpar só a autenticação e manter a sessão viva:

```php
if (time() - (int) $_SESSION['last_seen'] > AUTH_IDLE_SECONDS) {
    unset($_SESSION['uid'], $_SESSION['last_seen']);
    session_regenerate_id(true);
    return null;
}
```

### WR-04: `client_ip()` com `REMOTE_ADDR` quebra rate limit e IP do consentimento atrás de CDN/proxy

**Arquivo:** `app/lib/security.php:43-46`
**Problema:** se a cópia ficar atrás de Cloudflare ou do CDN da Hostinger, `REMOTE_ADDR` passa a ser o IP do proxy. Todos os visitantes dividem o mesmo contador:

- no máximo 5 leads a cada 10 minutos **no site inteiro**, e o resto recebe 429;
- qualquer pessoa consegue bloquear o login do dono com 5 POSTs;
- o `consent_ip` gravado para a LGPD fica errado.

No LiteSpeed sem CDN, `REMOTE_ADDR` é o IP real. Por isso o risco depende de como cada domínio está configurado.
**Correção:** manter `REMOTE_ADDR` como padrão e aceitar um header só quando ele estiver configurado de forma explícita e vier de um proxy confiável:

```php
function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $header = app_config('trusted_proxy_header'); // ex.: 'HTTP_CF_CONNECTING_IP'
    if ($header && !empty($_SERVER[$header]) && filter_var($_SERVER[$header], FILTER_VALIDATE_IP)) {
        return $_SERVER[$header];
    }
    return $ip;
}
```

Documentar no checklist de deploy: "se ativar o CDN, configure `trusted_proxy_header`". Nunca confiar em `X-Forwarded-For` sem proxy conhecido.

### WR-05: HTTPS não é forçado; login, setup e cookie de sessão podem trafegar em HTTP

**Arquivo:** `public/.htaccess` (ausência de regra) e `app/lib/session.php:13`
**Problema:** não há redirecionamento para HTTPS nem HSTS. A flag `secure` do cookie só é ligada se `$_SERVER['HTTPS']` estiver definido. Quem acessar `http://dominio/admin/` ou `http://dominio/setup.php` envia a senha e o setup_token em texto puro, e o cookie de sessão sai sem `Secure`.
**Correção:** no `public/.htaccess`:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000" env=HTTPS
</IfModule>
```

Também vale ativar "Forçar HTTPS" no hPanel.

### WR-06: `session.gc_maxlifetime` padrão (24 min) invalida o CSRF do formulário público e o timeout de 2h do painel

**Arquivo:** `app/lib/session.php:3-16`
**Problema:** o CSRF do lead fica guardado na sessão. Com o GC padrão do PHP (1440 s), um visitante que deixa a aba aberta por mais de 24 minutos recebe 403 "Sessão expirada" ao enviar, e perde conversão. O `AUTH_IDLE_SECONDS = 7200` também nunca é alcançado, porque a sessão morre antes.
**Correção:** em `session_start_secure()`, antes de `session_start()`:

```php
ini_set('session.gc_maxlifetime', '7200');
```

Opcional: no `site.js`, em um 403 com `error === 'csrf'`, orientar o usuário a recarregar e manter os dados digitados.

### WR-07: `index.php` sem `Cache-Control` embute token CSRF e `form_ts` em HTML que pode ser cacheado

**Arquivo:** `public/index.php:5`
**Problema:** a página tem token CSRF por sessão, mas não manda nenhum header de cache. Se o CDN da Hostinger, o LiteSpeed Cache ou um proxy cachear o HTML, todos os visitantes recebem o mesmo token, que não bate com a sessão deles. Resultado: **todo envio de lead falha com 403**.
**Correção:** logo após `send_security_headers();`:

```php
header('Cache-Control: no-store, private');
```

### WR-08: Política de privacidade promete retenção máxima de 24 meses e exclusão, mas nada disso está implementado

**Arquivo:** `public/privacidade.php:132-133` e `:145-150`
**Problema:** a política diz que os dados ficam guardados "por, no máximo, 24 meses", mas nenhuma rotina apaga leads antigos, e o painel não tem como excluir um lead. Hoje o dono só atende um pedido de exclusão pelo phpMyAdmin. Prometer publicamente algo que não é cumprido é risco de LGPD.
**Correção:** mínimo simples é uma limpeza oportunista (como já é feito em `rate_limits`) no login do painel:

```php
$pdo->prepare('DELETE FROM leads WHERE created_at < ?')->execute([date('Y-m-d H:i:s', strtotime('-24 months'))]);
```

Adicionalmente, incluir um botão "Excluir" no painel (POST + CSRF). Se não der para fazer hoje, ajustar o texto da política.

## Info

### IN-01: Campos enviados como array geram "Array" no nome ou TypeError na view

**Arquivo:** `app/lib/leads.php:8`, `:20`, `:26` e `app/views/lead-form.php:31`
**Problema:** `name[]=x` vira `(string) array`, que dá `"Array"`: passa na validação e é gravado. Nos caminhos de erro, `'old' => $_POST` guarda o array no flash, e `e($old['name'])` lança TypeError (500) para esse visitante. O impacto fica restrito ao próprio atacante.
**Correção:** `is_string($input['name'] ?? null) ? trim($input['name']) : ''` para cada campo escalar.

### IN-02: Zips de deploy não têm pasta raiz

**Arquivo:** `scripts/build-deploy.sh:25` e `:32`
**Problema:** `hostinger-app.zip` extrai `bootstrap.php`, `lib/` etc. direto no diretório de destino. `_bootstrap.php` espera `../app`. Se o arquivo for extraído na raiz do domínio sem criar `app/` antes, o site dá 500.
**Correção:** zipar `app/` como pasta, com `(cd "$STAGE_DIR" && zip -rq "$zip" app)`, ou deixar explícito no checklist de deploy.

### IN-03: Setup reporta "Instalação concluída" mesmo ignorando o e-mail e a senha informados

**Arquivo:** `public/setup.php:77-81` e `:93-96`
**Problema:** se já existir usuário (por exemplo, depois de uma falha ao gravar o lock), o setup não cria nada, mas mostra sucesso. O dono pode achar que a senha nova vale. Além disso, uma exceção em `$pdo->exec` do schema sai como 500 em branco.
**Correção:** quando `$userCount > 0`, mostrar "Usuário já existe; credenciais não alteradas". Envolver o loop do schema em `try/catch` com mensagem amigável.

### IN-04: E-mail da Conceição fixo como fallback em um template genérico

**Arquivo:** `public/privacidade.php:38`
**Problema:** se outra cópia esquecer `lgpd.contact_email`, a política passa a mostrar o e-mail de outra mentora.
**Correção:** usar fallback `''` e esconder o parágrafo quando estiver vazio.

### IN-05: "Copiar CSV" usa `;`, e colar no Google Sheets não separa as colunas

**Arquivo:** `public/assets/js/admin.js:9-18`
**Problema:** o Sheets separa o que é colado por TAB, então cada linha cai inteira em uma só célula.
**Correção:** para a área de transferência, gerar TSV (pode ser um `export.php?format=tsv`). Também é aceitável só documentar o comportamento.

### IN-06: Confirmar que `Require all denied` funciona no LiteSpeed da Hostinger (modo fallback)

**Arquivo:** `app/.htaccess:1`
**Problema:** com `app/` dentro de `public_html`, toda a proteção de `config.php`, `schema.sql` e das views depende dessa linha.
**Correção:** depois do deploy, testar se `https://dominio/app/database/schema.sql` responde 403. Por segurança, somar a compatibilidade `<IfModule !mod_authz_core.c>Deny from all</IfModule>`.

### IN-07: Helpers duplicados entre `index.php` e `privacidade.php`

**Arquivo:** `public/index.php:10-37` e `public/privacidade.php:9-35`
**Problema:** `safe_url`, `asset_version` e a validação de fonte estão copiados com sufixo `_privacy`. Uma correção futura pode ser aplicada em só um dos dois.
**Correção:** mover essas funções para `app/lib/security.php` ou um `app/lib/view.php`.

---

_Revisado em: 2026-09-24T17:35:00Z_
_Revisor: Claude (gsd-code-reviewer)_
_Profundidade: standard_
