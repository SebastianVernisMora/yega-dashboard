# Dashboard Yega - Scripts de Instalación y Configuración

Este directorio contiene todos los scripts y archivos necesarios para la instalación, configuración y mantenimiento del Dashboard Yega.

## Archivos Incluidos

### Scripts de Instalación

- **`install.sh`** - Script principal de instalación automatizada
- **`configure.sh`** - Script de configuración interactiva
- **`check-system.sh`** - Verificador de requisitos del sistema

### Scripts de Mantenimiento

- **`backup.sh`** - Crear backups completos de la aplicación
- **`restore.sh`** - Restaurar desde backups
- **`update.sh`** - Actualizar a la última versión

### Archivos de Configuración

- **`.env.example`** - Plantilla de variables de entorno
- **`database.sql`** - Scripts SQL para configurar la base de datos
- **`prisma.schema`** - Schema de Prisma para el ORM

## Uso Rápido

### Instalación Completa

```bash
# Hacer ejecutables los scripts
chmod +x code/setup/*.sh

# Ejecutar instalación automatizada
./code/setup/install.sh
```

### Verificar Sistema

```bash
# Verificar requisitos
./code/setup/check-system.sh
```

### Configuración Interactiva

```bash
# Configurar manualmente
./code/setup/configure.sh
```

### Backup y Restauración

```bash
# Crear backup
./code/setup/backup.sh

# Listar backups disponibles
./code/setup/restore.sh

# Restaurar backup específico
./code/setup/restore.sh yega_backup_20250817_120000
```

### Actualización

```bash
# Actualizar a la última versión
./code/setup/update.sh

# Actualizar sin crear backup
./code/setup/update.sh --no-backup

# Actualizar desde rama específica
./code/setup/update.sh --branch development
```

## Configuración Manual

### 1. Variables de Entorno

```bash
# Copiar plantilla
cp code/setup/.env.example .env

# Editar configuración
nano .env
```

### 2. Base de Datos

```bash
# Ejecutar scripts SQL
mysql -u root -p < code/setup/database.sql
```

### 3. Migraciones con Prisma

```bash
# Copiar schema
cp code/setup/prisma.schema prisma/schema.prisma

# Ejecutar migraciones
npx prisma migrate dev --name init
npx prisma generate
```

## Solución de Problemas

### Error de Permisos

```bash
# Si los scripts no tienen permisos
chmod +x code/setup/*.sh
```

### Error de Conexión MySQL

```bash
# Verificar servicio MySQL
sudo systemctl status mysql

# Probar conexión
mysql -u yega_user -p yega_dashboard
```

### Error de GitHub Token

1. Ve a https://github.com/settings/tokens
2. Genera un nuevo token con permisos: `repo`, `user:read`, `user:email`, `read:org`
3. Actualiza el token en `.env`

### Problemas de Dependencias

```bash
# Limpiar e instalar dependencias
rm -rf vendor node_modules
composer install
npm install
```

## Notas de Seguridad

- ⚠️ **Nunca compartas el archivo `.env` con credenciales reales**
- 🔐 **Usa contraseñas seguras y únicas**
- 🔄 **Regenera las claves en producción**
- 💾 **Mantén backups regulares**
- 🔍 **Revisa los logs regularmente**

## Soporte

Para más información, consulta la documentación completa en `docs/INSTALLATION.md`.