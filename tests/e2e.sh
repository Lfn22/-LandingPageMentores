#!/usr/bin/env bash
set -euo pipefail
export MSYS_NO_PATHCONV=1

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

BASE="http://localhost:${LPM_PORT:-8080}"
DOCKER_SETUP_TOKEN="docker-setup-token-0123456789"

# Caminho relativo (não "/tmp/..."): com MSYS_NO_PATHCONV=1 o curl nativo do
# Windows não resolve caminhos POSIX absolutos passados para -c/-b/-D/-o.
TMPDIR_E2E="tests/.e2e-tmp"
rm -rf "$TMPDIR_E2E"
mkdir -p "$TMPDIR_E2E"
trap 'rm -rf "$TMPDIR_E2E"' EXIT
HDR="$TMPDIR_E2E/headers"
BODY="$TMPDIR_E2E/body"

fail() {
  echo "FALHOU: $1" >&2
  exit 1
}

assert_eq() {
  local expected="$1" actual="$2" desc="$3"
  if [ "$expected" != "$actual" ]; then
    fail "$desc (esperado: '$expected', obtido: '$actual')"
  fi
}

assert_contains() {
  local haystack="$1" needle="$2" desc="$3"
  case "$haystack" in
    *"$needle"*) ;;
    *) fail "$desc (esperado conter '$needle')" ;;
  esac
}

sql() {
  docker compose exec -T db mariadb -ulpm -plpm_dev_pass lpm -N -B -e "$1"
}

http_code() {
  # http_code <url>
  # -o com caminho relativo: com MSYS_NO_PATHCONV=1 o curl nativo do Windows
  # não resolve "/dev/null" passado como argumento.
  curl -s -o "$TMPDIR_E2E/discard" -w "%{http_code}" "$1"
}

# get_form <url> <cookiejar>
# Baixa o HTML, guarda em $FORM_HTML e extrai csrf_token/form_ts em $CSRF/$FORM_TS.
get_form() {
  local url="$1" jar="$2"
  FORM_HTML=$(curl -s -c "$jar" -b "$jar" "$url")
  # "|| true": sob set -e + pipefail, grep sem match (ex.: form_ts ausente em
  # /setup.php) faria a atribuição abortar o script silenciosamente.
  CSRF=$(printf '%s' "$FORM_HTML" | grep -oE 'name="csrf_token"[^>]*value="[^"]*"' | head -1 | sed -E 's/.*value="([^"]*)".*/\1/' || true)
  FORM_TS=$(printf '%s' "$FORM_HTML" | grep -oE 'name="form_ts"[^>]*value="[^"]*"' | head -1 | sed -E 's/.*value="([^"]*)".*/\1/' || true)
}

# do_post <jar> <json:0|1> <field=value> ...
# POST em /api/lead.php; grava headers em $HDR, corpo em $BODY; retorna http code via stdout.
do_post() {
  local jar="$1" json="$2"
  shift 2
  local args=(-s -D "$HDR" -o "$BODY" -w "%{http_code}" -b "$jar" -c "$jar")
  if [ "$json" = "1" ]; then
    args+=(-H "Accept: application/json")
  fi
  for kv in "$@"; do
    args+=(--data-urlencode "$kv")
  done
  curl "${args[@]}" "$BASE/api/lead.php"
}

leads_count() {
  sql "SELECT COUNT(*) FROM leads"
}

echo "== Passo 1: subindo stack Docker =="
rm -f app/storage/setup.lock
docker compose down -v
docker compose up -d --build --wait

ready=0
for i in $(seq 1 30); do
  code=$(http_code "$BASE/" || echo "000")
  if [ "$code" = "200" ]; then
    ready=1
    break
  fi
  sleep 1
done
[ "$ready" = "1" ] || fail "Servidor não respondeu 200 em / após 30 tentativas"
echo "Servidor respondeu 200 em /"

echo "== Passo 2: proteção de arquivos sensíveis =="
code=$(http_code "$BASE/app/config/config.php")
[ "$code" != "200" ] || fail "GET /app/config/config.php respondeu 200"
assert_eq "403" "$(http_code "$BASE/_bootstrap.php")" "GET /_bootstrap.php deveria responder 403"
code=$(http_code "$BASE/.htaccess")
[ "$code" != "200" ] || fail "GET /.htaccess respondeu 200"

echo "== Passo 3: instalação (setup.php) =="
SETUP_JAR="$TMPDIR_E2E/setup.cookies"
get_form "$BASE/setup.php" "$SETUP_JAR"
[ -n "$CSRF" ] || fail "Não foi possível extrair csrf_token de /setup.php"

code=$(curl -s -D "$HDR" -o "$BODY" -w "%{http_code}" -b "$SETUP_JAR" -c "$SETUP_JAR" \
  --data-urlencode "email=dono@example.com" \
  --data-urlencode "password=SenhaForte123!" \
  --data-urlencode "password_confirm=SenhaForte123!" \
  --data-urlencode "setup_token=$DOCKER_SETUP_TOKEN" \
  --data-urlencode "csrf_token=$CSRF" \
  "$BASE/setup.php")
assert_eq "200" "$code" "POST /setup.php deveria responder 200"
assert_contains "$(cat "$BODY")" "Instalação concluída" "Corpo do setup.php deveria conter 'Instalação concluída'"

assert_eq "1" "$(sql "SELECT COUNT(*) FROM users")" "Deveria existir 1 usuário após o setup"
assert_eq "1" "$(sql "SELECT password_hash LIKE '\$2y\$%' OR password_hash LIKE '\$argon2%' FROM users")" "Senha do dono deveria estar em hash"
TABLES=$(sql "SHOW TABLES")
assert_contains "$TABLES" "leads" "SHOW TABLES deveria conter leads"
assert_contains "$TABLES" "users" "SHOW TABLES deveria conter users"
assert_contains "$TABLES" "rate_limits" "SHOW TABLES deveria conter rate_limits"

assert_eq "403" "$(http_code "$BASE/setup.php")" "GET /setup.php após instalado deveria responder 403"

echo "== Passo 4: lead feliz via JSON =="
JAR1="$TMPDIR_E2E/jar1"
get_form "$BASE/" "$JAR1"
[ -n "$CSRF" ] || fail "Não foi possível extrair csrf_token de /"
[ -n "$FORM_TS" ] || fail "Não foi possível extrair form_ts de /"
FIRST_INTEREST=$(printf '%s' "$FORM_HTML" | grep -oE 'name="interests\[\]"[^>]*value="[^"]*"' | head -1 | sed -E 's/.*value="([^"]*)".*/\1/' || true)
[ -n "$FIRST_INTEREST" ] || fail "Não foi possível extrair o primeiro id de interesse de /"

sleep 3
code=$(do_post "$JAR1" 1 \
  "name=Ana Souza" \
  "phone=(11) 98765-4321" \
  "email=ana@example.com" \
  "message=Quero saber da mentoria" \
  "interests[]=$FIRST_INTEREST" \
  "consent=1" \
  "website=" \
  "csrf_token=$CSRF" \
  "form_ts=$FORM_TS")
assert_eq "200" "$code" "POST /api/lead.php (JSON, feliz) deveria responder 200"
assert_contains "$(cat "$BODY")" '"ok":true' "Resposta JSON deveria conter \"ok\":true"
assert_contains "$(cat "$BODY")" "wa.me" "Resposta JSON deveria conter link wa.me"

assert_eq "11987654321" "$(sql "SELECT phone FROM leads WHERE email='ana@example.com'")" "Telefone deveria estar gravado só com dígitos"
assert_eq "novo" "$(sql "SELECT status FROM leads WHERE email='ana@example.com'")" "Status inicial deveria ser 'novo'"
assert_eq "1" "$(sql "SELECT consent_at IS NOT NULL FROM leads WHERE email='ana@example.com'")" "consent_at não deveria ser nulo"
assert_eq "1" "$(sql "SELECT consent_ip != '' FROM leads WHERE email='ana@example.com'")" "consent_ip não deveria estar vazio"
assert_eq "1" "$(sql "SELECT consent_text != '' FROM leads WHERE email='ana@example.com'")" "consent_text não deveria estar vazio"
assert_eq "1" "$(sql "SELECT interests LIKE '%$FIRST_INTEREST%' FROM leads WHERE email='ana@example.com'")" "interests deveria conter o id marcado"

echo "== Passo 5: lead feliz sem JavaScript =="
JAR2="$TMPDIR_E2E/jar2"
get_form "$BASE/" "$JAR2"
sleep 3
code=$(do_post "$JAR2" 0 \
  "name=Bruno Lima" \
  "phone=(21) 91234-5678" \
  "email=bruno@example.com" \
  "message=Quero mais informacoes" \
  "consent=1" \
  "website=" \
  "csrf_token=$CSRF" \
  "form_ts=$FORM_TS")
assert_eq "303" "$code" "POST /api/lead.php sem Accept JSON deveria responder 303"
LOCATION=$(grep -i '^location:' "$HDR" | tr -d '\r' | sed -E 's/[Ll]ocation: ?//')
assert_contains "$LOCATION" "enviado=1" "Location deveria conter enviado=1"

FOLLOW_HTML=$(curl -s -b "$JAR2" -c "$JAR2" "$BASE/index.php?enviado=1")
assert_contains "$FOLLOW_HTML" "wa.me" "Página de confirmação deveria conter wa.me"
assert_contains "$FOLLOW_HTML" "Bruno" "Página de confirmação deveria conter o nome enviado"

assert_eq "2" "$(leads_count)" "Deveria haver 2 leads gravados até aqui"

echo "== Passo 6: cenários negativos =="

echo "-- honeypot --"
sql "DELETE FROM rate_limits"
JAR3="$TMPDIR_E2E/jar3"
get_form "$BASE/" "$JAR3"
code=$(do_post "$JAR3" 1 \
  "name=Spammer" \
  "phone=(11) 90000-0000" \
  "email=spam@example.com" \
  "message=Spam" \
  "consent=1" \
  "website=http://spam.example" \
  "csrf_token=$CSRF" \
  "form_ts=$FORM_TS")
assert_eq "200" "$code" "Honeypot preenchido deveria responder 200 (falso sucesso)"
assert_eq "2" "$(leads_count)" "Honeypot não deveria gravar lead"

echo "-- csrf inválido --"
sql "DELETE FROM rate_limits"
JAR4="$TMPDIR_E2E/jar4"
get_form "$BASE/" "$JAR4"
code=$(do_post "$JAR4" 1 \
  "name=Teste" \
  "phone=11999998888" \
  "email=teste@example.com" \
  "message=Teste" \
  "consent=1" \
  "website=" \
  "csrf_token=invalido" \
  "form_ts=$FORM_TS")
assert_eq "403" "$code" "CSRF inválido deveria responder 403"
assert_eq "2" "$(leads_count)" "CSRF inválido não deveria gravar lead"

echo "-- envio rápido demais --"
sql "DELETE FROM rate_limits"
JAR5="$TMPDIR_E2E/jar5"
get_form "$BASE/" "$JAR5"
code=$(do_post "$JAR5" 1 \
  "name=Rapido" \
  "phone=11999997777" \
  "email=rapido@example.com" \
  "message=Rapido demais" \
  "consent=1" \
  "website=" \
  "csrf_token=$CSRF" \
  "form_ts=$FORM_TS")
assert_eq "422" "$code" "Envio antes do tempo mínimo deveria responder 422"
assert_contains "$(cat "$BODY")" "too_fast" "Resposta deveria indicar too_fast"
assert_eq "2" "$(leads_count)" "Envio rápido demais não deveria gravar lead"

echo "-- validação --"
sql "DELETE FROM rate_limits"
JAR6="$TMPDIR_E2E/jar6"
get_form "$BASE/" "$JAR6"
sleep 3
code=$(do_post "$JAR6" 1 \
  "name=Invalido" \
  "phone=123" \
  "email=nao-e-email" \
  "message=Mensagem de teste" \
  "interests[]=produto-inexistente" \
  "website=" \
  "csrf_token=$CSRF" \
  "form_ts=$FORM_TS")
assert_eq "422" "$code" "Dados inválidos deveriam responder 422"
VALID_BODY=$(cat "$BODY")
assert_contains "$VALID_BODY" "consent" "Erro de validação deveria citar consent"
assert_contains "$VALID_BODY" "email" "Erro de validação deveria citar email"
assert_contains "$VALID_BODY" "phone" "Erro de validação deveria citar phone"
assert_contains "$VALID_BODY" "interests" "Erro de validação deveria citar interests"
assert_eq "2" "$(leads_count)" "Envio inválido não deveria gravar lead"

echo "== Passo 7: rate limit =="
sql "DELETE FROM rate_limits"
JAR7="$TMPDIR_E2E/jar7"
get_form "$BASE/" "$JAR7"
sleep 3
for i in 1 2 3 4 5; do
  code=$(do_post "$JAR7" 1 \
    "name=RateLimit" \
    "phone=11999996666" \
    "email=rate@example.com" \
    "message=Teste de limite" \
    "website=" \
    "csrf_token=$CSRF" \
    "form_ts=$FORM_TS")
  assert_eq "422" "$code" "Tentativa $i de rate limit deveria responder 422 (dado inválido, sem consent)"
done
code=$(do_post "$JAR7" 1 \
  "name=RateLimit" \
  "phone=11999996666" \
  "email=rate@example.com" \
  "message=Teste de limite" \
  "website=" \
  "csrf_token=$CSRF" \
  "form_ts=$FORM_TS")
assert_eq "429" "$code" "6ª tentativa deveria responder 429 (rate limit)"

echo "E2E OK"

if [ "${1:-}" = "--down" ]; then
  docker compose down -v
fi
