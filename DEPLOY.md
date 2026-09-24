# Guia de Deploy — Hostinger (hPanel)

Passo a passo para publicar uma cópia deste projeto em um domínio na Hostinger.

## 1. Criar o banco de dados MySQL

No hPanel, vá em **Bancos de dados → MySQL** e crie um banco novo. Anote:

- Nome do banco
- Usuário
- Senha
- Host (geralmente `localhost`)

## 2. Enviar os arquivos

- O conteúdo da pasta `public/` deste repositório vai para `public_html/` (o docroot do domínio).
- A pasta `app/` vai **ao lado** de `public_html/`, em `domains/SEU-DOMINIO/app/` (fora do webroot —
  é a camada compartilhada com configuração, banco e views).

**Se o plano não permitir criar pastas fora de `public_html/`:** coloque `app/` dentro de
`public_html/` (ex.: `public_html/app/`) e:

1. Edite `public/_bootstrap.php` e troque a definição de `APP_PATH` para `__DIR__ . '/app'`.
2. Garanta que o arquivo `app/.htaccess` (já existente no repositório, com `Require all denied`) foi
   enviado junto — ele bloqueia qualquer acesso direto a `public_html/app/` pelo navegador.

## 3. Configurar `app/config/config.php`

Copie `app/config/config.example.php` para `app/config/config.php` (não é versionado) e preencha:

- `db.host`, `db.port`, `db.name`, `db.user`, `db.pass` — dados do passo 1
- `app_secret` — 64 caracteres aleatórios, ex.: gerar com `php -r "echo bin2hex(random_bytes(32));"`
- `setup_token` — no mínimo 16 caracteres, usado só durante a instalação

## 4. Ativar SSL

No hPanel, ative o SSL gratuito (Let's Encrypt) para o domínio e force o redirecionamento HTTPS.

## 5. Rodar o setup

Abra `https://SEU-DOMINIO/setup.php`, informe:

- O `setup_token` definido no passo 3
- O e-mail do dono
- Uma senha forte (recomendado 14+ caracteres)

Isso cria as tabelas e o usuário do dono, e trava o próprio `setup.php` (grava
`app/storage/setup.lock`). Por segurança, apague `public/setup.php` do servidor depois de instalar.

## 6. Testar

- Envie um lead de teste pela landing e confirme que ele aparece em `/admin/`.
- Acesse `/admin/` com o e-mail/senha criados no passo 5 e confirme login, KPIs, Radar de demanda,
  Fila de trabalho e exportação/cópia de CSV.

## 7. Nova cópia para outro mentor

Cada mentor tem sua própria cópia (pasta/domínio e banco próprios). Para uma cópia nova:

1. Repita os passos 1–6 com um banco novo.
2. Edite `app/config/site.php` com a marca do novo mentor: nome, cores, fontes, textos, WhatsApp,
   `commercial.url` e o logo em `public/assets/img/` (atualize a chave `brand.logo`).

## Troubleshooting

- **Erro 500**: confira `app/config/config.php` (credenciais do banco corretas) e a versão do PHP no
  hPanel (precisa ser ≥ 8.1).
- **Login bloqueado após várias tentativas**: aguarde 15 minutos (limite de tentativas por IP).
- **"Instalação já concluída" ao abrir `/setup.php`**: o setup já rodou nesta cópia — o arquivo
  `app/storage/setup.lock` existe. Apague-o manualmente só se quiser reinstalar do zero (isso não
  recria o banco sozinho).
- **`app/storage` precisa ter permissão de escrita** (o setup grava `setup.lock` ali; sem isso a
  instalação falha no último passo).

## Nota sobre a Política de Privacidade

O texto de `public/privacidade.php` é um modelo alinhado à LGPD para o fluxo deste site (formulário de
contato/lista de espera). Antes de publicar, revise o texto com sua própria orientação jurídica.
