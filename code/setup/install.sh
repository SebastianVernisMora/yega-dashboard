#!/bin/bash

# =============================================================================
# Script de Instalación Automatizada - Dashboard Yega
# Versión: 1.0.0
# Autor: MiniMax Agent
# Descripción: Instalación completa automatizada para Linux/macOS
# =============================================================================

set -e  # Salir en caso de error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Variables globales
PROJECT_NAME="yega-dashboard"
DB_NAME="yega_dashboard"
DB_USER="yega_user"
DB_PASSWORD=""
GITHUB_TOKEN=""
DEV_MODE=false
PROD_MODE=false
SILENT_MODE=false
CONFIG_FILE=""

# Banner de bienvenida
show_banner() {
    echo -e "${PURPLE}"
    cat << "EOF"
┌────────────────────────────────────────────────────────────────────────────────┐
│                    🚀 YEGA DASHBOARD INSTALLER 🚀                     │
│                     Instalación Automatizada v1.0.0                    │
├────────────────────────────────────────────────────────────────────────────────┤
│ • Verificación de requisitos del sistema                              │
│ • Instalación de dependencias PHP y Node.js                           │
│ • Configuración de base de datos MySQL                               │
│ • Setup de variables de entorno                                      │
│ • Migraciones de Prisma                                              │
│ • Compilación de assets frontend                                    │
│ • Inicio del servidor de desarrollo                                  │
└────────────────────────────────────────────────────────────────────────────────┘
EOF
    echo -e "${NC}"
}

# Funciones de logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "\n${CYAN}━━━ $1 ━━━${NC}"
}

# Detectar sistema operativo
detect_os() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        if [ -f /etc/debian_version ]; then
            OS="debian"
            DISTRO=$(lsb_release -si 2>/dev/null || echo "Debian")
        elif [ -f /etc/redhat-release ]; then
            OS="redhat"
            DISTRO="RedHat"
        else
            OS="linux"
            DISTRO="Linux"
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
        DISTRO="macOS"
    else
        OS="unknown"
        DISTRO="Unknown"
    fi
    
    log_info "Sistema detectado: $DISTRO ($OS)"
}

# Verificar si el comando existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Verificar requisitos del sistema
check_requirements() {
    log_step "Verificando requisitos del sistema"
    
    local errors=0
    
    # Verificar PHP
    if command_exists php; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        log_success "PHP encontrado: v$PHP_VERSION"
        
        # Verificar versión mínima
        if php -r "exit(version_compare(PHP_VERSION, '8.1.0', '<') ? 1 : 0);"; then
            log_error "Se requiere PHP 8.1 o superior. Versión actual: $PHP_VERSION"
            ((errors++))
        fi
        
        # Verificar extensiones PHP
        local required_extensions=("curl" "json" "mbstring" "openssl" "pdo" "pdo_mysql" "tokenizer" "xml")
        for ext in "${required_extensions[@]}"; do
            if php -m | grep -q "^$ext$"; then
                log_success "Extensión PHP $ext: instalada"
            else
                log_error "Extensión PHP $ext: NO encontrada"
                ((errors++))
            fi
        done
    else
        log_error "PHP no encontrado. Por favor instala PHP 8.1 o superior."
        ((errors++))
    fi
    
    # Verificar MySQL
    if command_exists mysql; then
        MYSQL_VERSION=$(mysql --version | awk '{print $5}' | sed 's/,//')
        log_success "MySQL encontrado: v$MYSQL_VERSION"
    else
        log_warning "MySQL no encontrado. Se intentará instalar o se puede usar un servidor remoto."
    fi
    
    # Verificar Composer
    if command_exists composer; then
        COMPOSER_VERSION=$(composer --version --no-ansi | awk '{print $3}')
        log_success "Composer encontrado: v$COMPOSER_VERSION"
    else
        log_warning "Composer no encontrado. Se instalará automáticamente."
    fi
    
    # Verificar Node.js
    if command_exists node; then
        NODE_VERSION=$(node --version)
        log_success "Node.js encontrado: $NODE_VERSION"
        
        # Verificar versión mínima
        NODE_MAJOR=$(echo $NODE_VERSION | sed 's/v//' | cut -d. -f1)
        if [ "$NODE_MAJOR" -lt 16 ]; then
            log_error "Se requiere Node.js 16 o superior. Versión actual: $NODE_VERSION"
            ((errors++))
        fi
    else
        log_error "Node.js no encontrado. Por favor instala Node.js 16 o superior."
        ((errors++))
    fi
    
    # Verificar NPM
    if command_exists npm; then
        NPM_VERSION=$(npm --version)
        log_success "NPM encontrado: v$NPM_VERSION"
    else
        log_error "NPM no encontrado."
        ((errors++))
    fi
    
    # Verificar Git
    if command_exists git; then
        GIT_VERSION=$(git --version | awk '{print $3}')
        log_success "Git encontrado: v$GIT_VERSION"
    else
        log_error "Git no encontrado."
        ((errors++))
    fi
    
    if [ $errors -gt 0 ]; then
        log_error "Se encontraron $errors errores. Por favor resuelve los problemas antes de continuar."
        exit 1
    fi
    
    log_success "Todos los requisitos verificados correctamente"
}

# Instalar Composer si no está presente
install_composer() {
    if ! command_exists composer; then
        log_step "Instalando Composer"
        
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer
        
        log_success "Composer instalado correctamente"
    fi
}

# Instalar dependencias PHP
install_php_dependencies() {
    log_step "Instalando dependencias PHP"
    
    if [ "$PROD_MODE" = true ]; then
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        composer install --no-interaction
    fi
    
    log_success "Dependencias PHP instaladas"
}

# Instalar dependencias Node.js
install_node_dependencies() {
    log_step "Instalando dependencias Node.js"
    
    npm install
    
    log_success "Dependencias Node.js instaladas"
}

# Configurar base de datos
setup_database() {
    log_step "Configurando base de datos"
    
    if [ "$SILENT_MODE" = false ]; then
        echo -n "Ingresa la contraseña para el usuario root de MySQL: "
        read -s MYSQL_ROOT_PASSWORD
        echo
        
        echo -n "Ingresa una contraseña para el usuario $DB_USER: "
        read -s DB_PASSWORD
        echo
    fi
    
    # Crear base de datos y usuario
    mysql -u root -p$MYSQL_ROOT_PASSWORD << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
GRANT SELECT ON performance_schema.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    log_success "Base de datos configurada"
}

# Configurar variables de entorno
setup_environment() {
    log_step "Configurando variables de entorno"
    
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            cp .env.example .env
        else
            # Crear archivo .env básico
            cat > .env << EOF
APP_NAME="Yega Dashboard"
APP_ENV=${PROD_MODE:+production}${DEV_MODE:+development}${PROD_MODE}${DEV_MODE:+local}
APP_DEBUG=${DEV_MODE:+true}${PROD_MODE:+false}
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASSWORD

DATABASE_URL="mysql://$DB_USER:$DB_PASSWORD@localhost:3306/$DB_NAME"

GITHUB_TOKEN=$GITHUB_TOKEN
GITHUB_USERNAME=
GITHUB_API_URL=https://api.github.com

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

LOG_CHANNEL=daily
LOG_LEVEL=info

API_RATE_LIMIT=60
API_RATE_LIMIT_WINDOW=60

GITHUB_SYNC_INTERVAL=300
GITHUB_BATCH_SIZE=50
GITHUB_TIMEOUT=30
EOF
        fi
        
        # Generar claves de seguridad
        if command_exists openssl; then
            APP_KEY=$(openssl rand -base64 32)
            echo "APP_KEY=base64:$APP_KEY" >> .env
            
            JWT_SECRET=$(openssl rand -base64 64)
            echo "JWT_SECRET=$JWT_SECRET" >> .env
        fi
        
        log_success "Variables de entorno configuradas"
    else
        log_info "Archivo .env ya existe, saltando configuración"
    fi
}

# Ejecutar migraciones
run_migrations() {
    log_step "Ejecutando migraciones de base de datos"
    
    # Generar cliente Prisma
    npx prisma generate
    
    # Ejecutar migraciones
    if [ "$DEV_MODE" = true ]; then
        npx prisma migrate dev --name "instalacion_inicial"
    else
        npx prisma migrate deploy
    fi
    
    # Ejecutar seeders si existen
    if [ -f "prisma/seed.js" ] || [ -f "prisma/seed.ts" ]; then
        npx prisma db seed
    fi
    
    log_success "Migraciones ejecutadas correctamente"
}

# Compilar assets
build_assets() {
    log_step "Compilando assets frontend"
    
    if [ "$PROD_MODE" = true ]; then
        npm run build
    else
        npm run dev &
        BUILD_PID=$!
        sleep 5
        kill $BUILD_PID 2>/dev/null || true
    fi
    
    log_success "Assets compilados"
}

# Configurar permisos
setup_permissions() {
    log_step "Configurando permisos de archivos"
    
    # Crear directorios necesarios
    mkdir -p logs storage/cache storage/sessions storage/app/public
    mkdir -p bootstrap/cache
    
    # Configurar permisos
    chmod -R 755 storage bootstrap/cache logs
    chmod -R 777 storage/app/public
    
    # Si está ejecutándose como root, cambiar propietario
    if [ "$EUID" -eq 0 ]; then
        chown -R www-data:www-data storage bootstrap/cache logs 2>/dev/null || true
    fi
    
    log_success "Permisos configurados"
}

# Ejecutar sincronización inicial
initial_sync() {
    if [ -n "$GITHUB_TOKEN" ]; then
        log_step "Ejecutando sincronización inicial con GitHub"
        
        php sync.php || log_warning "La sincronización inicial falló. Puedes ejecutarla manualmente más tarde."
        
        log_success "Sincronización inicial completada"
    else
        log_warning "Token de GitHub no configurado. Saltando sincronización inicial."
    fi
}

# Mostrar información final
show_completion_info() {
    echo -e "\n${GREEN}"
    cat << "EOF"
┌────────────────────────────────────────────────────────────────────────────────┐
│                    ✅ INSTALACIÓN COMPLETADA ✅                      │
├────────────────────────────────────────────────────────────────────────────────┤
EOF
    echo -e "${NC}"
    
    echo -e "${CYAN}Dashboard Yega instalado correctamente!${NC}\n"
    
    echo -e "${YELLOW}Próximos pasos:${NC}"
    echo -e "1. Configurar tu token de GitHub en el archivo .env"
    echo -e "2. Iniciar el servidor: ${GREEN}composer serve${NC} o ${GREEN}npm run serve${NC}"
    echo -e "3. Abrir en el navegador: ${BLUE}http://localhost:8000${NC}"
    echo -e "4. Ejecutar sincronización: ${GREEN}composer sync${NC}\n"
    
    echo -e "${YELLOW}Comandos útiles:${NC}"
    echo -e "- Ver logs: ${GREEN}npm run logs${NC}"
    echo -e "- Ejecutar tests: ${GREEN}composer test${NC}"
    echo -e "- Backup de DB: ${GREEN}composer backup${NC}"
    echo -e "- Actualizar deps: ${GREEN}npm run update-deps${NC}\n"
    
    echo -e "${CYAN}Documentación completa: docs/INSTALLATION.md${NC}"
    echo -e "${CYAN}Soporte: https://github.com/tu-usuario/yega-dashboard/issues${NC}\n"
}

# Parsear argumentos de línea de comandos
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --dev)
                DEV_MODE=true
                shift
                ;;
            --production)
                PROD_MODE=true
                shift
                ;;
            --silent)
                SILENT_MODE=true
                shift
                ;;
            --config)
                CONFIG_FILE="$2"
                shift 2
                ;;
            --github-token)
                GITHUB_TOKEN="$2"
                shift 2
                ;;
            --db-password)
                DB_PASSWORD="$2"
                shift 2
                ;;
            --help|-h)
                show_help
                exit 0
                ;;
            *)
                log_error "Opción desconocida: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Mostrar ayuda
show_help() {
    cat << EOF
Uso: $0 [OPCIONES]

Opciones:
    --dev                Instalación para desarrollo
    --production         Instalación para producción
    --silent             Instalación silenciosa sin prompts
    --config FILE        Archivo de configuración personalizada
    --github-token TOKEN Token de GitHub para API
    --db-password PASS   Contraseña para base de datos
    --help, -h           Mostrar esta ayuda

Ejemplos:
    $0                              # Instalación interactiva
    $0 --dev                        # Instalación para desarrollo
    $0 --production --silent        # Instalación silenciosa para producción
    $0 --github-token ghp_xxx       # Con token de GitHub
EOF
}

# Función principal
main() {
    # Mostrar banner
    show_banner
    
    # Parsear argumentos
    parse_arguments "$@"
    
    # Detectar sistema operativo
    detect_os
    
    # Verificar si estamos en el directorio correcto
    if [ ! -f "composer.json" ] || [ ! -f "package.json" ]; then
        log_error "No se encontraron archivos composer.json o package.json."
        log_error "Asegúrate de estar en el directorio raíz del proyecto Yega Dashboard."
        exit 1
    fi
    
    # Ejecutar pasos de instalación
    check_requirements
    install_composer
    install_php_dependencies
    install_node_dependencies
    setup_database
    setup_environment
    run_migrations
    build_assets
    setup_permissions
    initial_sync
    
    # Mostrar información de finalización
    show_completion_info
}

# Trap para cleanup en caso de interrupción
trap 'echo -e "\n${RED}Instalación interrumpida.${NC}"; exit 1' INT TERM

# Ejecutar script principal
main "$@"