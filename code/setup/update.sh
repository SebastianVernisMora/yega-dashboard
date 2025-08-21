#!/bin/bash

# =============================================================================
# Script de Actualización - Dashboard Yega
# =============================================================================
# Actualiza la aplicación a la última versión
# =============================================================================

set -e

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${GREEN}[UPDATE] $1${NC}"; }
warn() { echo -e "${YELLOW}[WARN] $1${NC}"; }
error() { echo -e "${RED}[ERROR] $1${NC}"; exit 1; }
info() { echo -e "${BLUE}[INFO] $1${NC}"; }

BACKUP_BEFORE_UPDATE=true
GIT_BRANCH="main"

show_banner() {
    echo -e "${BLUE}"
    echo "======================================"
    echo "  Actualización - Dashboard Yega"
    echo "======================================"
    echo -e "${NC}"
}

check_git_status() {
    log "Verificando estado de Git..."
    
    if [ ! -d ".git" ]; then
        error "Este no es un repositorio Git"
    fi
    
    # Verificar cambios sin confirmar
    if ! git diff --quiet; then
        warn "Hay cambios sin confirmar en el repositorio"
        git status --porcelain
        echo ""
        read -p "¿Continuar de todas formas? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 0
        fi
    fi
    
    # Verificar rama actual
    local current_branch=$(git branch --show-current)
    info "Rama actual: $current_branch"
    
    if [ "$current_branch" != "$GIT_BRANCH" ]; then
        warn "No estás en la rama $GIT_BRANCH"
        read -p "¿Cambiar a $GIT_BRANCH? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            git checkout "$GIT_BRANCH"
        fi
    fi
}

create_backup() {
    if [ "$BACKUP_BEFORE_UPDATE" = true ]; then
        log "Creando backup antes de actualizar..."
        
        if [ -f "code/setup/backup.sh" ]; then
            bash code/setup/backup.sh
        else
            warn "Script de backup no encontrado, saltando..."
        fi
    fi
}

update_code() {
    log "Actualizando código desde repositorio..."
    
    # Obtener últimos cambios
    git fetch origin
    
    # Mostrar cambios pendientes
    local commits_behind=$(git rev-list HEAD..origin/$GIT_BRANCH --count)
    
    if [ "$commits_behind" -eq 0 ]; then
        info "El código ya está actualizado"
        return
    fi
    
    info "Hay $commits_behind commits nuevos"
    echo "Cambios pendientes:"
    git log --oneline HEAD..origin/$GIT_BRANCH
    echo ""
    
    read -p "¿Continuar con la actualización? (y/N): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 0
    fi
    
    # Aplicar actualización
    git pull origin "$GIT_BRANCH"
    log "Código actualizado"
}

update_dependencies() {
    log "Actualizando dependencias..."
    
    # Dependencias PHP
    if [ -f "composer.json" ]; then
        log "Actualizando dependencias PHP..."
        composer install --no-dev --optimize-autoloader
    fi
    
    # Dependencias Node.js
    if [ -f "package.json" ]; then
        log "Actualizando dependencias Node.js..."
        npm install
    fi
}

run_migrations() {
    log "Ejecutando migraciones..."
    
    if [ -f "prisma/schema.prisma" ]; then
        log "Ejecutando migraciones de Prisma..."
        npx prisma migrate deploy
        npx prisma generate
    elif command -v php artisan &> /dev/null; then
        log "Ejecutando migraciones de Laravel..."
        php artisan migrate --force
    else
        warn "No se encontraron herramientas de migración"
    fi
}

build_assets() {
    log "Compilando assets..."
    
    if [ -f "package.json" ]; then
        npm run build
        log "Assets compilados"
    else
        warn "package.json no encontrado, saltando compilación"
    fi
}

clear_cache() {
    log "Limpiando cache..."
    
    # Laravel cache
    if command -v php artisan &> /dev/null; then
        php artisan cache:clear
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        php artisan config:cache
        php artisan route:cache
    fi
    
    # Node.js cache
    if [ -d "node_modules/.cache" ]; then
        rm -rf node_modules/.cache
    fi
    
    log "Cache limpiado"
}

optimize_application() {
    log "Optimizando aplicación..."
    
    # Laravel optimizations
    if command -v php artisan &> /dev/null; then
        php artisan optimize
    fi
    
    # Configurar permisos
    chmod -R 775 storage 2>/dev/null || true
    chmod -R 775 bootstrap/cache 2>/dev/null || true
    
    log "Aplicación optimizada"
}

verify_update() {
    log "Verificando actualización..."
    
    # Verificar que la aplicación funcione
    if command -v php artisan &> /dev/null; then
        if php artisan --version &>/dev/null; then
            log "Aplicación funcionando correctamente"
        else
            error "Error en la aplicación después de actualizar"
        fi
    fi
    
    # Verificar conexión a base de datos
    if [ -f "code/setup/check-system.sh" ]; then
        bash code/setup/check-system.sh
    fi
}

show_summary() {
    echo ""
    echo "======================================"
    echo "        ACTUALIZACIÓN COMPLETADA"
    echo "======================================"
    log "Dashboard Yega actualizado exitosamente"
    
    # Mostrar versión actual
    if [ -f "package.json" ]; then
        local version=$(grep '"version"' package.json | head -1 | cut -d'"' -f4)
        info "Versión actual: $version"
    fi
    
    # Mostrar commit actual
    local commit=$(git rev-parse --short HEAD)
    info "Commit actual: $commit"
    
    echo ""
    info "Para iniciar la aplicación:"
    echo "  php artisan serve"
    echo "  # o"
    echo "  php -S localhost:8000 -t public"
}

main() {
    show_banner
    
    # Parsear argumentos
    while [[ $# -gt 0 ]]; do
        case $1 in
            --no-backup)
                BACKUP_BEFORE_UPDATE=false
                shift
                ;;
            --branch)
                GIT_BRANCH="$2"
                shift 2
                ;;
            --help)
                echo "Uso: $0 [opciones]"
                echo "Opciones:"
                echo "  --no-backup    No crear backup antes de actualizar"
                echo "  --branch RAMA  Actualizar desde rama específica (default: main)"
                echo "  --help         Mostrar esta ayuda"
                exit 0
                ;;
            *)
                error "Opción desconocida: $1"
                ;;
        esac
    done
    
    check_git_status
    create_backup
    update_code
    update_dependencies
    run_migrations
    build_assets
    clear_cache
    optimize_application
    verify_update
    show_summary
}

main "$@"