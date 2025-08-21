#!/bin/bash

# Script de instalación automática para Yega Dashboard
# Ejecutar como: bash install.sh

echo "🎆 Instalando Yega Dashboard..."

# Verificar PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP no encontrado. Por favor instala PHP 8.0 o superior."
    exit 1
fi

# Verificar MySQL
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL no encontrado. Por favor instala MySQL."
    exit 1
fi

# Verificar Composer
if ! command -v composer &> /dev/null; then
    echo "📦 Instalando Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# Crear directorios necesarios
mkdir -p logs
touch logs/sync.log logs/github.log logs/database.log logs/cron.log
chmod 755 logs/

# Instalar dependencias
echo "📦 Instalando dependencias PHP..."
composer install --no-dev --optimize-autoloader

echo "📦 Instalando dependencias Node.js..."
npm install

# Copiar configuración
if [ ! -f .env ]; then
    cp .env.example .env
    echo "⚙️ Configuración copiada. Por favor edita .env con tus credenciales."
fi

# Generar cliente Prisma
echo "🔧 Generando cliente Prisma..."
npx prisma generate

echo "✅ Instalación completada!"
echo ""
echo "Pasos siguientes:"
echo "1. Editar archivo .env con tus credenciales"
echo "2. Crear base de datos: mysql -u root -p yega_dashboard < database/migration.sql"
echo "3. Ejecutar sincronización inicial: php sync.php"
echo "4. Iniciar servidor: php -S localhost:8000 -t public"
echo ""
echo "🎆 ¡Dashboard listo para usar!"
echo "Accede en: http://localhost:8000"