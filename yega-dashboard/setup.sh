#!/bin/bash

# Script de inicialización del Dashboard Yega
echo "🚀 Inicializando Dashboard Yega..."

# Verificar PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP no está instalado"
    exit 1
fi

# Verificar Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer no está instalado"
    exit 1
fi

# Verificar Node.js
if ! command -v node &> /dev/null; then
    echo "❌ Node.js no está instalado"
    exit 1
fi

# Verificar MySQL
if ! command -v mysql &> /dev/null; then
    echo "⚠️  MySQL no está disponible en PATH"
fi

echo "✅ Dependencias verificadas"

# Instalar dependencias PHP
echo "📦 Instalando dependencias PHP..."
composer install

# Instalar dependencias Node.js
echo "📦 Instalando dependencias Node.js..."
npm install

# Verificar archivo .env
if [ ! -f .env ]; then
    echo "📝 Creando archivo .env..."
    cp .env.example .env
    echo "⚠️  Por favor configura las variables en .env antes de continuar"
else
    echo "✅ Archivo .env existe"
fi

# Crear directorios necesarios
echo "📁 Creando directorios..."
mkdir -p cache logs

# Construir assets
echo "🔨 Construyendo assets..."
npm run build 2>/dev/null || echo "⚠️  Build de assets falló - ejecuta 'npm run build' manualmente"

echo ""
echo "✅ Inicialización completada!"
echo ""
echo "Pasos siguientes:"
echo "1. Configura las variables en .env"
echo "2. Crea la base de datos MySQL"
echo "3. Ejecuta las migraciones: npx prisma migrate dev"
echo "4. Inicia el servidor: composer run start"
echo ""
echo "Dashboard disponible en: http://localhost:8000"
