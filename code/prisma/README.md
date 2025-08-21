# Schema de Base de Datos - Dashboard Yega

Este directorio contiene el schema de Prisma y las migraciones para el dashboard de análisis del ecosistema Yega.

## Estructura del Schema

### Modelos Principales

#### **Repository**
Modelo central que almacena información de los repositorios del ecosistema Yega:
- **Repositorios específicos incluidos:**
  - `SebastianVernisMora/Yega-Ecosistema` (CORE_SYSTEM)
  - `SebastianVernisMora/Yega-API` (API)
  - `SebastianVernisMora/Yega-Tienda` (WEB_APP)
  - `SebastianVernisMora/Yega-Repartidor` (MOBILE_APP)
  - `SebastianVernisMora/Yega-Cliente` (MOBILE_APP)

- **Campos principales:**
  - Metadatos de GitHub (stars, forks, issues, etc.)
  - Información técnica (lenguaje, topics, licencia)
  - Análisis del ecosistema (rol, dependencias, stack tecnológico)
  - Métricas de salud y actividad

#### **Issue**
Gestión de issues de los repositorios:
- Estados: OPEN, CLOSED
- Prioridades: CRITICAL, HIGH, MEDIUM, LOW
- Análisis de categorización y estimación de horas
- Flag para issues relacionados con el ecosistema

#### **PullRequest**
Seguimiento de pull requests:
- Estados: OPEN, CLOSED, MERGED
- Complejidad: SIMPLE, MODERATE, COMPLEX, CRITICAL
- Estadísticas de cambios (adiciones, eliminaciones, archivos)
- Análisis de impacto y tipo (feature, bugfix, hotfix)

#### **Commit**
Registro detallado de commits:
- Información de autor y committer
- Estadísticas de cambios
- Tipos: FEATURE, BUGFIX, HOTFIX, REFACTOR, DOCS, etc.
- Análisis de breaking changes y scope de impacto

#### **Contributor**
Perfiles de contribuidores:
- Información de GitHub (bio, empresa, ubicación)
- Estadísticas públicas (repos, seguidores)
- Análisis del rol en el ecosistema
- Áreas de expertise y lenguajes principales

#### **RepositoryContributor**
Relación many-to-many entre repositorios y contribuidores:
- Estadísticas de contribución por repositorio
- Tipos: OWNER, MAINTAINER, CONTRIBUTOR, COLLABORATOR, OCCASIONAL
- Fechas de primera y última contribución

#### **ReadmeContent**
Análisis de documentación README:
- Contenido completo y metadatos
- Análisis de completitud (instalación, uso, ejemplos, API)
- Métricas de calidad (legibilidad, completitud)
- Enlaces internos y externos

### Enums Definidos

```prisma
ProjectType: CORE_SYSTEM, API, WEB_APP, MOBILE_APP, LIBRARY, TOOL, DOCUMENTATION, OTHER
IssueState: OPEN, CLOSED
IssuePriority: CRITICAL, HIGH, MEDIUM, LOW
PullRequestState: OPEN, CLOSED, MERGED
PullRequestComplexity: SIMPLE, MODERATE, COMPLEX, CRITICAL
CommitType: FEATURE, BUGFIX, HOTFIX, REFACTOR, DOCS, STYLE, TEST, CHORE, MERGE, REVERT, INITIAL
ContributionType: OWNER, MAINTAINER, CONTRIBUTOR, COLLABORATOR, OCCASIONAL
```

## Uso

### Configuración
1. Configurar `DATABASE_URL` en el archivo `.env`
2. Ejecutar `npx prisma migrate deploy` para aplicar migraciones
3. Ejecutar `npx prisma generate` para generar el cliente

### Consultas Ejemplo

```typescript
// Obtener todos los repositorios del ecosistema Yega
const yegaRepos = await prisma.repository.findMany({
  where: {
    fullName: {
      startsWith: 'SebastianVernisMora/Yega-'
    }
  },
  include: {
    contributors: true,
    issues: true,
    pullRequests: true,
    readmeContent: true
  }
});

// Análisis de salud de repositorios
const healthAnalysis = await prisma.repository.findMany({
  select: {
    name: true,
    healthScore: true,
    isActive: true,
    hasTests: true,
    hasDocumentation: true,
    hasCI: true
  },
  orderBy: {
    healthScore: 'desc'
  }
});

// Contribuidores más activos del ecosistema
const topContributors = await prisma.contributor.findMany({
  include: {
    repositories: {
      include: {
        repository: {
          select: {
            name: true,
            projectType: true
          }
        }
      }
    }
  },
  where: {
    repositories: {
      some: {
        repository: {
          fullName: {
            startsWith: 'SebastianVernisMora/Yega-'
          }
        }
      }
    }
  }
});
```

## Migraciones

La migración inicial `20250817161748_init_yega_dashboard` crea toda la estructura de base de datos necesaria para el dashboard.

### Futuras Migraciones
Para crear nuevas migraciones:
```bash
npx prisma migrate dev --name nombre_de_la_migracion
```

## Características Especiales

- **Soft deletes**: No implementados, se usan CASCADE deletes
- **Timestamps**: Todos los modelos tienen timestamps del sistema y de GitHub
- **Arrays**: Uso extensivo de arrays PostgreSQL para topics, dependencias, etc.
- **Enums**: Tipado fuerte para estados y categorías
- **Análisis automatizado**: Campos preparados para análisis de IA/ML
- **Escalabilidad**: Índices optimizados para consultas frecuentes

## Mantenimiento

- Los campos `syncedAt` indican la última sincronización con GitHub
- Los campos `healthScore` pueden ser calculados por procesos automatizados
- La estructura permite análisis evolutivo del ecosistema Yega
