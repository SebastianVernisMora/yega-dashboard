#!/bin/bash

# =============================================================================
# Script de Backup - Dashboard Yega
# =============================================================================
# Crea backup completo de la aplicación y base de datos
# =============================================================================

set -e

# Configuración
BACKUP_DIR="backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="yega_backup_$TIMESTAMP"
RETENTION_DAYS=30

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log() { echo -e "${GREEN}[BACKUP] $1${NC}"; }
warn() { echo -e "${YELLOW}[WARN] $1${NC}"; }
error() { echo -e "${RED}[ERROR] $1${NC}"; exit 1; }

# Crear directorio de backup
mkdir -p "$BACKUP_DIR"

log "Iniciando backup: $BACKUP_NAME"

# Backup de archivos
log "Creando backup de archivos..."
tar -czf "$BACKUP_DIR/${BACKUP_NAME}_files.tar.gz" \
    --exclude="node_modules" \
    --exclude="vendor" \
    --exclude="storage/logs" \
    --exclude=".git" \
    --exclude="backups" \
    .

# Backup de base de datos
if [ -f ".env" ]; then
    log "Creando backup de base de datos..."
    
    DB_HOST=$(grep "^DB_HOST=" .env | cut -d= -f2)
    DB_PORT=$(grep "^DB_PORT=" .env | cut -d= -f2)
    DB_USER=$(grep "^DB_USERNAME=" .env | cut -d= -f2)
    DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d= -f2)
    DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d= -f2)
    
    mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASSWORD" \
        --single-transaction --routines --triggers "$DB_NAME" \
        > "$BACKUP_DIR/${BACKUP_NAME}_database.sql"
else
    warn "Archivo .env no encontrado, saltando backup de base de datos"
fi

# Crear manifiesto
cat > "$BACKUP_DIR/${BACKUP_NAME}_manifest.txt" << EOF
Backup creado: $(date)
Tipo: Backup completo
Archivos incluidos:
- Aplicación (sin node_modules, vendor, logs)
- Base de datos MySQL
- Configuración

Para restaurar:
1. Extraer archivos: tar -xzf ${BACKUP_NAME}_files.tar.gz
2. Restaurar BD: mysql -u usuario -p base_datos < ${BACKUP_NAME}_database.sql
3. Instalar dependencias: composer install && npm install
4. Configurar .env
EOF

log "Backup completado en: $BACKUP_DIR/"
log "Archivos creados:"
ls -la "$BACKUP_DIR" | grep "$BACKUP_NAME"

# Limpiar backups antiguos
log "Limpiando backups antiguos (>$RETENTION_DAYS días)..."
find "$BACKUP_DIR" -name "yega_backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "yega_backup_*.sql" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "yega_backup_*_manifest.txt" -mtime +$RETENTION_DAYS -delete

log "Backup finalizado exitosamente"