#!/bin/bash

# =============================================================================
# Script de Verificación del Sistema - Dashboard Yega
# =============================================================================
# Verifica que todos los requisitos estén instalados y configurados correctamente
# =============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Contadores
ERRORS=0
WARNINGS=0

log() { echo -e "${GREEN}[CHECK] $1${NC}"; }
warn() { echo -e "${YELLOW}[WARN] $1${NC}"; ((WARNINGS++)); }
error() { echo -e "${RED}[ERROR] $1${NC}"; ((ERRORS++)); }
info() { echo -e "${BLUE}[INFO] $1${NC}"; }

check_command() {
    local cmd=$1
    local name=$2
    local min_version=$3
    
    if command -v "$cmd" &> /dev/null; then
        local version=$("$cmd" --version 2>/dev/null | head -n1 || echo "unknown")
        log "$name está instalado: $version"
        return 0
    else
        error "$name no está instalado"
        return 1
    fi
}

check_php_version() {
    if command -v php &> /dev/null; then
        local version=$(php -r "echo PHP_VERSION;")
        local major=$(echo $version | cut -d. -f1)
        local minor=$(echo $version | cut -d. -f2)
        
        if [ "$major" -gt 8 ] || [ "$major" -eq 8 -a "$minor" -ge 1 ]; then
            log "PHP $version ✓"
        else
            error "PHP 8.1+ requerido, encontrado: $version"
        fi
    else
        error "PHP no está instalado"
    fi
}

check_php_extensions() {
    local required=("pdo" "pdo_mysql" "mbstring" "xml" "curl" "json" "tokenizer" "bcmath" "ctype" "fileinfo" "openssl" "zip")
    
    for ext in "${required[@]}"; do
        if php -m | grep -q "^$ext$"; then
            log "Extensión PHP $ext ✓"
        else
            error "Extensión PHP $ext faltante"
        fi
    done
}

check_node_version() {
    if command -v node &> /dev/null; then
        local version=$(node --version | cut -c2-)
        local major=$(echo $version | cut -d. -f1)
        
        if [ "$major" -ge 16 ]; then
            log "Node.js $version ✓"
        else
            warn "Node.js 16+ recomendado, encontrado: $version"
        fi
    else
        error "Node.js no está instalado"
    fi
}

check_mysql_connection() {
    if [ -f ".env" ]; then
        local host=$(grep "^DB_HOST=" .env | cut -d= -f2)
        local port=$(grep "^DB_PORT=" .env | cut -d= -f2)
        local user=$(grep "^DB_USERNAME=" .env | cut -d= -f2)
        local password=$(grep "^DB_PASSWORD=" .env | cut -d= -f2)
        local database=$(grep "^DB_DATABASE=" .env | cut -d= -f2)
        
        if mysql -h"$host" -P"$port" -u"$user" -p"$password" -e "USE $database; SELECT 1;" &>/dev/null; then
            log "Conexión MySQL ✓"
        else
            error "No se puede conectar a MySQL"
        fi
    else
        warn "Archivo .env no encontrado"
    fi
}

check_github_token() {
    if [ -f ".env" ]; then
        local token=$(grep "^GITHUB_TOKEN=" .env | cut -d= -f2)
        
        if [ -n "$token" ] && [ "$token" != "ghp_tu_personal_access_token_aqui" ]; then
            local response=$(curl -s -H "Authorization: token $token" https://api.github.com/user)
            if echo "$response" | grep -q '"login"'; then
                log "GitHub token válido ✓"
            else
                error "GitHub token inválido"
            fi
        else
            warn "GitHub token no configurado"
        fi
    fi
}

check_file_permissions() {
    local dirs=("storage" "bootstrap/cache" "public/uploads" "logs")
    
    for dir in "${dirs[@]}"; do
        if [ -d "$dir" ]; then
            if [ -w "$dir" ]; then
                log "Permisos $dir ✓"
            else
                error "Sin permisos de escritura en $dir"
            fi
        else
            warn "Directorio $dir no existe"
        fi
    done
}

check_project_files() {
    local files=("composer.json" "package.json" ".env")
    
    for file in "${files[@]}"; do
        if [ -f "$file" ]; then
            log "Archivo $file ✓"
        else
            if [ "$file" == ".env" ]; then
                warn "Archivo $file no encontrado (opcional)"
            else
                error "Archivo $file no encontrado"
            fi
        fi
    done
}

main() {
    echo -e "${BLUE}"
    echo "======================================"
    echo "  Verificación del Sistema - Yega"
    echo "======================================"
    echo -e "${NC}"
    
    info "Verificando herramientas del sistema..."
    check_command "git" "Git"
    check_command "curl" "cURL"
    check_command "wget" "Wget"
    check_command "composer" "Composer"
    check_command "npm" "NPM"
    
    echo
    info "Verificando PHP..."
    check_php_version
    check_php_extensions
    
    echo
    info "Verificando Node.js..."
    check_node_version
    
    echo
    info "Verificando MySQL..."
    check_command "mysql" "MySQL"
    check_mysql_connection
    
    echo
    info "Verificando configuración..."
    check_project_files
    check_github_token
    
    echo
    info "Verificando permisos..."
    check_file_permissions
    
    echo
    echo "======================================"
    echo "           RESUMEN"
    echo "======================================"
    
    if [ $ERRORS -eq 0 ]; then
        log "Sistema listo para Dashboard Yega ✓"
    else
        error "Se encontraron $ERRORS errores"
    fi
    
    if [ $WARNINGS -gt 0 ]; then
        warn "Se encontraron $WARNINGS advertencias"
    fi
    
    echo "Errores: $ERRORS | Advertencias: $WARNINGS"
    
    if [ $ERRORS -gt 0 ]; then
        exit 1
    fi
}

main "$@"