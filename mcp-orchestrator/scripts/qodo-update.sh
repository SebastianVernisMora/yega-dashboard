
#!/usr/bin/env bash
# scripts/qodo-update.sh
set -euo pipefail

: "${BASE_BRANCH:=dev}"
: "${GH_TOKEN:?GH_TOKEN no está seteado}"
: "${GHPROJECT:=}"

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
PR_TITLE="[$CURRENT_BRANCH] Update"
PR_BODY_FILE=".tmp/pr-body.md"
mkdir -p .tmp

if [ ! -f ".tmp/validation.ok" ]; then
  echo "La validación no ha pasado. Aborta." >&2
  exit 1
fi

echo "# CI ✓" > "$PR_BODY_FILE"
echo "" >> "$PR_BODY_FILE"
echo "- Lint/Types/Tests/Build: OK" >> "$PR_BODY_FILE"
echo "- Artefactos: ./artifacts" >> "$PR_BODY_FILE"

# Requiere GitHub CLI (gh)
if gh pr view "$CURRENT_BRANCH" >/dev/null 2>&1; then
  gh pr edit "$CURRENT_BRANCH" --title "$PR_TITLE" --body-file "$PR_BODY_FILE"
else
  gh pr create --fill --base "$BASE_BRANCH" --title "$PR_TITLE" --body-file "$PR_BODY_FILE"
fi

if [ -n "$GHPROJECT" ]; then
  PR_URL=$(gh pr view --json url -q .url)
  gh project item-add --project "$GHPROJECT" --url "$PR_URL" || true
fi
