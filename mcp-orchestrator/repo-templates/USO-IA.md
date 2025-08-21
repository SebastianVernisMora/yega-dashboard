
# USO-IA.md — Manual de operación multiagente

1) Escribir playbook (`/playbooks/<feature>.md`) con objetivos y criterios.
2) Ejecutar Qwen: `./scripts/qwen-run.sh playbooks/<feature>.md`.
3) Validar con Codex: `./scripts/codex-validate.sh`.
4) Si OK, actualizar con Qodo: `./scripts/qodo-update.sh`.
5) Documentar handoff y actualizar `agents.md` si aplica.
