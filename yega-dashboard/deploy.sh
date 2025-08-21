#!/bin/bash

# Script de deployment para producción
echo "🚀 Desplegando Dashboard Yega en producción..."

# Verificar entorno
if [ "$APP_ENV" != "production" ]; then
    echo "⚠️  APP_ENV debe ser 'production'"
    exit 1
fi

# Instalar dependencias optimizadas
echo "📦 Instalando dependencias de producción..."
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --only=production

# Construir assets
echo "🔨 Construyendo assets para producción..."
npm run build

# Optimizar autoloader
echo "⚡ Optimizando autoloader..."
composer dump-autoload --optimize

# Crear directorios necesarios
mkdir -p cache logs
chmod 755 cache logs

# Configurar permisos
echo "🔒 Configurando permisos..."
chown -R www-data:www-data cache logs
chmod 644 .env

# Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
npx prisma migrate deploy

# Limpiar cache
echo "🧹 Limpiando cache..."
rm -rf cache/*

echo "✅ Deployment completado!"
echo "Dashboard disponible en: $APP_URL"
