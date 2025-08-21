
# src/orchestrator/adapters/mcp_client.py

"""
Cliente MCP minimalista (placeholder). Implementa métodos de conveniencia
para Access a servers como GitHub/FS/HTTP. Completa con tu SDK MCP real.
"""
from typing import Any, Dict

class MCPClient:
    def __init__(self, servers: Dict[str, Dict[str, Any]]):
        self.servers = servers

    def github(self) -> Any:
        return self.servers.get("github")

    def fs(self) -> Any:
        return self.servers.get("filesystem")

    def http(self) -> Any:
        return self.servers.get("http")
