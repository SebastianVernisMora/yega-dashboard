
# mcp-orchestrator

Orquestador multiagente para unificar herramientas vía **Model Context Protocol (MCP)** y exponer un **bus único de tools** para Gemini, Qwen (LLXPRT), Blackbox, Codex/Qodo y Jules.

## Objetivo
- Homologar el acceso a repositorios, FS, HTTP, y futuros servers (DB/Search).
- Orquestar pipelines: **Generar (Qwen)** → **Validar (Codex CLI)** → **Actualizar PR/Issues/Project V2 (Qodo)**.
- Mantener **trazabilidad**, **seguridad** y **documentación viva** (agents.md, handoff, USO-IA.md).

## Estructura
```
mcp-orchestrator/
  src/orchestrator/
    flows/
      gen_validate_qodo.py
    servers/
      github.server.json
      filesystem.server.json
      http.server.json
    adapters/
      mcp_client.py
    utils/
      logging.py
  scripts/
    assemble-context.sh
    qwen-run.sh
    codex-validate.sh
    qodo-update.sh
  examples/playbooks/
    example-feature.md
  repo-templates/
    agents.md
    .blackbox
    GEMINI.md
    LLXPRT.md
    USO-IA.md
    .env.example
  .github/workflows/codex-validate.yml
  requirements.txt
  LICENSE
  README.md
```

## Uso rápido
1) Ajusta `src/orchestrator/servers/*.server.json` con credenciales y scopes mínimos.
2) Usa `scripts/assemble-context.sh` para preparar contexto de repo.
3) Ejecuta `scripts/qwen-run.sh playbooks/<feature>.md` (en tu repo objetivo).
4) Ejecuta `scripts/codex-validate.sh` para validar.
5) Si OK, `scripts/qodo-update.sh` para PR/Issues/Project V2.

> Nota: Este repositorio publica **plantillas y scripts**; integra los archivos de `repo-templates/` en cada repo destino.
