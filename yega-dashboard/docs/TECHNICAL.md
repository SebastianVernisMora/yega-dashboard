# Documentación Técnica - Dashboard Yega

## Arquitectura del Sistema

### Stack Tecnológico

**Backend:**
- PHP 8.1+ con arquitectura MVC
- MySQL 8.0+ como base de datos principal
- Prisma ORM para manejo de datos
- Guzzle HTTP para integración con GitHub API
- Monolog para logging

**Frontend:**
- HTML5 + CSS3 (TailwindCSS)
- JavaScript Vanilla ES6+
- Chart.js para visualizaciones
- Axios para comunicación HTTP

**Infraestructura:**
- Servidor web PHP integrado (desarrollo)
- GitHub API v3 para sincronización
- Sistema de caché local para rate limiting

### Estructura de Directorios

```
yega-dashboard/
├── src/                    # Código PHP backend
│   ├── Config/            # Configuraciones
│   ├── Controllers/       # Controladores MVC
│   ├── Services/          # Lógica de negocio
│   └── Models/            # Modelos de datos (Prisma)
├── public/                # Frontend y punto de entrada
│   ├── css/              # Estilos
│   ├── js/               # JavaScript
│   ├── index.html        # Página principal
│   └── index.php         # Router PHP
├── config/                # Archivos de configuración
├── database/              # Schema y migraciones
│   ├── schema.prisma     # Definición de base de datos
│   └── migrations/       # Scripts de migración
├── docs/                  # Documentación
├── cache/                 # Caché temporal
├── logs/                  # Archivos de log
├── vendor/                # Dependencias PHP
└── node_modules/          # Dependencias Node.js
```

## Base de Datos

### Modelo de Datos

#### Tabla: repositories
- **id**: Identificador único
- **name**: Nombre del repositorio
- **owner**: Propietario del repositorio
- **full_name**: Nombre completo (owner/name)
- **description**: Descripción del repositorio
- **language**: Lenguaje principal
- **stars**: Número de estrellas
- **forks**: Número de forks
- **created_at/updated_at**: Timestamps

#### Tabla: issues
- **id**: Identificador único
- **repo_id**: Referencia al repositorio
- **number**: Número del issue en GitHub
- **title**: Título del issue
- **state**: Estado (open/closed)
- **body**: Contenido del issue
- **author**: Autor del issue
- **labels**: Labels en formato JSON
- **created_at/updated_at**: Timestamps

#### Tabla: pull_requests
- **id**: Identificador único
- **repo_id**: Referencia al repositorio
- **number**: Número del PR en GitHub
- **title**: Título del PR
- **state**: Estado (open/closed/merged)
- **body**: Descripción del PR
- **author**: Autor del PR
- **merged_at**: Fecha de merge (si aplica)
- **created_at/updated_at**: Timestamps

#### Tabla: commits
- **id**: Identificador único
- **repo_id**: Referencia al repositorio
- **sha**: Hash único del commit
- **message**: Mensaje del commit
- **author**: Autor del commit
- **date**: Fecha del commit
- **created_at/updated_at**: Timestamps

#### Tabla: readme_content
- **id**: Identificador único
- **repo_id**: Referencia al repositorio (única)
- **content**: Contenido del README
- **last_updated**: Última actualización del contenido
- **created_at/updated_at**: Timestamps

### Relaciones

- **repositories** 1:N **issues**
- **repositories** 1:N **pull_requests** 
- **repositories** 1:N **commits**
- **repositories** 1:1 **readme_content**

Todas las relaciones incluyen CASCADE DELETE para mantener integridad.

## Integración con GitHub API

### Autenticación
- Token personal de GitHub con permisos de lectura
- Headers requeridos: Authorization, Accept, User-Agent

### Endpoints Utilizados

```
GET /repos/{owner}/{repo}              # Datos del repositorio
GET /repos/{owner}/{repo}/issues       # Issues del repositorio
GET /repos/{owner}/{repo}/pulls        # Pull requests
GET /repos/{owner}/{repo}/commits      # Commits recientes
GET /repos/{owner}/{repo}/readme       # Contenido README
```

### Rate Limiting
- Límite: 5000 requests/hora por token
- Implementación: Sistema de caché local
- Manejo: Queue con reintentos automáticos

## API REST Interna

### Endpoints Disponibles

#### GET /api/overview
Retorna estadísticas generales del ecosistema.

**Respuesta:**
```json
{
  "total_repositories": 5,
  "open_issues": 23,
  "open_pull_requests": 8,
  "recent_commits": 45,
  "most_active_repo": {...},
  "daily_activity": [...]
}
```

#### GET /api/repositories
Lista todos los repositorios con estadísticas básicas.

#### GET /api/repository/{id}
Detalles completos de un repositorio específico.

#### POST /api/sync/{id}
Sincroniza un repositorio específico con GitHub.

#### POST /api/sync/all
Sincroniza todos los repositorios configurados.

#### GET /api/stats/languages
Distribución de lenguajes de programación.

#### GET /api/stats/activity?days=30
Tendencias de actividad por período.

### Códigos de Respuesta

- **200**: Operación exitosa
- **404**: Recurso no encontrado
- **500**: Error interno del servidor
- **429**: Rate limit excedido

## Sincronización de Datos

### Proceso de Sincronización

1. **Validación de Rate Limit**: Verificar disponibilidad de requests
2. **Obtención de Datos**: Llamadas a GitHub API
3. **Procesamiento**: Normalización y validación
4. **Persistencia**: Inserción/actualización en BD
5. **Logging**: Registro de operaciones

### Estrategia de Actualización

- **Repositorios**: Upsert basado en `full_name`
- **Issues/PRs**: Upsert basado en `repo_id + number`
- **Commits**: Insert ignore basado en `sha`
- **README**: Upsert basado en `repo_id`

### Manejo de Errores

- **Network Errors**: Reintentos con backoff exponencial
- **API Errors**: Logging y continuación con siguiente repo
- **Rate Limit**: Pausa automática hasta reset
- **Database Errors**: Rollback y notificación

## Frontend

### Arquitectura

- **Patrón**: MVC en JavaScript
- **Estado**: Gestión centralizada en clase principal
- **Comunicación**: API REST con Axios
- **UI**: Componentes reutilizables

### Características

- **Dashboard Responsivo**: Adaptable a móviles y desktop
- **Actualización en Tiempo Real**: Auto-refresh cada 5 minutos
- **Visualizaciones**: Gráficos interactivos con Chart.js
- **Notificaciones**: Sistema de toasts para feedback
- **Modal System**: Detalles expandidos de repositorios

### Componentes Principales

1. **StatsCards**: Métricas generales del ecosistema
2. **ActivityChart**: Gráfico de actividad temporal
3. **LanguagesChart**: Distribución de lenguajes
4. **RepositoriesList**: Tabla interactiva de repositorios
5. **RepositoryModal**: Vista detallada de repositorio

## Configuración

### Variables de Entorno

```env
# Base de datos
DATABASE_URL="mysql://user:pass@host:port/database"

# GitHub
GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxx"
GITHUB_API_URL="https://api.github.com"

# Aplicación
APP_ENV="development|production"
APP_DEBUG="true|false"
APP_URL="http://localhost:8000"

# Repositorios
REPOSITORIES="owner/repo1,owner/repo2,..."

# Cache
CACHE_TTL=3600
RATE_LIMIT_PER_HOUR=5000
```

### Configuración de Repositorios

Los repositorios se configuran en la variable `REPOSITORIES` separados por comas:

```
SebastianVernisMora/Yega-Ecosistema,
SebastianVernisMora/Yega-API,
SebastianVernisMora/Yega-Tienda,
SebastianVernisMora/Yega-Repartidor,
SebastianVernisMora/Yega-Cliente
```

## Logging

### Niveles de Log

- **INFO**: Operaciones normales
- **WARNING**: Situaciones inesperadas pero manejables
- **ERROR**: Errores que requieren atención
- **DEBUG**: Información detallada para desarrollo

### Archivos de Log

- `logs/github.log`: Interacciones con GitHub API
- `logs/database.log`: Operaciones de base de datos
- `logs/application.log`: Eventos generales de la aplicación

## Monitoreo

### Métricas Clave

- **Rate Limit Usage**: Requests restantes por hora
- **Sync Success Rate**: Porcentaje de sincronizaciones exitosas
- **Response Times**: Tiempos de respuesta de la API
- **Error Rates**: Frecuencia de errores por tipo

### Alertas

- Rate limit < 500 requests
- Sync failures > 10%
- Database connection errors
- GitHub API downtime

## Seguridad

### Medidas Implementadas

- **Token Security**: GitHub token en variables de entorno
- **Input Validation**: Sanitización de datos de entrada
- **SQL Injection**: Prepared statements en todas las consultas
- **CORS**: Headers configurados para desarrollo
- **Rate Limiting**: Protección contra abuse

### Recomendaciones

- Rotar tokens de GitHub regularmente
- Implementar HTTPS en producción
- Configurar firewall para base de datos
- Monitoreo de logs de seguridad
- Backup automático de base de datos

## Deployment

### Desarrollo

```bash
composer install
npm install
cp .env.example .env
# Configurar variables de entorno
npx prisma migrate dev
composer run start
```

### Producción

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
# Configurar servidor web (Apache/Nginx)
# Configurar base de datos
# Configurar cron para sync automático
```

### Cron Jobs Recomendados

```bash
# Sincronización cada 30 minutos
*/30 * * * * curl -X POST http://localhost:8000/api/sync/all

# Limpieza de logs diaria
0 2 * * * find /path/logs -name "*.log" -mtime +7 -delete
```
