#!/bin/bash

# =============================================================================
# Script de Restauración - Dashboard Yega
# =============================================================================
# Restaura la aplicación desde un backup
# =============================================================================

set -e

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${GREEN}[RESTORE] $1${NC}"; }
warn() { echo -e "${YELLOW}[WARN] $1${NC}"; }
error() { echo -e "${RED}[ERROR] $1${NC}"; exit 1; }
info() { echo -e "${BLUE}[INFO] $1${NC}"; }

show_usage() {
    echo "Uso: $0 [backup_name]"
    echo ""
    echo "Ejemplos:"
    echo "  $0 yega_backup_20250817_120000"
    echo "  $0  # Muestra backups disponibles"
}

list_backups() {
    echo "Backups disponibles:"
    if [ -d "backups" ]; then
        ls -la backups/ | grep "yega_backup_" | grep ".tar.gz" | awk '{print $9}' | sed 's/_files.tar.gz//' | sort -r
    else
        warn "No se encontraron backups"
    fi
}

restore_files() {
    local backup_name=$1
    local files_backup="backups/${backup_name}_files.tar.gz"
    
    if [ ! -f "$files_backup" ]; then
        error "Archivo de backup no encontrado: $files_backup"
    fi
    
    warn "Esto sobrescribirá los archivos actuales"
    read -p "¿Continuar? (y/N): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log "Operación cancelada"
        exit 0
    fi
    
    log "Restaurando archivos desde $files_backup..."
    tar -xzf "$files_backup"
    log "Archivos restaurados"
}

restore_database() {
    local backup_name=$1
    local db_backup="backups/${backup_name}_database.sql"
    
    if [ ! -f "$db_backup" ]; then
        warn "Backup de base de datos no encontrado: $db_backup"
        return
    fi
    
    if [ ! -f ".env" ]; then
        error "Archivo .env no encontrado. Configura la base de datos primero."
    fi
    
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d= -f2)
    DB_PORT=$(grep "^DB_PORT=" .env | cut -d= -f2)
    DB_USER=$(grep "^DB_USERNAME=" .env | cut -d= -f2)
    DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d= -f2)
    DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d= -f2)
    
    warn "Esto sobrescribirá la base de datos actual: $DB_NAME"
    read -p "¿Continuar? (y/N): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log "Restauración de BD cancelada"
        return
    fi
    
    log "Restaurando base de datos desde $db_backup..."
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$db_backup"
    log "Base de datos restaurada"
}

post_restore() {
    log "Ejecutando tareas post-restauración..."
    
    # Instalar dependencias
    if [ -f "composer.json" ]; then
        log "Instalando dependencias PHP..."
        composer install --no-dev --optimize-autoloader
    fi
    
    if [ -f "package.json" ]; then
        log "Instalando dependencias Node.js..."
        npm install
    fi
    
    # Generar assets
    if [ -f "package.json" ]; then
        log "Compilando assets..."
        npm run build
    fi
    
    # Limpiar cache
    if command -v php artisan &> /dev/null; then
        log "Limpiando cache..."
        php artisan cache:clear
        php artisan config:cache
    fi
    
    # Configurar permisos
    log "Configurando permisos..."
    chmod -R 775 storage 2>/dev/null || true
    chmod -R 775 bootstrap/cache 2>/dev/null || true
    
    log "Tareas post-restauración completadas"
}

main() {
    local backup_name=$1
    
    if [ -z "$backup_name" ]; then
        list_backups
        echo ""
        show_usage
        exit 0
    fi
    
    echo -e "${BLUE}"
    echo "======================================"
    echo "  Restauración - Dashboard Yega"
    echo "======================================"
    echo -e "${NC}"
    
    info "Restaurando desde: $backup_name"
    
    # Verificar manifiesto
    local manifest="backups/${backup_name}_manifest.txt"
    if [ -f "$manifest" ]; then
        info "Información del backup:"
        cat "$manifest"
        echo ""
    fi
    
    # Restaurar archivos
    restore_files "$backup_name"
    
    # Restaurar base de datos
    restore_database "$backup_name"
    
    # Tareas post-restauración
    post_restore
    
    log "Restauración completada exitosamente"
    info "Recuerda verificar la configuración en .env"
}

main "$@"