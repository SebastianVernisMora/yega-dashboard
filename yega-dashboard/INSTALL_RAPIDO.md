# 🚀 INSTALACIÓN RÁPIDA - YEGA DASHBOARD

## 🎯 Configuración Express (5 minutos)

### 1️⃣ Prerequisitos
```bash
# Verificar versiones
php --version    # Necesitas PHP 8.0+
mysql --version  # Necesitas MySQL 8.0+
```

### 2️⃣ Configuración de Base de Datos
```bash
# Crear base de datos
mysql -u root -p
CREATE DATABASE yega_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Ejecutar migración
mysql -u root -p yega_dashboard < database/migration.sql
```

### 3️⃣ Configuración de la App
```bash
# Ejecutar instalación automática
bash install.sh

# O manual:
composer install
npm install
npx prisma generate
```

### 4️⃣ Configurar Variables de Entorno
```bash
# Copiar configuración
cp .env.example .env

# Editar .env (IMPORTANTE)
nano .env
```

**Configuración mínima requerida en .env:**
```env
DATABASE_URL="mysql://tu_usuario:tu_password@localhost:3306/yega_dashboard"
GITHUB_TOKEN=ghp_tu_token_de_github_aqui
```

### 5️⃣ Obtener Token de GitHub
1. Ir a: https://github.com/settings/tokens
2. "Generate new token (classic)"
3. Seleccionar scopes: `repo`, `read:user`
4. Copiar token al archivo `.env`

### 6️⃣ Sincronización Inicial
```bash
# Sincronizar datos de GitHub
php sync.php

# Verificar que funciona
mysql -u root -p yega_dashboard -e "SELECT name, stars FROM repositories;"
```

### 7️⃣ Levantar Servidor
```bash
# Servidor de desarrollo
php -S localhost:8000 -t public

# O usar composer
composer run serve
```

### 8️⃣ ¡Listo!
🎆 **Abrir navegador**: http://localhost:8000

---

## 🎨 Características del Dashboard

- ✨ **Diseño Neón**: Fondo negro + rosa, cian, morado, verde
- 📈 **5 Repositorios**: Ecosistema, API, Tienda, Repartidor, Cliente
- 🐛 **Issues & PRs**: Monitoreo en tiempo real
- 📊 **Visualizaciones**: Gráficos interactivos
- 📝 **README Viewer**: Modal con documentación
- 🔄 **Auto-sync**: Sincronización programada

## 🎆 URLs del Dashboard

- **Principal**: http://localhost:8000
- **API Repos**: http://localhost:8000/api/repositories
- **API Stats**: http://localhost:8000/api/stats/overview

## 🛠️ Troubleshooting Express

### ❌ Error: "Access denied"
```bash
# Verificar credenciales MySQL
mysql -u root -p
GRANT ALL PRIVILEGES ON yega_dashboard.* TO 'tu_usuario'@'localhost';
```

### ❌ Error: "GitHub API rate limit"
- Verificar token válido en `.env`
- Esperar 1 hora o usar nuevo token

### ❌ Dashboard vacío
```bash
# Re-ejecutar sincronización
php sync.php

# Ver logs
tail -f logs/sync.log
```

### ❌ Permisos
```bash
chmod 755 logs/
chown $(whoami) logs/
```

---

🎆 **¡Tu dashboard neón del ecosistema Yega está listo!** 🎆