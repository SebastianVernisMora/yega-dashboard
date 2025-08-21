<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$repo = $_GET['repo'] ?? '';

// Simulación de contenido README por repositorio
$readme_content = [
    'yega-core' => <<<'EOD'
# Yega Core

🚀 **Núcleo principal del ecosistema Yega**

## Descripción

Yega Core es el componente fundamental que proporciona las funcionalidades base para todo el ecosistema Yega. Incluye utilidades comunes, sistema de eventos, manejo de estado y arquitectura modular.

## Características

- ⚙️ **Arquitectura modular**: Sistema de plugins extensible
- 📦 **Gestión de estado**: Store centralizado con soporte para async
- 🔔 **Sistema de eventos**: Event bus robusto y eficiente
- 🔧 **Utilidades**: Conjunto completo de herramientas de desarrollo
- 📊 **Performance**: Optimizado para aplicaciones de gran escala

## Instalación

```bash
npm install @yega/core
# o
yarn add @yega/core
```

## Uso Básico

```javascript
import { YegaCore, createStore } from '@yega/core';

// Inicializar Yega Core
const app = new YegaCore({
  plugins: [],
  store: createStore({
    initialState: {}
  })
});

// Usar el sistema de eventos
app.on('user:login', (userData) => {
  console.log('Usuario conectado:', userData);
});

app.emit('user:login', { id: 1, name: 'Juan' });
```

## API Reference

### YegaCore

#### Constructor

```javascript
new YegaCore(options)
```

**Parámetros:**
- `options.plugins` - Array de plugins a cargar
- `options.store` - Instancia del store de estado
- `options.debug` - Activar modo debug

#### Métodos

- `use(plugin)` - Registrar un plugin
- `on(event, callback)` - Escuchar evento
- `emit(event, data)` - Emitir evento
- `getState()` - Obtener estado actual
- `setState(newState)` - Actualizar estado

## Desarrollo

```bash
# Instalar dependencias
npm install

# Ejecutar tests
npm test

# Build para producción
npm run build

# Modo desarrollo
npm run dev
```

## Contribuir

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## Licencia

MIT © 2024 Equipo Yega
EOD,
    'yega-ui' => <<<'EOD'
# Yega UI

🎨 **Biblioteca de componentes UI para Yega**

## Descripción

Yega UI proporciona un conjunto completo de componentes de interfaz de usuario diseñados específicamente para aplicaciones del ecosistema Yega. Construido con TypeScript y optimizado para rendimiento.

## Componentes Disponibles

### Básicos
- 🔲 **Button** - Botones con variantes y estados
- 🏷️ **Badge** - Etiquetas y marcadores
- 📝 **Input** - Campos de entrada con validación
- 📄 **Card** - Contenedores de contenido
- 🎇 **Modal** - Ventanas modales

### Navegación
- 🧭 **Navbar** - Barra de navegación
- 👈 **Sidebar** - Panel lateral
- 🍖 **Breadcrumb** - Migas de pan
- 📋 **Tabs** - Pestañas

### Formularios
- ✅ **Checkbox** - Casillas de verificación
- 🔘 **Radio** - Botones de radio
- 📅 **DatePicker** - Selector de fechas
- 🔽 **Select** - Desplegables

## Instalación

```bash
npm install @yega/ui
# o
yarn add @yega/ui
```

## Uso

```jsx
import { Button, Card, Modal } from '@yega/ui';
import '@yega/ui/dist/styles.css';

function App() {
  return (
    <Card>
      <h2>Mi Aplicación</h2>
      <Button variant="primary" onClick={() => alert('Hola!')}>Saludar</Button>
    </Card>
  );
}
```

## Temas y Personalización

Yega UI soporta temas personalizados mediante CSS custom properties:

```css
:root {
  --yega-primary-color: #3182ce;
  --yega-secondary-color: #4a5568;
  --yega-border-radius: 8px;
  --yega-spacing-unit: 8px;
}
```

## Accesibilidad

Todos los componentes están diseñados siguiendo las guías WCAG 2.1 AA:

- ⌨️ Navegación por teclado completa
- 🔊 Soporte para lectores de pantalla
- 🎨 Contraste adecuado de colores
- 🏷️ ARIA labels apropiados

## Desarrollo

```bash
# Instalar dependencias
npm install

# Storybook para desarrollo
npm run storybook

# Ejecutar tests
npm test

# Build
npm run build
```

## Licencia

MIT © 2024 Equipo Yega
EOD,
    'yega-api' => <<<'EOD'
# Yega API

🚀 **API REST para servicios backend de Yega**

## Descripción

Yega API proporciona una interfaz REST robusta y escalable para las aplicaciones del ecosistema Yega. Construida con Python/FastAPI, incluye autenticación, autorización, validación de datos y documentación automática.

## Características

- 🔐 **Autenticación JWT** - Sistema seguro de tokens
- 🔒 **Autorización basada en roles** - Control granular de permisos
- 📄 **Documentación automática** - Swagger/OpenAPI integrado
- 📋 **Validación de datos** - Pydantic schemas
- 📊 **Rate limiting** - Protección contra abuso
- 🔍 **Logging estructurado** - Monitoreo y debugging

## Instalación

### Con Docker (Recomendado)

```bash
docker-compose up -d
```

### Instalación manual

```bash
# Crear entorno virtual
python -m venv venv
source venv/bin/activate  # Linux/Mac
venv\Scripts\activate     # Windows

# Instalar dependencias
pip install -r requirements.txt

# Configurar variables de entorno
cp .env.example .env

# Ejecutar migraciones
alembic upgrade head

# Iniciar servidor
uvicorn main:app --reload
```

## Endpoints Principales

### Autenticación

```http
POST /auth/login
POST /auth/register
POST /auth/refresh
POST /auth/logout
```

### Usuarios

```http
GET    /users/me
PUT    /users/me
GET    /users/{user_id}
DELETE /users/{user_id}
```

### Proyectos

```http
GET    /projects
POST   /projects
GET    /projects/{project_id}
PUT    /projects/{project_id}
DELETE /projects/{project_id}
```

## Ejemplo de Uso

### Autenticación

```python
import requests

# Login
response = requests.post('http://localhost:8000/auth/login', json={
    'email': 'usuario@ejemplo.com',
    'password': 'mi_password'
})

token = response.json()['access_token']

# Usar token en requests
headers = {'Authorization': f'Bearer {token}'}
user_data = requests.get('http://localhost:8000/users/me', headers=headers)
```

### JavaScript/Frontend

```javascript
// Cliente API
class YegaAPI {
  constructor(baseURL, token = null) {
    this.baseURL = baseURL;
    this.token = token;
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;
    const config = {
      headers: {
        'Content-Type': 'application/json',
        ...(this.token && { 'Authorization': `Bearer ${this.token}` })
      },
      ...options
    };

    const response = await fetch(url, config);
    return response.json();
  }

  async login(email, password) {
    const data = await this.request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password })
    });
    this.token = data.access_token;
    return data;
  }

  async getProjects() {
    return this.request('/projects');
  }
}

// Uso
const api = new YegaAPI('http://localhost:8000');
await api.login('usuario@ejemplo.com', 'password');
const projects = await api.getProjects();
```

## Configuración

Ver `.env.example` para todas las variables disponibles:

```env
# Base de datos
DATABASE_URL=postgresql://user:pass@localhost/yega

# JWT
JWT_SECRET=tu-secreto-super-seguro
JWT_EXPIRATION=30  # minutos

# Redis (opcional, para caching)
REDIS_URL=redis://localhost:6379

# Email (opcional)
SMTP_SERVER=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=tu-email@gmail.com
SMTP_PASSWORD=tu-password
```

## Testing

```bash
# Ejecutar todos los tests
pytest

# Con coverage
pytest --cov=app

# Tests específicos
pytest tests/test_auth.py
```

## Deployment

### Con Docker

```bash
docker build -t yega-api .
docker run -p 8000:8000 yega-api
```

### Con systemd

```bash
# Copiar servicio
sudo cp deploy/yega-api.service /etc/systemd/system/
sudo systemctl enable yega-api
sudo systemctl start yega-api
```

## Licencia

Apache 2.0 © 2024 Equipo Yega
EOD
];

try {
    $content = $readme_content[$repo] ?? '';
    
    if (empty($content)) {
        $content = "# $repo\n\nREADME no disponible para este repositorio.";
    }
    
    $response = [
        'success' => true,
        'content' => $content,
        'repository' => $repo,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error cargando README',
        'error' => $e->getMessage()
    ]);
}
?>