#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
IMAGE="${DIR_BROWSER_TEST_IMAGE:-dir-browser:test}"
CONTAINER="dir-browser-integration-$RANDOM"
PORT="${DIR_BROWSER_TEST_PORT:-18080}"
BASE_URL="http://127.0.0.1:${PORT}"
GITSYNC_FIXTURE="$(mktemp -d)"

cleanup() {
  docker rm -f "$CONTAINER" >/dev/null 2>&1 || true
  docker run --rm --entrypoint bash -v "$GITSYNC_FIXTURE:/fixture" "$IMAGE" \
    -c 'if [ -d /fixture/.worktrees/rev-b/owned ]; then chmod -R a+rwX /fixture/.worktrees/rev-b/owned; fi' \
    >/dev/null 2>&1 || true
  rm -rf "$GITSYNC_FIXTURE"
}
trap cleanup EXIT

docker build -t "$IMAGE" "$ROOT_DIR"
docker run -d --name "$CONTAINER" \
  -p "${PORT}:80" \
  -v "$ROOT_DIR/tests/fixtures/public:/var/www/html/public:ro" \
  -v "$ROOT_DIR/tests/fixtures/public-backup:/var/www/html/public-backup:ro" \
  "$IMAGE" >/dev/null

for _ in $(seq 1 30); do
  if curl --fail --silent "$BASE_URL/__health" >/dev/null; then break; fi
  sleep 1
done
curl --fail --silent "$BASE_URL/__health" | grep -q '"status":"ok"'
docker exec "$CONTAINER" grep -q 'alias /var/www/html/.dir-browser-public-root/;' /etc/nginx/conf.d/default.conf
docker exec "$CONTAINER" test "$(docker exec "$CONTAINER" readlink /var/www/html/.dir-browser-public-root)" = /var/www/html/public

# Startup preprocessing must not traverse into the read-only public mount,
# even when user content contains nested PHP templates.
grep -q 'process.env.DIRBROWSER_VERSION' "$ROOT_DIR/tests/fixtures/public/nested/index.php"

assert_status() {
  local expected="$1"
  shift
  local actual
  actual="$(curl --silent --output /tmp/dir-browser-test-response --write-out '%{http_code}' "$@" || true)"
  if [ "$actual" != "$expected" ]; then
    echo "Expected HTTP $expected, got $actual for: $*" >&2
    sed -n '1,20p' /tmp/dir-browser-test-response >&2
    if [ "$actual" = "000" ]; then docker logs "$CONTAINER" >&2 || true; fi
    exit 1
  fi
}

assert_body_contains() {
  if ! grep -q "$1" /tmp/dir-browser-test-response; then
    echo "Expected response body to contain: $1" >&2
    sed -n '1,20p' /tmp/dir-browser-test-response >&2
    exit 1
  fi
}

assert_body_excludes() {
  if grep -q "$1" /tmp/dir-browser-test-response; then
    echo "Expected response body not to contain: $1" >&2
    exit 1
  fi
}

# Dotfiles are intentionally public.
assert_status 200 "$BASE_URL/.env"
grep -q 'DOTFILES_ARE_PUBLIC=by-design' /tmp/dir-browser-test-response

# Breadcrumb generation must work for non-root folders.
assert_status 200 "$BASE_URL/nested/"
grep -q 'href="/nested/"' /tmp/dir-browser-test-response

# Metadata hash enforcement applies to direct files.
assert_status 403 "$BASE_URL/locked.txt"
assert_status 200 "$BASE_URL/locked.txt?hash=2c0cf24749273cf66978a30ffab07782563e8c45a636d92929a9d21859fc11b9"

# Folder access configs are never downloadable or included in listings.
assert_status 404 "$BASE_URL/protected/.access.json"
assert_status 200 "$BASE_URL/"
assert_body_excludes '.access.json'

# Protected folders, inherited children, direct files, and their APIs all deny
# unauthenticated requests without disclosing the protected content.
assert_status 401 "$BASE_URL/protected/"
assert_body_excludes 'protected-content'
assert_status 401 "$BASE_URL/protected/secret.txt"
assert_body_excludes 'protected-content'
assert_status 401 "$BASE_URL/protected/child/inherited.txt"
assert_status 401 "$BASE_URL/protected/secret.txt?info=1"
assert_status 401 "$BASE_URL/protected/secret.txt?preview=1"
assert_status 401 --get --data-urlencode 'q=secret' --data-urlencode 'e=s' "$BASE_URL/protected/"

# X-Key authenticates a request without creating a browser session.
assert_status 200 --header 'X-Key: folder-secret' "$BASE_URL/protected/secret.txt"
assert_body_contains 'protected-content'
assert_status 200 --header 'X-Key: folder-secret' "$BASE_URL/protected/child/inherited.txt"
assert_body_contains 'inherited-protected-content'
assert_status 401 "$BASE_URL/protected/secret.txt"
assert_status 401 --header 'X-Key: wrong-secret' "$BASE_URL/protected/secret.txt"

# A successful form login uses PRG, stores only an opaque session token, and
# authorizes subsequent folder, file, API, search, and batch requests.
COOKIE_JAR="$(mktemp)"
LOGIN_HEADERS="$(mktemp)"
assert_status 401 --request POST --data-urlencode 'key=wrong-secret' "$BASE_URL/protected/"
assert_body_contains 'Incorrect password.'
assert_status 303 --dump-header "$LOGIN_HEADERS" --cookie-jar "$COOKIE_JAR" \
  --request POST --data-urlencode 'key=folder-secret' "$BASE_URL/protected/"
grep -qi '^Location: /protected/' "$LOGIN_HEADERS"
grep -qi 'Set-Cookie: dir_browser_session=' "$LOGIN_HEADERS"
grep -qi 'Set-Cookie: dir_browser_session=.*HttpOnly' "$LOGIN_HEADERS"
grep -qi 'Set-Cookie: dir_browser_session=.*SameSite=Lax' "$LOGIN_HEADERS"
if grep -qi 'Set-Cookie: dir_browser_session=.*Secure' "$LOGIN_HEADERS"; then
  echo 'HTTP login unexpectedly issued a Secure-only cookie' >&2
  exit 1
fi
if grep -q 'folder-secret' "$LOGIN_HEADERS" "$COOKIE_JAR"; then
  echo 'Raw folder password leaked into the authentication cookie' >&2
  exit 1
fi
assert_status 200 --cookie "$COOKIE_JAR" "$BASE_URL/protected/"
assert_status 200 --cookie "$COOKIE_JAR" "$BASE_URL/protected/secret.txt?info=1"
assert_body_contains '"name":"secret.txt"'
assert_status 200 --cookie "$COOKIE_JAR" "$BASE_URL/protected/secret.txt?preview=1"
assert_body_contains 'protected-content'
assert_status 200 --cookie "$COOKIE_JAR" --get --data-urlencode 'q=secret' --data-urlencode 'e=s' "$BASE_URL/protected/"
assert_body_contains 'secret.txt'
assert_status 200 --cookie "$COOKIE_JAR" --request POST \
  --data-urlencode 'download_batch[]=/protected/secret.txt' "$BASE_URL/"

# A child password overrides its inherited parent credential.
assert_status 401 --header 'X-Key: folder-secret' "$BASE_URL/protected/override/child-secret.txt"
assert_status 200 --header 'X-Key: child-secret' "$BASE_URL/protected/override/child-secret.txt"
assert_body_contains 'overridden-protected-content'

# inherit=false protects the configured folder itself but not descendants.
assert_status 401 "$BASE_URL/non-inherited/"
assert_status 401 "$BASE_URL/non-inherited/locked-at-root.txt"
assert_status 200 "$BASE_URL/non-inherited/child/public.txt"
assert_body_contains 'inheritance-disabled-content'

# password_hash credentials are verified using password_verify.
assert_status 401 "$BASE_URL/hashed/hash-secret.txt"
assert_status 401 --header 'X-Key: not-foobar' "$BASE_URL/hashed/hash-secret.txt"
assert_status 200 --header 'X-Key: foobar' "$BASE_URL/hashed/hash-secret.txt"
assert_body_contains 'hashed-password-content'

# Hidden folders are absent from listings/search and indistinguishable from a
# missing path even when a key is supplied.
assert_status 200 "$BASE_URL/"
assert_body_excludes 'invisible.txt'
assert_status 404 "$BASE_URL/hidden/"
assert_status 404 "$BASE_URL/hidden/invisible.txt"
assert_status 404 --header 'X-Key: anything' "$BASE_URL/hidden/invisible.txt"
assert_status 200 --get --data-urlencode 'q=invisible' --data-urlencode 'e=s' "$BASE_URL/"
assert_body_excludes 'invisible.txt'

# Logout invalidates the server-side grant and expires both current and legacy
# password cookies.
LOGOUT_HEADERS="$(mktemp)"
assert_status 303 --cookie "$COOKIE_JAR" --cookie-jar "$COOKIE_JAR" --dump-header "$LOGOUT_HEADERS" \
  "$BASE_URL/protected/?logout=1"
grep -qi '^Location: /' "$LOGOUT_HEADERS"
grep -qi 'Set-Cookie: dir_browser_session=.*expires=' "$LOGOUT_HEADERS"
grep -qi 'Set-Cookie: dir_browser_key=.*expires=' "$LOGOUT_HEADERS"
assert_status 401 --cookie "$COOKIE_JAR" "$BASE_URL/protected/secret.txt"

# Legacy query-key login still works but redirects to a URL without the secret.
LEGACY_HEADERS="$(mktemp)"
assert_status 303 --cookie-jar "$COOKIE_JAR" --dump-header "$LEGACY_HEADERS" \
  "$BASE_URL/protected/?key=folder-secret&p=1"
grep -qi '^Location: /protected/?p=1' "$LEGACY_HEADERS"
if grep -qi '^Location: .*key=' "$LEGACY_HEADERS"; then
  echo 'Legacy login redirect retained the password in its Location header' >&2
  exit 1
fi
assert_status 200 --cookie "$COOKIE_JAR" "$BASE_URL/protected/secret.txt"

# Glob patterns cannot traverse out of their selected folder.
assert_status 400 --get --data-urlencode 'q=../*' --data-urlencode 'e=g' "$BASE_URL/"

# Batch paths are constrained even though POST values bypass URI normalization.
assert_status 400 --request POST --data-urlencode 'download_batch[]=/../public-backup/secret.txt' "$BASE_URL/"
if grep -q 'must-not-cross-the-public-root' /tmp/dir-browser-test-response; then
  echo 'Batch traversal exposed sibling content' >&2
  exit 1
fi

# Default response hardening is present and wildcard CORS is opt-in.
headers="$(curl --silent --head "$BASE_URL/")"
grep -qi '^X-Content-Type-Options: nosniff' <<<"$headers"
if grep -qi '^Access-Control-Allow-Origin:' <<<"$headers"; then
  echo 'Wildcard CORS should be disabled by default' >&2
  exit 1
fi

rm -f "$COOKIE_JAR" "$LOGIN_HEADERS" "$LOGOUT_HEADERS" "$LEGACY_HEADERS"

# Exercise instance-wide URL-key and Basic authentication together. Health is
# deliberately available without credentials for container orchestration.
docker rm -f "$CONTAINER" >/dev/null
docker run -d --name "$CONTAINER" \
  -p "${PORT}:80" \
  -e PASSWORD_URL_KEY=global-url-secret \
  -e PASSWORD_USER=admin \
  -e PASSWORD_RAW=global-password \
  -v "$ROOT_DIR/tests/fixtures/public:/var/www/html/public:ro" \
  "$IMAGE" >/dev/null
for _ in $(seq 1 30); do
  if curl --fail --silent "$BASE_URL/__health" >/dev/null; then break; fi
  sleep 1
done
assert_status 200 "$BASE_URL/__health"
assert_status 401 "$BASE_URL/"
assert_status 401 "$BASE_URL/?auth=global-url-secret"
assert_status 401 --user 'admin:global-password' "$BASE_URL/"
assert_status 401 --user 'admin:wrong-password' "$BASE_URL/?auth=global-url-secret"
assert_status 200 --user 'admin:global-password' "$BASE_URL/?auth=global-url-secret"
assert_body_contains 'auth=global-url-secret'
assert_status 200 --user 'admin:global-password' "$BASE_URL/locked.txt?auth=global-url-secret&hash=2c0cf24749273cf66978a30ffab07782563e8c45a636d92929a9d21859fc11b9"

# Build a writable git-sync-like content tree. The whole tree is mounted, but
# PUBLIC_ROOT selects only its atomically updated link.
mkdir -p \
  "$GITSYNC_FIXTURE/.worktrees/rev-a/protected" \
  "$GITSYNC_FIXTURE/.worktrees/rev-a/nested" \
  "$GITSYNC_FIXTURE/.worktrees/rev-b/owned" \
  "$GITSYNC_FIXTURE/public-backup"
printf 'revision-a\n' > "$GITSYNC_FIXTURE/.worktrees/rev-a/file-a.txt"
printf '# custom-root-readme\n' > "$GITSYNC_FIXTURE/.worktrees/rev-a/README.md"
printf 'space-and-unicode\n' > "$GITSYNC_FIXTURE/.worktrees/rev-a/space name ü.txt"
printf 'nested-custom-root\n' > "$GITSYNC_FIXTURE/.worktrees/rev-a/nested/result.txt"
printf 'protected-custom-root\n' > "$GITSYNC_FIXTURE/.worktrees/rev-a/protected/secret.txt"
printf '{"password_raw":"custom-secret"}\n' > "$GITSYNC_FIXTURE/.worktrees/rev-a/protected/.access.json"
printf 'hash-required-custom-root\n' > "$GITSYNC_FIXTURE/.worktrees/rev-a/locked.txt"
printf '{"hash_required":true}\n' > "$GITSYNC_FIXTURE/.worktrees/rev-a/locked.txt.dbmeta.json"
printf 'revision-b\n' > "$GITSYNC_FIXTURE/.worktrees/rev-b/file-b.txt"
printf 'owned-by-git-sync\n' > "$GITSYNC_FIXTURE/.worktrees/rev-b/owned/uid-65533.txt"
printf 'must-not-cross-custom-root\n' > "$GITSYNC_FIXTURE/public-backup/secret.txt"
ln -s .worktrees/rev-a "$GITSYNC_FIXTURE/link"
ln -s ../rev-a "$GITSYNC_FIXTURE/.worktrees/rev-b/sibling-link"
ln -s /var/www/html/public-backup "$GITSYNC_FIXTURE/.worktrees/rev-b/outside-link"

# Populate git-sync-owned content as root without changing the user's checkout.
docker run --rm --entrypoint bash \
  -v "$GITSYNC_FIXTURE:/fixture" \
  "$IMAGE" -c 'chown -R 65533:65533 /fixture/.worktrees/rev-b/owned && chmod 0755 /fixture/.worktrees/rev-b/owned && chmod 0744 /fixture/.worktrees/rev-b/owned/uid-65533.txt'

docker rm -f "$CONTAINER" >/dev/null
docker run -d --name "$CONTAINER" \
  -p "${PORT}:80" \
  -e PUBLIC_ROOT=/var/www/html/public/link \
  -e 'IGNORE=/\..*' \
  -e SEARCH_ENGINE=s,g,r \
  -v "$GITSYNC_FIXTURE:/var/www/html/public" \
  -v "$GITSYNC_FIXTURE/public-backup:/var/www/html/public-backup:ro" \
  "$IMAGE" >/dev/null
for _ in $(seq 1 30); do
  if curl --fail --silent "$BASE_URL/__health" >/dev/null; then break; fi
  sleep 1
done

# The logical root and its generated Nginx alias agree, while canonical
# git-sync implementation directories never enter browser-visible URLs.
docker exec "$CONTAINER" grep -q 'alias /var/www/html/.dir-browser-public-root/;' /etc/nginx/conf.d/default.conf
docker exec "$CONTAINER" test "$(docker exec "$CONTAINER" readlink /var/www/html/.dir-browser-public-root)" = /var/www/html/public/link
assert_status 200 "$BASE_URL/"
assert_body_contains 'file-a.txt'
assert_body_contains 'custom-root-readme'
assert_body_excludes '.worktrees'
assert_status 200 "$BASE_URL/?ls=1"
assert_body_contains '"url":"/file-a.txt"'
assert_body_excludes '.worktrees'
assert_status 200 "$BASE_URL/file-a.txt"
assert_body_contains 'revision-a'
assert_status 200 "$BASE_URL/file-a.txt?info=1"
assert_body_contains '"url":"/file-a.txt"'
assert_status 200 "$BASE_URL/file-a.txt?preview=1"
assert_body_contains 'revision-a'
assert_status 200 --get --data-urlencode 'q=file-a' --data-urlencode 'e=s' "$BASE_URL/"
assert_body_contains '"url":"/file-a.txt"'
assert_status 200 --get --data-urlencode 'q=file-a' --data-urlencode 'e=r' "$BASE_URL/"
assert_body_contains '"url":"/file-a.txt"'
assert_status 200 --get --data-urlencode 'q=*.txt' --data-urlencode 'e=g' "$BASE_URL/"
assert_body_contains '"url":"/file-a.txt"'
assert_status 401 "$BASE_URL/protected/secret.txt"
assert_status 200 --header 'X-Key: custom-secret' "$BASE_URL/protected/secret.txt"
assert_body_contains 'protected-custom-root'
custom_hash="$(sha256sum "$GITSYNC_FIXTURE/.worktrees/rev-a/locked.txt" | cut -d ' ' -f 1)"
assert_status 403 "$BASE_URL/locked.txt"
assert_status 200 "$BASE_URL/locked.txt?hash=$custom_hash"
assert_body_contains 'hash-required-custom-root'
assert_status 200 "$BASE_URL/space%20name%20%C3%BC.txt"
assert_body_contains 'space-and-unicode'
assert_status 200 --request POST --data-urlencode 'download_batch[]=/nested/result.txt' "$BASE_URL/"
unzip -p /tmp/dir-browser-test-response nested/result.txt | grep -q 'nested-custom-root'

# Child symlinks may not escape the selected repository, and encoded and batch
# traversal remain bounded.
assert_status 400 --path-as-is "$BASE_URL/%2e%2e/public-backup/secret.txt"
assert_status 400 --request POST --data-urlencode 'download_batch[]=/../public-backup/secret.txt' "$BASE_URL/"
assert_status 400 --get --data-urlencode 'q=../*' --data-urlencode 'e=g' "$BASE_URL/"

# A git-sync link rotation is visible to both PHP and Nginx without a restart.
ln -sfn .worktrees/rev-b "$GITSYNC_FIXTURE/link"
assert_status 200 "$BASE_URL/"
assert_body_contains 'file-b.txt'
assert_body_excludes 'file-a.txt'
assert_status 200 "$BASE_URL/file-b.txt"
assert_body_contains 'revision-b'
assert_status 404 "$BASE_URL/sibling-link/file-a.txt"
assert_status 404 "$BASE_URL/outside-link/secret.txt"
assert_status 200 "$BASE_URL/owned/uid-65533.txt?preview=1"
assert_body_contains 'owned-by-git-sync'
assert_status 200 "$BASE_URL/owned/uid-65533.txt"
assert_body_contains 'owned-by-git-sync'

# A root that resolves outside the content mount is unavailable but cannot
# affect the orchestration health endpoint or expose the sibling mount.
ln -s /var/www/html/public-backup "$GITSYNC_FIXTURE/escape"
docker rm -f "$CONTAINER" >/dev/null
docker run -d --name "$CONTAINER" \
  -p "${PORT}:80" \
  -e PUBLIC_ROOT=/var/www/html/public/escape \
  -v "$GITSYNC_FIXTURE:/var/www/html/public" \
  -v "$GITSYNC_FIXTURE/public-backup:/var/www/html/public-backup:ro" \
  "$IMAGE" >/dev/null
for _ in $(seq 1 30); do
  if curl --fail --silent "$BASE_URL/__health" >/dev/null; then break; fi
  sleep 1
done
assert_status 200 "$BASE_URL/__health"
assert_status 503 "$BASE_URL/"
assert_body_excludes 'must-not-cross-custom-root'
docker logs "$CONTAINER" 2>&1 | grep -q 'resolves outside the content mount'

# Startup may race with git-sync. A missing link reports content unavailability
# and begins serving as soon as the link appears.
docker rm -f "$CONTAINER" >/dev/null
docker run -d --name "$CONTAINER" \
  -p "${PORT}:80" \
  -e PUBLIC_ROOT=/var/www/html/public/late-link \
  -v "$GITSYNC_FIXTURE:/var/www/html/public" \
  "$IMAGE" >/dev/null
for _ in $(seq 1 30); do
  if curl --fail --silent "$BASE_URL/__health" >/dev/null; then break; fi
  sleep 1
done
assert_status 200 "$BASE_URL/__health"
assert_status 503 "$BASE_URL/"
docker logs "$CONTAINER" 2>&1 | grep -q 'PUBLIC_ROOT is not currently available'
ln -s .worktrees/rev-b "$GITSYNC_FIXTURE/late-link"
assert_status 200 "$BASE_URL/file-b.txt"
assert_body_contains 'revision-b'

# Without DAC override, a non-traversable configured directory produces an
# accurate permission diagnostic rather than an ownership mutation.
mkdir "$GITSYNC_FIXTURE/non-traversable" "$GITSYNC_FIXTURE/redis-data"
chmod 0644 "$GITSYNC_FIXTURE/non-traversable"
chmod 0755 "$GITSYNC_FIXTURE"
chmod 0777 "$GITSYNC_FIXTURE/redis-data"
docker rm -f "$CONTAINER" >/dev/null
docker run -d --name "$CONTAINER" \
  --cap-drop DAC_OVERRIDE \
  --cap-drop DAC_READ_SEARCH \
  -p "${PORT}:80" \
  -e PUBLIC_ROOT=/var/www/html/public/non-traversable \
  -v "$GITSYNC_FIXTURE:/var/www/html/public" \
  -v "$GITSYNC_FIXTURE/redis-data:/var/lib/redis" \
  "$IMAGE" >/dev/null
for _ in $(seq 1 30); do
  if ! docker inspect --format '{{.State.Running}}' "$CONTAINER" 2>/dev/null | grep -q true; then break; fi
  sleep 1
done
docker logs "$CONTAINER" 2>&1 | grep -q 'execute permission on every ancestor'

echo 'Integration checks passed.'
