# Estructura del Proyecto Dashboard Yega

```
yega-dashboard/
│
├── 📝 README.md                      # Documentación principal
├── ⚙️  composer.json                   # Dependencias PHP
├── ⚙️  package.json                    # Dependencias Node.js
├── ⚙️  .env.example                    # Plantilla de variables de entorno
├── ⚙️  .gitignore                      # Archivos ignorados por Git
├── ⚙️  webpack.config.js               # Configuración de Webpack
├── ⚙️  tailwind.config.js              # Configuración de TailwindCSS
├── 🛠️  setup.sh                        # Script de configuración inicial
├── 🚀 deploy.sh                       # Script de deployment
│
├── 📁 src/                          # Backend PHP
│   ├── ⚙️  Config/
│   │   ├── DatabaseConfig.php        # Configuración de base de datos
│   │   └── GitHubConfig.php          # Configuración de GitHub API
│   │
│   ├── 🎮 Controllers/
│   │   └── DashboardController.php   # Controlador principal del dashboard
│   │
│   └── 🔧 Services/
│       ├── GitHubService.php         # Servicio de integración con GitHub
│       └── RateLimitService.php      # Servicio de control de rate limiting
│
├── 🕷️  public/                        # Frontend y punto de entrada
│   ├── 🎨 css/
│   │   └── dashboard.css             # Estilos personalizados
│   │
│   ├── ⚡ js/
│   │   └── dashboard.js              # Lógica del frontend
│   │
│   ├── 🏠 index.html                  # Página principal del dashboard
│   └── 🔀 index.php                   # Router y API PHP
│
├── ⚙️  config/                        # Archivos de configuración
│
├── 🗄️  database/                      # Base de datos
│   ├── schema.prisma               # Schema de Prisma para MySQL
│   └── migrations/
│       └── 001_initial_schema.sql    # Migración inicial
│
└── 📚 docs/                          # Documentación
    ├── TECHNICAL.md                # Documentación técnica detallada
    └── API.md                      # Documentación de la API
```

## Repositorios Configurados

📁 **SebastianVernisMora/Yega-Ecosistema**  
→ Arquitectura y documentación principal del ecosistema

💻 **SebastianVernisMora/Yega-API**  
→ API backend del sistema Yega

🛍️ **SebastianVernisMora/Yega-Tienda**  
→ Aplicación de tienda online

🛥️ **SebastianVernisMora/Yega-Repartidor**  
→ Aplicación para repartidores

📱 **SebastianVernisMora/Yega-Cliente**  
→ Aplicación cliente/usuario final

## Estructura de Base de Datos

### Tablas Principales

#### 📁 repositories
- Información básica de repositorios
- Métricas (stars, forks)
- Metadata (lenguaje, descripción)

#### 📝 issues  
- Issues de GitHub sincronizados
- Estado y metadata
- Labels en formato JSON

#### 🔀 pull_requests
- Pull requests sincronizados
- Estado de merge
- Información de autor

#### ⚙️ commits
- Historial de commits
- Mensajes y autores
- Referencias SHA

#### 📝 readme_content
- Contenido de archivos README
- Tracking de actualizaciones

### Relaciones

```
repositories (1:N) → issues
repositories (1:N) → pull_requests  
repositories (1:N) → commits
repositories (1:1) → readme_content
```

## Flujo de Datos

```
👙 GitHub API → 🔧 GitHubService → 🗄️ MySQL → 🎮 Controller → 🕷️ Frontend
```

### Proceso de Sincronización

1. 🔍 **Verificación Rate Limit** - Control de límites de API
2. 📝 **Obtención de Datos** - Llamadas a endpoints de GitHub
3. ⚙️ **Procesamiento** - Normalización y validación
4. 🗄️ **Persistencia** - Almacenamiento en base de datos
5. 📈 **Logging** - Registro de operaciones

## Funcionalidades del Dashboard

### 📊 Vista General
- Métricas consolidadas del ecosistema
- Contadores de issues, PRs y commits
- Repositorio más activo
- Actividad diaria

### 📁 Gestión de Repositorios
- Lista interactiva de todos los repos
- Sincronización individual o masiva
- Detalles expandidos por repositorio
- Filtros por lenguaje y estado

### 📈 Visualizaciones
- Gráfico de actividad temporal
- Distribución de lenguajes
- Tendencias de commits
- Métricas de issues y PRs

### ⚙️ Administración
- Sistema de notificaciones
- Auto-refresh cada 5 minutos
- Manejo de errores y rate limiting
- Interfaz responsive

## Tecnologías Utilizadas

### Backend
- **PHP 8.1+** - Lenguaje principal
- **MySQL** - Base de datos
- **Prisma** - ORM
- **Guzzle** - Cliente HTTP
- **Monolog** - Logging

### Frontend  
- **TailwindCSS** - Framework CSS
- **Chart.js** - Visualizaciones
- **JavaScript ES6+** - Lógica cliente
- **Axios** - Cliente HTTP

### Herramientas
- **Composer** - Manejo de dependencias PHP
- **npm** - Manejo de dependencias Node.js
- **Webpack** - Bundling de assets
- **Git** - Control de versiones

---

✨ **Dashboard completamente funcional para el monitoreo del ecosistema Yega en GitHub**
