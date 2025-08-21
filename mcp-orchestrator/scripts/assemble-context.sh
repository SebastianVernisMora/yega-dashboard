
#!/usr/bin/env bash
# scripts/assemble-context.sh
# Ensambla un prompt con contexto del repo + playbook del feature.
set -euo pipefail

FEATURE_MD="${1:?Uso: assemble-context.sh playbooks/<feature>.md}"
OUT="${2:-/dev/stdout}"

echo "# Contexto del repositorio" > "$OUT"
echo "" >> "$OUT"
echo "## Archivos clave" >> "$OUT"
# Lista archivos relevantes (ajusta a tus necesidades)
git ls-files | grep -E '\.(ts|tsx|js|jsx|py|php|md)$' | head -n 200 | sed 's/^/- /' >> "$OUT"

echo "" >> "$OUT"
echo "## Playbook" >> "$OUT"
echo "" >> "$OUT"
cat "$FEATURE_MD" >> "$OUT"
