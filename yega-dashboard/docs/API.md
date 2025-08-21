# API del Dashboard Yega

## Autenticación

Todos los endpoints requieren que el sistema tenga configurado un token válido de GitHub en las variables de entorno.

## Endpoints

### Estadísticas Generales

#### GET /api/overview

Retorna las métricas generales del ecosistema Yega.

**Respuesta de ejemplo:**
```json
{
  "total_repositories": 5,
  "open_issues": 23,
  "open_pull_requests": 8,
  "recent_commits": 45,
  "most_active_repo": {
    "name": "Yega-API",
    "full_name": "SebastianVernisMora/Yega-API",
    "commit_count": 28
  },
  "daily_activity": [
    {"day": "2025-08-10", "commits": 5},
    {"day": "2025-08-11", "commits": 8}
  ]
}
```

### Repositorios

#### GET /api/repositories

Lista todos los repositorios monitoreados con estadísticas básicas.

**Parámetros de consulta:**
- `language` (opcional): Filtrar por lenguaje
- `sort` (opcional): `stars`, `forks`, `updated_at`
- `order` (opcional): `asc`, `desc`

**Respuesta de ejemplo:**
```json
[
  {
    "id": 1,
    "name": "Yega-Ecosistema",
    "owner": "SebastianVernisMora",
    "full_name": "SebastianVernisMora/Yega-Ecosistema",
    "description": "Arquitectura principal del ecosistema Yega",
    "language": "PHP",
    "stars": 15,
    "forks": 3,
    "open_issues": 5,
    "open_prs": 2,
    "recent_commits": 12,
    "created_at": "2025-01-15T10:30:00Z",
    "updated_at": "2025-08-17T14:22:00Z"
  }
]
```

#### GET /api/repository/{id}

Detalles completos de un repositorio específico incluyendo actividad reciente.

**Parámetros de ruta:**
- `id`: ID del repositorio

**Respuesta de ejemplo:**
```json
{
  "repository": {
    "id": 1,
    "name": "Yega-API",
    "full_name": "SebastianVernisMora/Yega-API",
    "description": "API backend del sistema Yega",
    "language": "PHP",
    "stars": 8,
    "forks": 2
  },
  "stats": {
    "open_issues": 3,
    "closed_issues": 15,
    "open_prs": 1,
    "closed_prs": 12,
    "total_commits": 87
  },
  "recent_commits": [
    {
      "sha": "a1b2c3d4",
      "message": "Implementar endpoint de autenticación",
      "author": "SebastianVernisMora",
      "date": "2025-08-17T12:00:00Z"
    }
  ],
  "recent_issues": [
    {
      "number": 23,
      "title": "Optimizar consultas de base de datos",
      "state": "open",
      "author": "contributor1",
      "created_at": "2025-08-16T09:15:00Z"
    }
  ]
}
```

### Sincronización

#### POST /api/sync/{id}

Sincroniza un repositorio específico con GitHub.

**Parámetros de ruta:**
- `id`: ID del repositorio

**Respuesta de ejemplo:**
```json
{
  "repository_id": 1,
  "issues_synced": 15,
  "prs_synced": 8,
  "commits_synced": 25
}
```

#### POST /api/sync/all

Sincroniza todos los repositorios configurados.

**Respuesta de ejemplo:**
```json
{
  "SebastianVernisMora/Yega-Ecosistema": {
    "repository_id": 1,
    "issues_synced": 10,
    "prs_synced": 5,
    "commits_synced": 15
  },
  "SebastianVernisMora/Yega-API": {
    "repository_id": 2,
    "issues_synced": 8,
    "prs_synced": 3,
    "commits_synced": 20
  }
}
```

### Estadísticas

#### GET /api/stats/languages

Distribución de lenguajes de programación en el ecosistema.

**Respuesta de ejemplo:**
```json
[
  {
    "language": "PHP",
    "repo_count": 3,
    "total_stars": 45
  },
  {
    "language": "JavaScript",
    "repo_count": 2,
    "total_stars": 28
  }
]
```

#### GET /api/stats/activity

Tendencias de actividad de commits en el tiempo.

**Parámetros de consulta:**
- `days` (opcional): Número de días atrás (por defecto: 30)

**Respuesta de ejemplo:**
```json
[
  {
    "day": "2025-08-10",
    "commits": 12,
    "active_repos": 3
  },
  {
    "day": "2025-08-11",
    "commits": 8,
    "active_repos": 2
  }
]
```

## Manejo de Errores

### Códigos de Estado HTTP

- `200 OK`: Operación exitosa
- `404 Not Found`: Recurso no encontrado
- `429 Too Many Requests`: Rate limit excedido
- `500 Internal Server Error`: Error interno del servidor

### Formato de Errores

```json
{
  "error": "Descripción del error",
  "trace": "Stack trace (solo en modo debug)"
}
```

### Errores Comunes

#### Rate Limit Excedido
```json
{
  "error": "Límite de rate limit alcanzado. Espera 1200 segundos."
}
```

#### Repositorio No Encontrado
```json
{
  "error": "Repositorio no encontrado"
}
```

#### Token de GitHub Inválido
```json
{
  "error": "GITHUB_TOKEN no configurado en las variables de entorno"
}
```

## Rate Limiting

El sistema implementa rate limiting para proteger contra el abuso de la API de GitHub:

- **Límite**: 5000 requests por hora (configurable)
- **Ventana**: 1 hora
- **Comportamiento**: Rechaza requests cuando se alcanza el límite
- **Reset**: Automático cada hora

## Ejemplos de Uso

### Curl

```bash
# Obtener estadísticas generales
curl -X GET http://localhost:8000/api/overview

# Sincronizar todos los repositorios
curl -X POST http://localhost:8000/api/sync/all

# Obtener detalles de un repositorio
curl -X GET http://localhost:8000/api/repository/1

# Obtener actividad de los últimos 7 días
curl -X GET "http://localhost:8000/api/stats/activity?days=7"
```

### JavaScript (Axios)

```javascript
// Obtener repositorios
const repositories = await axios.get('/api/repositories');

// Sincronizar repositorio específico
const result = await axios.post('/api/sync/1');

// Obtener estadísticas de lenguajes
const languages = await axios.get('/api/stats/languages');
```

## Consideraciones de Rendimiento

- **Caching**: Los datos se almacenan localmente para reducir llamadas a GitHub
- **Paginación**: Los endpoints limitan automáticamente los resultados
- **Lazy Loading**: Los datos pesados se cargan bajo demanda
- **Batch Processing**: La sincronización se procesa en lotes

## Webhooks (Futuro)

En futuras versiones se planea implementar webhooks de GitHub para actualización en tiempo real:

```
POST /webhooks/github
```

Esto permitirá actualizaciones automáticas cuando ocurran eventos en los repositorios.
