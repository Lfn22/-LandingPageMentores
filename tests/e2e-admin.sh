#!/usr/bin/env bash
set -euo pipefail
export MSYS_NO_PATHCONV=1

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

BASE="http://localhost:${LPM_PORT:-8091}"

echo "== Passo 0: e2e da Fase 1 (stack + setup + leads) =="
bash tests/e2e.sh

# Caminho relativo (não "/tmp/..."): com MSYS_NO_PATHCONV=1 o curl nativo do
# Windows não resolve caminhos POSIX absolutos passados para -c/-b/-D/-o.
TMPDIR_ADMIN="tests/.e2e-admin-tmp"
rm -rf "$TMPDIR_ADMIN"
mkdir -p "$TMPDIR_ADMIN"
trap 'rm -rf "$TMPDIR_ADMIN"' EXIT
HDR="$TMPDIR_ADMIN/headers"
BODY="$TMPDIR_ADMIN/body"

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

assert_not_contains() {
  local haystack="$1" needle="$2" desc="$3"
  case "$haystack" in
    *"$needle"*) fail "$desc (não deveria conter '$needle')" ;;
    *) ;;
  esac
}

sql() {
  docker compose exec -T db mariadb -ulpm -plpm_dev_pass lpm -N -B -e "$1"
}

http_code() {
  curl -s -o "$TMPDIR_ADMIN/discard" -w "%{http_code}" "$1"
}

# csrf_from <html>
csrf_from() {
  printf '%s' "$1" | grep -oE 'name="csrf_token"[^>]*value="[^"]*"' | head -1 | sed -E 's/.*value="([^"]*)".*/\1/' || true
}

echo "== Passo (a): /admin/ sem sessão =="
JAR1="$TMPDIR_ADMIN/jar1"
HTML=$(curl -s -c "$JAR1" -b "$JAR1" "$BASE/admin/")
CODE=$(curl -s -D "$HDR" -o "$TMPDIR_ADMIN/discard" -w "%{http_code}" -c "$JAR1" -b "$JAR1" "$BASE/admin/")
assert_eq "200" "$CODE" "GET /admin/ sem sessão deveria responder 200"
assert_contains "$HTML" 'name="password"' "Login deveria conter campo password"
assert_not_contains "$HTML" "Fila de trabalho" "Sem sessão não deveria mostrar Fila de trabalho"
grep -qi '^x-robots-tag:.*noindex' "$HDR" || fail "Header X-Robots-Tag noindex ausente em /admin/"

echo "== Passo (b): senha errada =="
sql "DELETE FROM rate_limits"
CSRF=$(csrf_from "$HTML")
[ -n "$CSRF" ] || fail "Não foi possível extrair csrf_token do login"
BODY_WRONG=$(curl -s -c "$JAR1" -b "$JAR1" \
  --data-urlencode "email=dono@example.com" \
  --data-urlencode "password=senhaerrada" \
  --data-urlencode "csrf_token=$CSRF" \
  "$BASE/admin/")
assert_contains "$BODY_WRONG" "E-mail ou senha inv" "Senha errada deveria mostrar mensagem genérica"
assert_not_contains "$BODY_WRONG" "Fila de trabalho" "Senha errada não deveria mostrar o painel"

echo "== Passo (c): login correto =="
HTML_LOGIN=$(curl -s -c "$JAR1" -b "$JAR1" "$BASE/admin/")
CSRF=$(csrf_from "$HTML_LOGIN")
CODE=$(curl -s -D "$HDR" -o "$BODY" -w "%{http_code}" -c "$JAR1" -b "$JAR1" \
  --data-urlencode "email=dono@example.com" \
  --data-urlencode "password=SenhaForte123!" \
  --data-urlencode "csrf_token=$CSRF" \
  "$BASE/admin/")
assert_eq "303" "$CODE" "Login correto deveria responder 303"

PANEL=$(curl -s -c "$JAR1" -b "$JAR1" "$BASE/admin/")
assert_contains "$PANEL" "Inscritos no total" "Painel deveria mostrar o KPI Inscritos no total"
assert_contains "$PANEL" "Radar de demanda" "Painel deveria mostrar Radar de demanda"
assert_contains "$PANEL" "Fila de trabalho" "Painel deveria mostrar Fila de trabalho"
assert_contains "$PANEL" "Ana Souza" "Painel deveria listar Ana Souza"
assert_contains "$PANEL" "Bruno Lima" "Painel deveria listar Bruno Lima"

assert_eq "1" "$(sql "SELECT last_login_at IS NOT NULL FROM users WHERE email='dono@example.com'")" "last_login_at deveria estar preenchido após o login"

echo "== Passo (d): busca =="
SEARCH=$(curl -s -c "$JAR1" -b "$JAR1" "$BASE/admin/?q=bruno")
# Radar de demanda sempre mostra as mensagens recentes (não é filtrado pela
# busca); a asserção de exclusão deve olhar só a seção "Fila de trabalho".
QUEUE_PART="${SEARCH#*Fila de trabalho}"
assert_contains "$QUEUE_PART" "Bruno Lima" "Busca por 'bruno' deveria retornar Bruno Lima na fila"
assert_not_contains "$QUEUE_PART" "Ana Souza" "Busca por 'bruno' não deveria retornar Ana Souza na fila"

echo "== Passo (csv-1): export.php e status.php sem sessão =="
JAR3="$TMPDIR_ADMIN/jar3"
CODE=$(curl -s -o "$TMPDIR_ADMIN/discard" -w "%{http_code}" -c "$JAR3" -b "$JAR3" "$BASE/admin/export.php")
[ "$CODE" != "200" ] || fail "GET /admin/export.php sem sessão não deveria responder 200 (obtido: $CODE)"
CODE=$(curl -s -o "$BODY" -w "%{http_code}" -c "$JAR3" -b "$JAR3" -H "Accept: application/json" \
  --data-urlencode "status=contatado" --data-urlencode "id=1" \
  "$BASE/admin/status.php")
assert_eq "401" "$CODE" "POST /admin/status.php sem sessão (Accept JSON) deveria responder 401"

echo "== Passo (csv-2): lead com tentativa de formula injection =="
sql "INSERT INTO leads (name, phone, email, message, interests, status, consent_at, consent_ip, consent_text, ip, user_agent, created_at) VALUES ('=HYPERLINK(1)', '11999998888', 'formula@example.com', 'teste formula injection', NULL, 'novo', NOW(), '127.0.0.1', 'consentimento de teste', '127.0.0.1', 'e2e-admin', NOW())"

echo "== Passo (csv-3): export.php logado =="
CODE=$(curl -s -D "$HDR" -o "$BODY" -w "%{http_code}" -c "$JAR1" -b "$JAR1" "$BASE/admin/export.php")
assert_eq "200" "$CODE" "GET /admin/export.php logado deveria responder 200"
grep -qi '^content-type:.*text/csv' "$HDR" || fail "Content-Type do export.php deveria conter text/csv"
FIRST3=$(head -c 3 "$BODY" | od -An -tx1 | tr -d ' \n')
assert_eq "efbbbf" "$FIRST3" "export.php deveria começar com BOM UTF-8"
FIRSTLINE=$(head -n 1 "$BODY")
assert_contains "$FIRSTLINE" "id;data;nome;telefone;email;mensagem;interesses;status;consent_at;consent_ip" "Cabeçalho do CSV incorreto"
CSVBODY=$(cat "$BODY")
assert_contains "$CSVBODY" "ana@example.com" "CSV deveria conter o lead da Ana"
assert_contains "$CSVBODY" "'=HYPERLINK" "CSV deveria neutralizar fórmula com prefixo '"

echo "== Passo (csv-4): status.php troca status =="
ANA_ID=$(sql "SELECT id FROM leads WHERE email='ana@example.com'")
[ -n "$ANA_ID" ] || fail "Não encontrei o id da Ana"
CODE=$(curl -s -o "$BODY" -w "%{http_code}" -c "$JAR1" -b "$JAR1" -H "Accept: application/json" \
  --data-urlencode "id=$ANA_ID" --data-urlencode "status=contatado" \
  "$BASE/admin/status.php")
assert_eq "403" "$CODE" "POST /admin/status.php sem csrf_token deveria responder 403"

PANEL_CSRF=$(curl -s -c "$JAR1" -b "$JAR1" "$BASE/admin/")
CSRF=$(csrf_from "$PANEL_CSRF")
CODE=$(curl -s -o "$BODY" -w "%{http_code}" -c "$JAR1" -b "$JAR1" -H "Accept: application/json" \
  --data-urlencode "id=$ANA_ID" --data-urlencode "status=contatado" --data-urlencode "csrf_token=$CSRF" \
  "$BASE/admin/status.php")
assert_eq "200" "$CODE" "POST /admin/status.php com csrf válido deveria responder 200"
assert_contains "$(cat "$BODY")" '"ok":true' "Resposta de status.php deveria conter ok:true"
assert_eq "contatado" "$(sql "SELECT status FROM leads WHERE id=$ANA_ID")" "Status da Ana deveria persistir como contatado"

CODE=$(curl -s -o "$BODY" -w "%{http_code}" -c "$JAR1" -b "$JAR1" -H "Accept: application/json" \
  --data-urlencode "id=$ANA_ID" --data-urlencode "status=invalido" --data-urlencode "csrf_token=$CSRF" \
  "$BASE/admin/status.php")
assert_eq "422" "$CODE" "POST /admin/status.php com status inválido deveria responder 422"

echo "== Passo (csv-5): painel referencia copiar CSV e admin.js =="
assert_contains "$PANEL_CSRF" "data-copy-csv" "Painel deveria ter o botão data-copy-csv"
assert_contains "$PANEL_CSRF" "admin.js" "Painel deveria carregar admin.js"

echo "== Passo (e): logout =="
CSRF=$(csrf_from "$PANEL")
CODE=$(curl -s -D "$HDR" -o "$BODY" -w "%{http_code}" -c "$JAR1" -b "$JAR1" \
  --data-urlencode "csrf_token=$CSRF" \
  "$BASE/admin/logout.php")
assert_eq "303" "$CODE" "Logout deveria responder 303"
AFTER=$(curl -s -c "$JAR1" -b "$JAR1" "$BASE/admin/")
assert_not_contains "$AFTER" "Fila de trabalho" "Após logout não deveria mostrar o painel"

echo "== Passo (f): rate limit do login =="
sql "DELETE FROM rate_limits"
JAR2="$TMPDIR_ADMIN/jar2"
HTML_RL=$(curl -s -c "$JAR2" -b "$JAR2" "$BASE/admin/")
CSRF=$(csrf_from "$HTML_RL")
for i in 1 2 3 4 5; do
  CODE=$(curl -s -o "$BODY" -w "%{http_code}" -c "$JAR2" -b "$JAR2" \
    --data-urlencode "email=dono@example.com" \
    --data-urlencode "password=senhaerrada" \
    --data-urlencode "csrf_token=$CSRF" \
    "$BASE/admin/")
  assert_eq "401" "$CODE" "Tentativa $i de login errado deveria responder 401"
done
CODE=$(curl -s -o "$BODY" -w "%{http_code}" -c "$JAR2" -b "$JAR2" \
  --data-urlencode "email=dono@example.com" \
  --data-urlencode "password=senhaerrada" \
  --data-urlencode "csrf_token=$CSRF" \
  "$BASE/admin/")
assert_eq "429" "$CODE" "6ª tentativa deveria responder 429 (rate limit)"

echo "E2E ADMIN OK"
