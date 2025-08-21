# 🚀 Dashboard Ecosistema Yega

**Frontend completo para monitoreo integral del ecosistema Yega**

## 📋 Descripción

Dashboard web interactivo diseñado para proporcionar una vista unificada de los 5 repositorios principales del ecosistema Yega. Incluye visualización de métricas, issues, pull requests, timeline de actividad y análisis comparativo.

## ✨ Características Principales

### 🎯 Vista General (Overview)
- **Estadísticas en tiempo real**: Repositorios, issues, PRs y contribuidores
- **Cards de repositorios**: Información detallada de cada repo
- **Gráficos interactivos**: Actividad por repositorio e issues vs PRs
- **Métricas de rendimiento**: Scores de actividad y salud del proyecto

### 📊 Sección de Repositorios
- **Lista detallada**: Vista expandida de todos los repositorios
- **Filtros avanzados**: Por nombre, actividad, issues, estrellas
- **Búsqueda en tiempo real**: Búsqueda instantánea por nombre/descripción
- **Modal de detalles**: Vista completa con tabs para overview, issues, PRs y README

### 📈 Analytics Avanzado
- **Gráfico radar comparativo**: Comparación multidimensional entre repos
- **Tendencias temporales**: Actividad de commits, issues y PRs
- **Distribución de issues**: Visualización por repositorio
- **Top contribuidores**: Ranking de desarrolladores más activos

### ⏰ Timeline de Actividad
- **Actividad cronológica**: Stream completo de eventos del ecosistema
- **Filtros temporales**: 7, 30, 90 días
- **Filtros por tipo**: Commits, issues, PRs, releases
- **Scroll infinito**: Carga progressiva de eventos
- **Exportación**: JSON y CSV

### 🎨 Interfaz y UX
- **Design responsive**: Optimizado para desktop, tablet y móvil
- **Navegación intuitiva**: Tabs principales con indicadores de estado
- **Loading states**: Indicadores de carga elegantes
- **Error handling**: Manejo graceful de errores con fallbacks
- **Tema consistente**: Variables CSS personalizables

## 🏗️ Arquitectura Técnica

### Frontend
- **HTML5 semántico** con estructura modular
- **CSS3 moderno** con variables CSS y grid/flexbox
- **JavaScript ES6+** con clases y módulos
- **Chart.js** para visualizaciones interactivas
- **Marked.js** para renderizado de Markdown
- **Font Awesome** para iconografía

### Backend API (PHP)
- **API REST** con endpoints estructurados
- **Headers CORS** configurados
- **Datos simulados** realistas para demo
- **Error handling** con códigos HTTP apropiados
- **Respuestas JSON** estandarizadas

## 📁 Estructura del Proyecto

```
code/public/
├── index.html              # Página principal
├── css/
│   ├── main.css           # Estilos base y componentes
│   └── dashboard.css      # Estilos específicos del dashboard
├── js/
│   ├── main.js           # Funcionalidad principal y navegación
│   ├── dashboard.js      # Gestión de filtros y modales
│   ├── charts.js         # Manejo de gráficos Chart.js
│   └── timeline.js       # Timeline de actividad
├── api/
│   ├── repositories.php  # Datos de repositorios
│   ├── overview.php      # Estadísticas generales
│   ├── issues.php        # Issues por repositorio
│   ├── prs.php          # Pull requests
│   ├── readme.php       # Contenido README
│   ├── timeline.php     # Timeline de actividad
│   └── activity.php     # Datos para gráficos
└── components/           # Directorio para assets adicionales
```

## 🚀 Instalación y Uso

### Requisitos
- Servidor web (Apache/Nginx)
- PHP 7.4+ (para API endpoints)
- Navegador moderno con soporte ES6+

### Instalación

1. **Clonar o copiar archivos**:
   ```bash
   cp -r code/public/ /var/www/html/yega-dashboard/
   ```

2. **Configurar servidor web**:
   ```apache
   # Apache .htaccess
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^api/(.*)$ api/$1.php [L]
   ```

3. **Abrir en navegador**:
   ```
   http://localhost/yega-dashboard/
   ```

### Desarrollo Local

```bash
# Servidor PHP integrado
cd code/public
php -S localhost:8000

# Abrir en navegador
open http://localhost:8000
```

## 🔗 API Endpoints

### Repositorios
- `GET /api/repositories.php` - Lista de repositorios
- `GET /api/overview.php` - Estadísticas generales
- `GET /api/issues.php?repo={name}` - Issues por repositorio
- `GET /api/prs.php?repo={name}` - Pull requests por repositorio
- `GET /api/readme.php?repo={name}` - README de repositorio

### Analytics
- `GET /api/activity.php` - Datos para gráficos
- `GET /api/timeline.php?period={7d|30d|90d}&type={all|commits|issues|prs}` - Timeline

### Parámetros de Timeline
- `period`: 7d, 30d, 90d (default: 30d)
- `type`: all, commits, issues, prs (default: all)
- `page`: número de página (default: 1)
- `limit`: elementos por página (default: 20)

## 🎨 Personalización

### Variables CSS

```css
:root {
  --primary-color: #2d3748;
  --secondary-color: #4a5568;
  --accent-color: #3182ce;
  --success-color: #38a169;
  --warning-color: #ed8936;
  --error-color: #e53e3e;
  --bg-color: #f7fafc;
  --card-bg: #ffffff;
  --border-radius: 8px;
}
```

### Configuración de Charts

```javascript
// En js/charts.js
this.colors = {
    primary: '#3182ce',
    secondary: '#4a5568',
    success: '#38a169',
    // ... más colores
};
```

## 📱 Responsive Design

- **Desktop**: Layout completo con sidebar y múltiples columnas
- **Tablet**: Adaptación de grid y navegación
- **Mobile**: Layout de una columna con navegación colapsible

### Breakpoints

```css
/* Tablet */
@media (max-width: 768px) { ... }

/* Mobile */
@media (max-width: 480px) { ... }
```

## 🔧 Funcionalidades Avanzadas

### Filtros y Búsqueda
- **Búsqueda en tiempo real** en repositorios
- **Filtros múltiples** para issues y PRs
- **Ordenamiento dinámico** por diferentes criterios
- **Persistencia de filtros** durante la sesión

### Exportación de Datos
- **Timeline en CSV/JSON**
- **Datos de repositorios en CSV**
- **Configuración de exportación** personalizable

### Rendimiento
- **Lazy loading** de contenido pesado
- **Caching** de respuestas API
- **Optimización de imágenes** y assets
- **Minificación** (recomendada para producción)

## 🔮 Extensiones Futuras

### Integraciones
- **GitHub API real**: Conexión directa con GitHub
- **Webhooks**: Actualizaciones en tiempo real
- **Slack/Discord**: Notificaciones
- **CI/CD**: Integración con pipelines

### Funcionalidades
- **Autenticación**: Login con GitHub OAuth
- **Configuración personalizada**: Settings por usuario
- **Temas**: Dark mode y temas personalizados
- **PWA**: Aplicación web progresiva

## 🐛 Troubleshooting

### Problemas Comunes

1. **CORS Errors**:
   ```php
   // Añadir en cada endpoint PHP
   header('Access-Control-Allow-Origin: *');
   ```

2. **PHP Errors**:
   ```bash
   # Verificar logs
   tail -f /var/log/apache2/error.log
   ```

3. **JavaScript Errors**:
   ```javascript
   // Verificar consola del navegador
   console.log('Debug info:', data);
   ```

### Performance

- **Optimizar imágenes**: Usar WebP cuando sea posible
- **Minificar CSS/JS**: Para producción
- **CDN**: Para Chart.js y Font Awesome
- **Caching**: Headers apropiados para assets estáticos

## 📄 Licencia

MIT © 2024 Dashboard Ecosistema Yega

## 👥 Contribución

1. Fork el repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

---

**Desarrollado con ❤️ para el ecosistema Yega**