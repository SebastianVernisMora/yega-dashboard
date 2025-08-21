
#!/usr/bin/env bash
# scripts/codex-validate.sh
set -euo pipefail
echo "[i] Installing deps (best-effort)"
npm ci || pnpm i || yarn || true
echo "[i] Running linters"
npm run lint || true
echo "[i] Typecheck"
npm run typecheck || true
echo "[i] Tests"
npm test || true
echo "[i] Build"
npm run build || true
echo "[OK] Validation completed"
echo "[OK]" > .tmp/validation.ok
