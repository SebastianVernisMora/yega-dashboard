#!/bin/bash

# =============================================================================
# Script de Deployment - Dashboard Yega
# Versión: 1.0.0
# Descripción: Deployment automatizado para diferentes entornos
# =============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Variables
ENVIRONMENT="production"
BRANCH="main"
BACKUP_ENABLED=true
MIGRATE_ENABLED=true
DOWNTIME_PAGE=false
SKIP_TESTS=false
VERBOSE=false

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
    echo -e "\n${CYAN}═══ $1 ═══${NC}"
}

# Banner
show_banner() {
    echo -e "${PURPLE}"
    cat << "EOF"
╔════════════════════════════════════════════════════════════════════════════════╗
║                    🚀 YEGA DASHBOARD DEPLOYMENT 🚀                            ║
║                         Automated Deployment v1.0.0                          ║
╚════════════════════════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
}

# Verificar requisitos previos
check_prerequisites() {
    log_step "Verificando requisitos previos"
    
    # Verificar que estamos en el directorio correcto
    if [ ! -f "composer.json" ] || [ ! -f "package.json" ]; then
        log_error "No se encontraron archivos composer.json o package.json"
        exit 1
    fi
    
    # Verificar Git
    if ! command -v git &> /dev/null; then
        log_error "Git no está instalado"
        exit 1
    fi
    
    # Verificar que no hay cambios sin commit
    if [ "$ENVIRONMENT" = "production" ] && [ -n "$(git status --porcelain)" ]; then
        log_error "Hay cambios sin commit. Confirma todos los cambios antes del deployment"
        exit 1
    fi
    
    log_success "Requisitos verificados"
}

# Crear backup
create_backup() {
    if [ "$BACKUP_ENABLED" = true ]; then
        log_step "Creando backup"
        
        BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
        mkdir -p "$BACKUP_DIR"
        
        # Backup de base de datos
        if [ -f ".env" ]; then
            source .env
            mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/database.sql"
            log_success "Backup de base de datos creado"
        fi
        
        # Backup de archivos críticos
        cp -r storage "$BACKUP_DIR/" 2>/dev/null || true
        cp .env "$BACKUP_DIR/" 2>/dev/null || true
        cp -r logs "$BACKUP_DIR/" 2>/dev/null || true
        
        # Comprimir backup
        tar -czf "backups/backup_$(date +%Y%m%d_%H%M%S).tar.gz" "$BACKUP_DIR"
        rm -rf "$BACKUP_DIR"
        
        log_success "Backup completo creado"
    fi
}

# Mostrar página de mantenimiento
show_maintenance() {
    if [ "$DOWNTIME_PAGE" = true ]; then
        log_step "Activando página de mantenimiento"
        
        cat > public/maintenance.html << EOF
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento - Yega Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #1a1a2e; color: #eee; }
        .container { max-width: 600px; margin: 0 auto; }
        .logo { font-size: 48px; margin-bottom: 20px; }
        h1 { color: #00d9ff; }
        .spinner { border: 4px solid #333; border-top: 4px solid #00d9ff; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🚀</div>
        <h1>Yega Dashboard</h1>
        <h2>Actualizando sistema...</h2>
        <div class="spinner"></div>
        <p>Estamos realizando mejoras para ofrecerte una mejor experiencia.</p>
        <p>El servicio estará disponible en unos minutos.</p>
    </div>
</body>
</html>
EOF
        
        # Redirigir todo el tráfico a la página de mantenimiento
        if [ -f "public/.htaccess" ]; then
            cp public/.htaccess public/.htaccess.backup
        fi
        
        cat > public/.htaccess << EOF
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/maintenance.html$
RewriteRule ^(.*)$ /maintenance.html [R=503,L]
EOF
        
        log_success "Página de mantenimiento activada"
    fi
}

# Remover página de mantenimiento
remove_maintenance() {
    if [ "$DOWNTIME_PAGE" = true ]; then
        log_step "Desactivando página de mantenimiento"
        
        rm -f public/maintenance.html
        
        if [ -f "public/.htaccess.backup" ]; then
            mv public/.htaccess.backup public/.htaccess
        else
            rm -f public/.htaccess
        fi
        
        log_success "Página de mantenimiento desactivada"
    fi
}

# Actualizar código
update_code() {
    log_step "Actualizando código desde Git"
    
    # Hacer stash de cambios locales si los hay
    git stash push -m "Auto-stash before deployment $(date)"
    
    # Fetch y pull
    git fetch origin
    git checkout "$BRANCH"
    git pull origin "$BRANCH"
    
    log_success "Código actualizado desde rama $BRANCH"
}

# Instalar dependencias
install_dependencies() {
    log_step "Instalando dependencias"
    
    # Dependencias PHP
    if [ "$ENVIRONMENT" = "production" ]; then
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        composer install --no-interaction
    fi
    
    # Dependencias Node.js
    npm ci --silent
    
    log_success "Dependencias instaladas"
}

# Ejecutar tests
run_tests() {
    if [ "$SKIP_TESTS" = false ]; then
        log_step "Ejecutando tests"
        
        # Tests PHP
        if [ -f "vendor/bin/phpunit" ]; then
            ./vendor/bin/phpunit --stop-on-failure
        fi
        
        # Tests JavaScript
        if command -v npm &> /dev/null; then
            npm test
        fi
        
        log_success "Tests ejecutados correctamente"
    else
        log_warning "Tests omitidos"
    fi
}

# Compilar assets
build_assets() {
    log_step "Compilando assets"
    
    if [ "$ENVIRONMENT" = "production" ]; then
        npm run build
    else
        npm run dev
    fi
    
    log_success "Assets compilados"
}

# Ejecutar migraciones
run_migrations() {
    if [ "$MIGRATE_ENABLED" = true ]; then
        log_step "Ejecutando migraciones de base de datos"
        
        # Generar cliente Prisma
        npx prisma generate
        
        # Ejecutar migraciones
        npx prisma migrate deploy
        
        log_success "Migraciones ejecutadas"
    else
        log_warning "Migraciones omitidas"
    fi
}

# Limpiar cache
clear_cache() {
    log_step "Limpiando cache"
    
    # Cache de aplicación
    if [ -d "storage/cache" ]; then
        rm -rf storage/cache/*
    fi
    
    # Cache de Composer
    composer dump-autoload --optimize
    
    # Cache de logs antiguos
    find logs/ -name "*.log" -mtime +7 -delete 2>/dev/null || true
    
    log_success "Cache limpiado"
}

# Optimizar aplicación
optimize_application() {
    log_step "Optimizando aplicación"
    
    # Optimizar autoloader
    composer dump-autoload --optimize --classmap-authoritative
    
    # Optimizar configuración de Node.js para producción
    if [ "$ENVIRONMENT" = "production" ]; then
        export NODE_ENV=production
    fi
    
    log_success "Aplicación optimizada"
}

# Verificar salud de la aplicación
health_check() {
    log_step "Verificando salud de la aplicación"
    
    # Esperar a que la aplicación esté lista
    sleep 5
    
    # Verificar endpoint de salud
    if command -v curl &> /dev/null; then
        if curl -f -s "http://localhost:8000/api/health" > /dev/null; then
            log_success "Aplicación respondiendo correctamente"
        else
            log_warning "La aplicación puede no estar respondiendo correctamente"
        fi
    fi
    
    # Verificar archivos críticos
    if [ -f "public/index.php" ] && [ -f ".env" ]; then
        log_success "Archivos críticos presentes"
    else
        log_error "Faltan archivos críticos"
        exit 1
    fi
}

# Notificar deployment
notify_deployment() {
    log_step "Notificando deployment completado"
    
    DEPLOYMENT_TIME=$(date)
    COMMIT_HASH=$(git rev-parse --short HEAD)
    
    log_info "Deployment completado:"
    log_info "  Entorno: $ENVIRONMENT"
    log_info "  Rama: $BRANCH"
    log_info "  Commit: $COMMIT_HASH"
    log_info "  Tiempo: $DEPLOYMENT_TIME"
    
    # Registrar deployment en log
    echo "$DEPLOYMENT_TIME - Deployment to $ENVIRONMENT from $BRANCH ($COMMIT_HASH)" >> logs/deployments.log
}

# Cleanup en caso de error
cleanup_on_error() {
    log_error "Error durante el deployment. Ejecutando cleanup..."
    
    # Remover página de mantenimiento
    remove_maintenance
    
    # Notificar error
    echo "$(date) - Deployment failed to $ENVIRONMENT from $BRANCH" >> logs/deployments.log
    
    exit 1
}

# Mostrar ayuda
show_help() {
    cat << EOF
Uso: $0 [OPCIONES]

Opciones:
    --env ENVIRONMENT       Entorno de deployment (local, staging, production)
    --branch BRANCH         Rama de Git a deployar (default: main)
    --no-backup            Omitir creación de backup
    --no-migrate           Omitir migraciones de base de datos
    --maintenance          Mostrar página de mantenimiento durante deployment
    --skip-tests           Omitir ejecución de tests
    --verbose              Modo verbose
    --help, -h             Mostrar esta ayuda

Ejemplos:
    $0                                    # Deployment básico a producción
    $0 --env staging --branch develop    # Deployment a staging
    $0 --maintenance --no-tests          # Con página de mantenimiento, sin tests
EOF
}

# Parsear argumentos
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --env)
                ENVIRONMENT="$2"
                shift 2
                ;;
            --branch)
                BRANCH="$2"
                shift 2
                ;;
            --no-backup)
                BACKUP_ENABLED=false
                shift
                ;;
            --no-migrate)
                MIGRATE_ENABLED=false
                shift
                ;;
            --maintenance)
                DOWNTIME_PAGE=true
                shift
                ;;
            --skip-tests)
                SKIP_TESTS=true
                shift
                ;;
            --verbose)
                VERBOSE=true
                set -x
                shift
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

# Función principal
main() {
    show_banner
    
    parse_arguments "$@"
    
    log_info "Iniciando deployment a $ENVIRONMENT desde rama $BRANCH"
    
    # Configurar trap para cleanup en caso de error
    trap cleanup_on_error ERR
    
    # Ejecutar pasos de deployment
    check_prerequisites
    create_backup
    show_maintenance
    update_code
    install_dependencies
    run_tests
    build_assets
    run_migrations
    clear_cache
    optimize_application
    remove_maintenance
    health_check
    notify_deployment
    
    echo -e "\n${GREEN}🎉 DEPLOYMENT COMPLETADO EXITOSAMENTE 🎉${NC}\n"
    log_success "Yega Dashboard deployado correctamente en $ENVIRONMENT"
}

# Ejecutar script principal
main "$@"