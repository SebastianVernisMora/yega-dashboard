
# agents.md — Orquestación Multiagente

## Roles
- **Gemini**: análisis de codebase, diseño técnico, prepara prompts para Qwen.
- **Qwen (LLXPRT CLI)**: generación de código a gran escala (one-liner→expansión).
- **Codex CLI**: validación (`lint`, `test`, `typecheck`, `build`).
- **Qodo**: actualización de **PRs, Issues, Project V2** solo tras validación OK.
- **Blackbox**: soporte 911 si fallan rutas estándar.
- **Jules/Codex Web**: cambios grandes/forks; documentación y PR finales.

## Pipeline
1. Gemini redacta `playbooks/<feature>.md`.
2. `scripts/qwen-run.sh playbooks/<feature>.md` → rama `feat/<feature>`.
3. `scripts/codex-validate.sh` → si OK, pasa a Qodo.
4. `scripts/qodo-update.sh` → PR + Issues + Project V2.
5. Actualizar `handoff/<feature>.md` y `USO-IA.md`.

## Convenciones
- Ramas: `feat/<slug>`, `fix/<slug>`, `chore/<slug>`.
- Commits: Conventional Commits.
- Checklists en PR: `CHECKS.md` autogenerado por Codex.
