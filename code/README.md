# Yega GitHub API

APIs PHP completas para integración con GitHub API específicamente diseñadas para el ecosistema Yega.

## Características

- ✅ **Integración completa con GitHub API** - Manejo de repositorios, issues, pull requests y commits
- ✅ **Sincronización automática** - Sync programable de los 5 repositorios Yega
- ✅ **Rate limiting inteligente** - Gestión automática de límites de GitHub API
- ✅ **Cache Redis** - Optimización de rendimiento con cache distribuido
- ✅ **Arquitectura MVC** - Estructura limpia y mantenible
- ✅ **PSR-4 Autoloading** - Cumple estándares PHP modernos
- ✅ **Middleware robusto** - Rate limiting, cache y manejo de errores
- ✅ **Endpoints REST** - API RESTful completa con routing
- ✅ **Logging detallado** - Monitoreo y debug completo

## Repositorios Yega Soportados

1. **yega-core** - Núcleo principal del ecosistema
2. **yega-dashboard** - Dashboard y panel de control
3. **yega-api** - APIs y servicios backend
4. **yega-frontend** - Aplicación frontend principal
5. **yega-mobile** - Aplicación móvil

## Instalación

### Requisitos

- PHP 8.1+
- Composer
- Redis
- GitHub Personal Access Token

### Configuración

1. **Instalar dependencias:**
```bash
composer install
```

2. **Configurar variables de entorno:**
```bash
cp .env.example .env
# Editar .env con tus credenciales
```

3. **Configurar GitHub Token:**
```env
GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxx
GITHUB_OWNER=yega
```

4. **Configurar Redis:**
```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
```

## Estructura del Proyecto

```
code/
├── src/
│   ├── Controllers/          # Controladores MVC
│   │   ├── RepositoryController.php
│   │   ├── IssueController.php
│   │   ├── PullRequestController.php
│   │   └── SyncController.php
│   ├── Services/             # Servicios principales
│   │   ├── GitHubService.php
│   │   └── RateLimitService.php
│   ├── Middleware/           # Middleware HTTP
│   │   ├── RateLimitMiddleware.php
│   │   └── CacheMiddleware.php
│   ├── Config/               # Configuración
│   │   └── GitHubConfig.php
│   ├── Exceptions/           # Excepciones personalizadas
│   │   ├── GitHubApiException.php
│   │   └── RateLimitException.php
│   ├── Routing/              # Sistema de rutas
│   │   └── ApiRoutes.php
│   ├── Utils/                # Utilidades
│   │   ├── ResponseFormatter.php
│   │   └── YegaRepositories.php
│   └── bootstrap.php         # Inicialización de la aplicación
├── index.php                 # Punto de entrada principal
├── composer.json             # Dependencias y autoload
└── .env.example             # Plantilla de configuración
```

## API Endpoints

### Repositorios

- `GET /api/repositories` - Lista todos los repositorios Yega
- `GET /api/repositories/{repo}` - Detalles de un repositorio
- `GET /api/repositories/{repo}/stats` - Estadísticas del repositorio
- `GET /api/repositories/{repo}/commits` - Commits recientes

### Issues

- `GET /api/repositories/{repo}/issues` - Lista issues con filtros
- `POST /api/repositories/{repo}/issues` - Crear nuevo issue
- `PUT /api/repositories/{repo}/issues/{number}` - Actualizar issue
- `GET /api/repositories/{repo}/issues/summary` - Resumen de issues

### Pull Requests

- `GET /api/repositories/{repo}/pulls` - Lista pull requests
- `GET /api/repositories/{repo}/pulls/{number}` - Detalles de PR
- `GET /api/repositories/{repo}/pulls/{number}/files` - Archivos modificados
- `GET /api/repositories/{repo}/pulls/{number}/commits` - Commits del PR
- `GET /api/repositories/{repo}/pulls/summary` - Resumen de PRs

### Sincronización

- `POST /api/sync` - Sincronización completa de todos los repos
- `POST /api/sync/incremental` - Sincronización incremental
- `GET /api/sync/status` - Estado actual de sincronización
- `GET /api/sync/history` - Historial de sincronizaciones
- `POST /api/sync/schedule` - Programar sincronización automática

### Utilidades

- `GET /api/health` - Health check de la API

## Ejemplos de Uso

### Obtener repositorios Yega

```bash
curl -X GET "http://localhost/api/repositories" \
  -H "Content-Type: application/json"
```

### Crear un issue

```bash
curl -X POST "http://localhost/api/repositories/yega-core/issues" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Bug en la funcionalidad X",
    "body": "Descripción detallada del problema...",
    "labels": ["bug", "priority-high"]
  }'
```

### Sincronizar todos los repositorios

```bash
curl -X POST "http://localhost/api/sync" \
  -H "Content-Type: application/json"
```

### Obtener estadísticas de un repositorio

```bash
curl -X GET "http://localhost/api/repositories/yega-dashboard/stats" \
  -H "Content-Type: application/json"
```

## Características Avanzadas

### Rate Limiting

La API implementa rate limiting automático que:
- Monitorea los límites de GitHub API
- Implementa backoff automático cuando se acerca al límite
- Cachea responses para reducir requests
- Proporciona headers informativos sobre el estado

### Cache Inteligente

- **Repositorios**: Cache de 10 minutos
- **Estadísticas**: Cache de 1 hora
- **Sincronización**: Cache de 1 minuto
- **Default**: Cache de 5 minutos

### Sincronización Automática

Sistema de sincronización que:
- Sincroniza los 5 repositorios Yega automáticamente
- Soporta sincronización completa e incremental
- Mantiene historial de sincronizaciones
- Permite programación de tareas
- Implementa locks para evitar conflictos

### Logging y Monitoreo

- Logs estructurados con contexto completo
- Diferentes niveles de log (INFO, WARNING, ERROR)
- Métricas de performance automáticas
- Tracking de rate limits y cache hits

## Manejo de Errores

La API maneja elegantemente:
- **Rate limit exceeded** - Retorna tiempo de espera
- **GitHub API errors** - Propaga errores con contexto
- **Network timeouts** - Reintentos automáticos
- **Invalid repositories** - Validación de repos Yega
- **Authentication errors** - Verificación de tokens

## Seguridad

- Validación estricta de parámetros
- Sanitización de inputs
- Rate limiting por IP
- CORS configurado apropiadamente
- Logs de seguridad detallados

## Performance

- Cache Redis distribuido
- Paginación automática de GitHub API
- Lazy loading de datos
- Optimizaciones específicas para repos Yega
- Conexiones HTTP persistentes

## Desarrollo

### Ejecutar tests

```bash
composer test
```

### Análisis de código

```bash
composer analyze
```

### Verificar estilo de código

```bash
composer cs-check
```

## Monitoreo

### Métricas disponibles

- Rate limit status de GitHub
- Cache hit/miss ratios
- Tiempos de respuesta de endpoints
- Frecuencia de sincronizaciones
- Errores por tipo y endpoint

### Health checks

```bash
curl http://localhost/api/health
```

## Contribución

1. Fork del repositorio
2. Crear branch de feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit de cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push al branch (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Licencia

Este proyecto está bajo la licencia MIT. Ver `LICENSE` para más detalles.

## Soporte

Para soporte técnico:
- Crear issue en GitHub
- Documentación en `/docs`
- Logs en `/logs`

---

**Desarrollado específicamente para el ecosistema Yega** 🚀