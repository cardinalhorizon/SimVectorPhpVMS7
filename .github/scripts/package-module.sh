#!/usr/bin/env bash
# Package this module as an installable phpVMS module zip.
#
# phpVMS expects the archive to contain a single top-level folder whose name
# matches the module name (SimVector/), with module.json inside it.
#
# Usage: .github/scripts/package-module.sh [expected-tag]
#   expected-tag  optional release tag (e.g. v1.0.0); fails if it does not
#                 match the version in module.json.
# Output: dist/<Name>-<version>.zip (path is also written to $GITHUB_OUTPUT)
set -euo pipefail

cd "$(dirname "$0")/../.."

NAME=$(jq -r '.name' module.json)
VERSION=$(jq -r '.version' module.json)

if [[ -z "$NAME" || "$NAME" == "null" || -z "$VERSION" || "$VERSION" == "null" ]]; then
  echo "::error::module.json must define name and version" >&2
  exit 1
fi

if [[ -n "${1:-}" ]]; then
  TAG="${1#v}"
  if [[ "$TAG" != "$VERSION" ]]; then
    echo "::error::Release tag '$1' does not match module.json version '$VERSION'" >&2
    exit 1
  fi
fi

# Every provider declared in module.json must exist on disk (module namespace is Modules\<Name>\)
while IFS= read -r provider; do
  rel="${provider#Modules\\${NAME}\\}"
  file="${rel//\\//}.php"
  if [[ ! -f "$file" ]]; then
    echo "::error::Provider $provider not found at $file" >&2
    exit 1
  fi
done < <(jq -r '.providers[]' module.json)

# Syntax check all shipped PHP
if command -v php >/dev/null 2>&1; then
  while IFS= read -r f; do
    php -l "$f" >/dev/null
  done < <(find . -name '*.php' -not -path './node_modules/*' -not -path './dist/*' -not -path './.git/*')
fi

# The built frontend bundle must exist (run `npm run prod` first)
if [[ ! -f public/mix-manifest.json ]]; then
  echo "::error::public/mix-manifest.json missing; build the frontend first (npm run prod)" >&2
  exit 1
fi

STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT
DEST="$STAGE/$NAME"
mkdir -p "$DEST"

# Runtime files only: no sources, node_modules, tests, VCS or editor files.
rsync -a \
  --exclude '.DS_Store' \
  --include 'Config/***' \
  --include 'Console/***' \
  --include 'Database/***' \
  --include 'Exceptions/***' \
  --include 'Http/***' \
  --include 'Listeners/***' \
  --include 'Models/***' \
  --include 'Providers/***' \
  --include 'Services/***' \
  --include 'public/***' \
  --include 'Resources/' \
  --include 'Resources/views/***' \
  --include 'Resources/lang/***' \
  --include 'helpers.php' \
  --include 'module.json' \
  --include 'composer.json' \
  --include 'README.md' \
  --exclude '*' \
  ./ "$DEST/"

mkdir -p dist
ZIP="dist/${NAME}-${VERSION}.zip"
rm -f "$ZIP"
(cd "$STAGE" && zip -qr "$OLDPWD/$ZIP" "$NAME")

# Verify the archive layout
LISTING=$(unzip -Z1 "$ZIP")
grep -qx "$NAME/module.json" <<<"$LISTING" || { echo "::error::$NAME/module.json missing from zip" >&2; exit 1; }
if grep -qE "(^|/)(node_modules|\.git|\.github|tests)/|\.DS_Store$|\.zip$" <<<"$LISTING"; then
  echo "::error::Zip contains files that should not be shipped" >&2
  exit 1
fi
if [[ "$(cut -d/ -f1 <<<"$LISTING" | sort -u | wc -l | tr -d ' ')" != "1" ]]; then
  echo "::error::Zip must have a single top-level folder ($NAME/)" >&2
  exit 1
fi

echo "Packaged $ZIP ($(wc -l <<<"$LISTING" | tr -d ' ') entries)"
[[ -n "${GITHUB_OUTPUT:-}" ]] && { echo "zip=$ZIP"; echo "version=$VERSION"; echo "name=$NAME"; } >> "$GITHUB_OUTPUT"
exit 0
