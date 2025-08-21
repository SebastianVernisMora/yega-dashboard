#!/bin/bash

# =============================================================================
# Script de Configuración - Dashboard Yega
# =============================================================================
# Script para configurar y reconfiguar el Dashboard Yega después de la instalación
# =============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Funciones de logging
log() { echo -e "${GREEN}[$(date +'%H:%M:%S')] $1${NC}"; }
warn() { echo -e "${YELLOW}[$(date +'%H:%M:%S')] WARNING: $1${NC}"; }
error() { echo -e "${RED}[$(date +'%H:%M:%S')] ERROR: $1${NC}"; exit 1; }
info() { echo -e "${BLUE}[$(date +'%H:%M:%S')] INFO: $1${NC}"; }

# Menú principal
show_main_menu() {
    clear
    echo -e "${BLUE}"
    echo "======================================"
    echo "    Dashboard Yega - Configurador"
    echo "======================================"
    echo -e "${NC}"
    echo "1) Configurar base de datos"
    echo "2) Configurar GitHub API"
    echo "3) Configurar variables de entorno"
    echo "4) Regenerar claves de seguridad"
    echo "5) Ejecutar migraciones"
    echo "6) Limpiar cache"
    echo "7) Verificar configuración"
    echo "8) Backup de configuración"
    echo "9) Restaurar configuración"
    echo "0) Salir"
    echo
    read -p "Selecciona una opción (0-9): " choice
    
    case $choice in
        1) configure_database ;;
        2) configure_github ;;
        3) configure_environment ;;
        4) regenerate_keys ;;
        5) run_migrations ;;
        6) clear_cache ;;
        7) verify_configuration ;;
        8) backup_configuration ;;
        9) restore_configuration ;;
        0) exit 0 ;;
        *) warn "Opción inválida"; sleep 2; show_main_menu ;;
    esac
}

# Configurar base de datos
configure_database() {
    echo
    echo "=== Configuración de Base de Datos ==="
    
    # Leer configuración actual
    if [ -f ".env" ]; then
        current_host=$(grep "^DB_HOST=" .env | cut -d= -f2)
        current_port=$(grep "^DB_PORT=" .env | cut -d= -f2)
        current_db=$(grep "^DB_DATABASE=" .env | cut -d= -f2)
        current_user=$(grep "^DB_USERNAME=" .env | cut -d= -f2)
        
        echo "Configuración actual:"
        echo "  Host: $current_host"
        echo "  Puerto: $current_port"
        echo "  Base de datos: $current_db"
        echo "  Usuario: $current_user"
        echo
    fi
    
    read -p "Host de MySQL [$current_host]: " db_host
    db_host=${db_host:-$current_host}
    
    read -p "Puerto [$current_port]: " db_port
    db_port=${db_port:-$current_port}
    
    read -p "Base de datos [$current_db]: " db_name
    db_name=${db_name:-$current_db}
    
    read -p "Usuario [$current_user]: " db_user
    db_user=${db_user:-$current_user}
    
    read -s -p "Password: " db_password
    echo
    
    # Probar conexión
    log "Probando conexión..."
    if mysql -h"$db_host" -P"$db_port" -u"$db_user" -p"$db_password" -e "SELECT 1;" &>/dev/null; then
        log "Conexión exitosa ✓"
        
        # Actualizar .env
        update_env_var "DB_HOST" "$db_host"
        update_env_var "DB_PORT" "$db_port"
        update_env_var "DB_DATABASE" "$db_name"
        update_env_var "DB_USERNAME" "$db_user"
        update_env_var "DB_PASSWORD" "$db_password"
        
        # Actualizar DATABASE_URL
        database_url="mysql://$db_user:$db_password@$db_host:$db_port/$db_name"
        update_env_var "DATABASE_URL" "\"$database_url\""
        
        log "Configuración de base de datos actualizada"
    else
        error "No se pudo conectar a la base de datos"
    fi
    
    read -p "Presiona Enter para continuar..."
    show_main_menu
}

# Configurar GitHub
configure_github() {
    echo
    echo "=== Configuración de GitHub API ==="
    
    # Mostrar configuración actual
    if [ -f ".env" ]; then
        current_username=$(grep "^GITHUB_USERNAME=" .env | cut -d= -f2)
        current_token=$(grep "^GITHUB_TOKEN=" .env | cut -d= -f2)
        
        echo "Configuración actual:"
        echo "  Usuario: $current_username"
        echo "  Token: ${current_token:0:10}..."
        echo
    fi
    
    echo "Para obtener un token de GitHub:"
    echo "1. Ve a https://github.com/settings/tokens"
    echo "2. Click en 'Generate new token (classic)'"
    echo "3. Selecciona los permisos: repo, user:read, user:email, read:org"
    echo
    
    read -p "Usuario de GitHub [$current_username]: " github_username
    github_username=${github_username:-$current_username}
    
    read -s -p "Personal Access Token: " github_token
    echo
    
    # Probar token
    log "Probando token..."
    response=$(curl -s -H "Authorization: token $github_token" https://api.github.com/user)
    
    if echo "$response" | grep -q '"login"'; then
        log "Token válido ✓"
        
        # Actualizar .env
        update_env_var "GITHUB_USERNAME" "$github_username"
        update_env_var "GITHUB_TOKEN" "$github_token"
        
        log "Configuración de GitHub actualizada"
    else
        error "Token inválido o sin permisos"
    fi
    
    read -p "Presiona Enter para continuar..."
    show_main_menu
}

# Configurar variables de entorno
configure_environment() {
    echo
    echo "=== Configuración de Variables de Entorno ==="
    echo "1) Configuración básica"
    echo "2) Configuración avanzada"
    echo "3) Ver configuración actual"
    echo "4) Volver al menú principal"
    
    read -p "Selecciona una opción: " env_choice
    
    case $env_choice in
        1) configure_basic_env ;;
        2) configure_advanced_env ;;
        3) show_current_env ;;
        4) show_main_menu ;;
        *) warn "Opción inválida"; configure_environment ;;
    esac
}

# Configuración básica
configure_basic_env() {
    echo
    echo "=== Configuración Básica ==="
    
    current_name=$(grep "^APP_NAME=" .env | cut -d= -f2 | tr -d '"')
    current_url=$(grep "^APP_URL=" .env | cut -d= -f2)
    current_env=$(grep "^APP_ENV=" .env | cut -d= -f2)
    current_debug=$(grep "^APP_DEBUG=" .env | cut -d= -f2)
    
    read -p "Nombre de la aplicación [$current_name]: " app_name
    app_name=${app_name:-$current_name}
    
    read -p "URL de la aplicación [$current_url]: " app_url
    app_url=${app_url:-$current_url}
    
    echo "Entorno de aplicación:"
    echo "1) development"
    echo "2) production"
    read -p "Selecciona [1-2]: " env_choice
    
    case $env_choice in
        1) app_env="development"; app_debug="true" ;;
        2) app_env="production"; app_debug="false" ;;
        *) app_env=$current_env; app_debug=$current_debug ;;
    esac
    
    # Actualizar variables
    update_env_var "APP_NAME" "\"$app_name\""
    update_env_var "APP_URL" "$app_url"
    update_env_var "APP_ENV" "$app_env"
    update_env_var "APP_DEBUG" "$app_debug"
    
    log "Configuración básica actualizada"
    
    read -p "Presiona Enter para continuar..."
    show_main_menu
}

# Configuración avanzada
configure_advanced_env() {
    echo
    echo "=== Configuración Avanzada ==="
    echo "1) Configurar cache"
    echo "2) Configurar sesiones"
    echo "3) Configurar logging"
    echo "4) Configurar email"
    echo "5) Configurar timezone"
    echo "6) Volver"
    
    read -p "Selecciona una opción: " adv_choice
    
    case $adv_choice in
        1) configure_cache ;;
        2) configure_sessions ;;
        3) configure_logging ;;
        4) configure_email ;;
        5) configure_timezone ;;
        6) configure_environment ;;
        *) warn "Opción inválida"; configure_advanced_env ;;
    esac
}

# Configurar cache
configure_cache() {
    echo
    echo "Driver de cache:"
    echo "1) file (por defecto)"
    echo "2) redis"
    echo "3) memcached"
    
    read -p "Selecciona [1-3]: " cache_choice
    
    case $cache_choice in
        1) cache_driver="file" ;;
        2) 
            cache_driver="redis"
            read -p "Redis host [localhost]: " redis_host
            redis_host=${redis_host:-localhost}
            read -p "Redis port [6379]: " redis_port
            redis_port=${redis_port:-6379}
            update_env_var "REDIS_HOST" "$redis_host"
            update_env_var "REDIS_PORT" "$redis_port"
            ;;
        3) cache_driver="memcached" ;;
        *) cache_driver="file" ;;
    esac
    
    update_env_var "CACHE_DRIVER" "$cache_driver"
    log "Configuración de cache actualizada"
    
    configure_advanced_env
}

# Configurar sesiones
configure_sessions() {
    echo
    echo "Driver de sesiones:"
    echo "1) file"
    echo "2) database"
    echo "3) redis"
    
    read -p "Selecciona [1-3]: " session_choice
    
    case $session_choice in
        1) session_driver="file" ;;
        2) session_driver="database" ;;
        3) session_driver="redis" ;;
        *) session_driver="file" ;;
    esac
    
    read -p "Duración de sesión en minutos [120]: " session_lifetime
    session_lifetime=${session_lifetime:-120}
    
    update_env_var "SESSION_DRIVER" "$session_driver"
    update_env_var "SESSION_LIFETIME" "$session_lifetime"
    
    log "Configuración de sesiones actualizada"
    
    configure_advanced_env
}

# Configurar logging
configure_logging() {
    echo
    echo "Canal de logging:"
    echo "1) daily (recomendado)"
    echo "2) single"
    echo "3) syslog"
    
    read -p "Selecciona [1-3]: " log_choice
    
    case $log_choice in
        1) log_channel="daily" ;;
        2) log_channel="single" ;;
        3) log_channel="syslog" ;;
        *) log_channel="daily" ;;
    esac
    
    echo "Nivel de logging:"
    echo "1) debug"
    echo "2) info (recomendado)"
    echo "3) warning"
    echo "4) error"
    
    read -p "Selecciona [1-4]: " level_choice
    
    case $level_choice in
        1) log_level="debug" ;;
        2) log_level="info" ;;
        3) log_level="warning" ;;
        4) log_level="error" ;;
        *) log_level="info" ;;
    esac
    
    update_env_var "LOG_CHANNEL" "$log_channel"
    update_env_var "LOG_LEVEL" "$log_level"
    
    log "Configuración de logging actualizada"
    
    configure_advanced_env
}

# Configurar timezone
configure_timezone() {
    echo
    echo "Timezone actual: $(grep "^APP_TIMEZONE=" .env | cut -d= -f2)"
    echo
    echo "Timezones comunes:"
    echo "1) America/Mexico_City"
    echo "2) America/New_York"
    echo "3) Europe/Madrid"
    echo "4) UTC"
    echo "5) Personalizado"
    
    read -p "Selecciona [1-5]: " tz_choice
    
    case $tz_choice in
        1) timezone="America/Mexico_City" ;;
        2) timezone="America/New_York" ;;
        3) timezone="Europe/Madrid" ;;
        4) timezone="UTC" ;;
        5) 
            read -p "Introduce el timezone: " timezone
            ;;
        *) timezone="UTC" ;;
    esac
    
    update_env_var "APP_TIMEZONE" "$timezone"
    log "Timezone actualizado a: $timezone"
    
    configure_advanced_env
}

# Mostrar configuración actual
show_current_env() {
    echo
    echo "=== Configuración Actual ==="
    
    if [ -f ".env" ]; then
        echo "Aplicación:"
        grep -E "^(APP_NAME|APP_ENV|APP_DEBUG|APP_URL|APP_TIMEZONE)=" .env | sed 's/^/  /'
        echo
        
        echo "Base de datos:"
        grep -E "^(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME)=" .env | sed 's/^/  /'
        echo
        
        echo "GitHub:"
        grep -E "^(GITHUB_USERNAME|GITHUB_API_URL)=" .env | sed 's/^/  /'
        echo
        
        echo "Cache y Sesiones:"
        grep -E "^(CACHE_DRIVER|SESSION_DRIVER|SESSION_LIFETIME)=" .env | sed 's/^/  /'
        echo
        
        echo "Logging:"
        grep -E "^(LOG_CHANNEL|LOG_LEVEL)=" .env | sed 's/^/  /'
    else
        warn "Archivo .env no encontrado"
    fi
    
    read -p "Presiona Enter para continuar..."
    configure_environment
}

# Regenerar claves de seguridad
regenerate_keys() {
    echo
    echo "=== Regenerar Claves de Seguridad ==="
    warn "Esto invalidará todas las sesiones activas"
    
    read -p "¿Continuar? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log "Generando nuevas claves..."
        
        # Generar claves
        app_key=$(openssl rand -base64 32)
        jwt_secret=$(openssl rand -base64 64)
        encryption_key=$(openssl rand -base64 32)
        
        # Actualizar .env
        update_env_var "APP_KEY" "base64:$app_key"
        update_env_var "JWT_SECRET" "$jwt_secret"
        update_env_var "ENCRYPTION_KEY" "$encryption_key"
        
        log "Claves regeneradas exitosamente"
    else
        log "Operación cancelada"
    fi
    
    read -p "Presiona Enter para continuar..."
    show_main_menu
}

# Ejecutar migraciones
run_migrations() {
    echo
    echo "=== Ejecutar Migraciones ==="
    
    if [ -f "prisma/schema.prisma" ]; then
        log "Ejecutando migraciones de Prisma..."
        npx prisma migrate deploy
        npx prisma generate
        log "Migraciones de Prisma completadas"
    elif command -v php artisan &> /dev/null; then
        log "Ejecutando migraciones de Laravel..."
        php artisan migrate --force
        log "Migraciones de Laravel completadas"
    else
        warn "No se encontraron herramientas de migración"
    fi
    
    read -p "Presiona Enter para continuar..."
    show_main_menu
}

# Limpiar cache
clear_cache() {
    echo
    echo "=== Limpiar Cache ==="
    
    if command -v php artisan &> /dev/null; then
        log "Limpiando cache de Laravel..."
        php artisan cache:clear
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        log "Cache de Laravel limpiado"
    fi
    
    # Limpiar cache de Node.js
    if [ -d "node_modules/.cache" ]; then
        log "Limpiando cache de Node.js..."
        rm -rf node_modules/.cache
        log "Cache de Node.js limpiado"
    fi
    
    read -p "Presiona Enter para continuar..."
    show_main_menu
}

# Verificar configuración
verify_configuration() {
    echo
    echo "=== Verificar Configuración ==="
    
    local errors=0
    
    # Verificar archivo .env
    if [ ! -f ".env" ]; then
        error "Archivo .env no encontrado"
        ((errors++))
    else
        log "Archivo .env encontrado ✓"
    fi
    
    # Verificar base de datos
    if [ -f ".env" ]; then
        log "Verificando conexión a base de datos..."
        db_host=$(grep "^DB_HOST=" .env | cut -d= -f2)
        db_port=$(grep "^DB_PORT=" .env | cut -d= -f2)
        db_user=$(grep "^DB_USERNAME=" .env | cut -d= -f2)
        db_password=$(grep "^DB_PASSWORD=" .env | cut -d= -f2)
        
        if mysql -h"$db_host" -P"$db_port" -u"$db_user" -p"$db_password" -e "SELECT 1;" &>/dev/null; then
            log "Conexión a base de datos ✓"
        else
            warn "Error de conexión a base de datos"
            ((errors++))
        fi
    fi
    
    # Verificar GitHub token
    if [ -f ".env" ]; then
        log "Verificando GitHub token..."
        github_token=$(grep "^GITHUB_TOKEN=" .env | cut -d= -f2)
        
        if [ -n "$github_token" ]; then
            response=$(curl -s -H "Authorization: token $github_token" https://api.github.com/user)
            if echo "$response" | grep -q '"login"'; then
                log "GitHub token válido ✓"
            else
                warn "GitHub token inválido"
                ((errors++))
            fi
        else
            warn "GitHub token no configurado"
            ((errors++))
        fi
    fi
    
    # Verificar permisos
    log "Verificando permisos de archivos..."
    writable_dirs=("storage" "bootstrap/cache" "public/uploads")
    
    for dir in "${writable_dirs[@]}"; do
        if [ -d "$dir" ]; then
            if [ -w "$dir" ]; then
                log "Permisos de $dir ✓"
            else
                warn "Sin permisos de escritura en $dir"
                ((errors++))
            fi
        fi
    done
    
    # Resumen
    echo
    if [ $errors -eq 0 ]; then
        log "Configuración verificada exitosamente ✓"
    else
        warn "Se encontraron $errors problemas"
    fi
    
    read -p "Presiona Enter para continuar..."
    show_main_menu
}

# Backup de configuración
backup_configuration() {
    echo
    echo "=== Backup de Configuración ==="
    
    local backup_dir="backups/config"
    local timestamp=$(date +"%Y%m%d_%H%M%S")
    local backup_file="$backup_dir/config_backup_$timestamp.tar.gz"
    
    mkdir -p "$backup_dir"
    
    log "Creando backup en $backup_file..."
    
    tar -czf "$backup_file" .env prisma/ 2>/dev/null || true
    
    if [ -f "$backup_file" ]; then
        log "Backup creado exitosamente"
        echo "Archivo: $backup_file"
    else
        error "Error al crear backup"
    fi
    
    read -p "Presiona Enter para continuar..."
    show_main_menu
}

# Restaurar configuración
restore_configuration() {
    echo
    echo "=== Restaurar Configuración ==="
    
    local backup_dir="backups/config"
    
    if [ ! -d "$backup_dir" ]; then
        warn "No se encontraron backups"
        read -p "Presiona Enter para continuar..."
        show_main_menu
        return
    fi
    
    echo "Backups disponibles:"
    ls -la "$backup_dir"/*.tar.gz 2>/dev/null | nl
    
    read -p "Selecciona el número de backup a restaurar: " backup_num
    
    local backup_file=$(ls "$backup_dir"/*.tar.gz 2>/dev/null | sed -n "${backup_num}p")
    
    if [ -n "$backup_file" ] && [ -f "$backup_file" ]; then
        warn "Esto sobrescribirá la configuración actual"
        read -p "¿Continuar? (y/N): " -n 1 -r
        echo
        
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            log "Restaurando desde $backup_file..."
            tar -xzf "$backup_file"
            log "Configuración restaurada exitosamente"
        else
            log "Operación cancelada"
        fi
    else
        warn "Backup inválido"
    fi
    
    read -p "Presiona Enter para continuar..."
    show_main_menu
}

# Función auxiliar para actualizar variables de entorno
update_env_var() {
    local key=$1
    local value=$2
    
    if grep -q "^$key=" .env; then
        # Reemplazar valor existente
        sed -i "s|^$key=.*|$key=$value|g" .env
    else
        # Agregar nueva variable
        echo "$key=$value" >> .env
    fi
}

# Función principal
main() {
    # Verificar que estamos en el directorio correcto
    if [ ! -f "composer.json" ] && [ ! -f "package.json" ]; then
        error "Ejecuta este script desde el directorio raíz del proyecto"
    fi
    
    show_main_menu
}

# Ejecutar si es llamado directamente
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi