
# GEMINI.md — Operación

- Modo: análisis profundo + planificación.
- Prepara prompts para Qwen en `playbooks/*.md`.
- Usa MCP (FS/GitHub) para leer codebase y estado CI.

## Comandos (ejemplos)
- `npm run gemini:plan -- <feature>`
- `npm run gemini:review -- <PR#>`

## Variables de entorno (.env.example)
GEMINI_API_KEY="VISIBLE_KEY_ACTIVA"
# GEMINI_API_KEY_ALT_1="COMENTADA_POR_FREE_TIER"
# GEMINI_API_KEY_ALT_2="COMENTADA_POR_FREE_TIER"
REPO_MAIN_BRANCH="dev"
MCP_ENDPOINT="http://localhost:4000"
