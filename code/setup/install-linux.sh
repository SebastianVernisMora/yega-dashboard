#!/bin/bash

# =============================================================================
# Script de Instalación para Linux (Ubuntu/Debian) - Dashboard Yega
# Versión: 1.0.0
# Descripción: Instalación optimizada para sistemas Ubuntu/Debian
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
PHP_VERSION="8.2"
NODE_VERSION="18"
MYSQL_VERSION="8.0"

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "\n${CYAN}━━━ $1 ━━━${NC}"
}

# Actualizar repositorios
update_repositories() {
    log_step "Actualizando repositorios del sistema"
    
    sudo apt update
    sudo apt upgrade -y
    
    # Añadir repositorios necesarios
    sudo apt install -y software-properties-common apt-transport-https ca-certificates gnupg lsb-release curl wget
    
    # Repositorio de PHP
    sudo add-apt-repository ppa:ondrej/php -y
    
    # Repositorio de Node.js
    curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | sudo -E bash -
    
    # Repositorio de MySQL
    wget https://dev.mysql.com/get/mysql-apt-config_0.8.25-1_all.deb
    sudo dpkg -i mysql-apt-config_0.8.25-1_all.deb || true
    sudo apt-get update
    
    log_success "Repositorios actualizados"
}

# Instalar PHP y extensiones
install_php() {
    log_step "Instalando PHP $PHP_VERSION y extensiones"
    
    sudo apt install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-pdo \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-json \
        php${PHP_VERSION}-tokenizer \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-ctype \
        php${PHP_VERSION}-fileinfo \
        php${PHP_VERSION}-openssl \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-dev
    
    # Configurar PHP como versión por defecto
    sudo update-alternatives --set php /usr/bin/php${PHP_VERSION}
    
    log_success "PHP $PHP_VERSION instalado"
}

# Instalar MySQL
install_mysql() {
    log_step "Instalando MySQL $MYSQL_VERSION"
    
    # Configuración previa para instalación no interactiva
    echo "mysql-server mysql-server/root_password password root" | sudo debconf-set-selections
    echo "mysql-server mysql-server/root_password_again password root" | sudo debconf-set-selections
    
    sudo apt install -y mysql-server mysql-client
    
    # Iniciar y habilitar MySQL
    sudo systemctl start mysql
    sudo systemctl enable mysql
    
    # Configuración de seguridad básica
    sudo mysql -u root -proot << EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%';
FLUSH PRIVILEGES;
EOF
    
    log_success "MySQL $MYSQL_VERSION instalado"
}

# Instalar Node.js y NPM
install_nodejs() {
    log_step "Instalando Node.js $NODE_VERSION"
    
    sudo apt install -y nodejs npm
    
    # Actualizar NPM a la última versión
    sudo npm install -g npm@latest
    
    # Instalar herramientas globales útiles
    sudo npm install -g yarn pm2 nodemon
    
    log_success "Node.js $NODE_VERSION y NPM instalados"
}

# Instalar Composer
install_composer() {
    log_step "Instalando Composer"
    
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
    
    # Verificar instalación
    composer --version
    
    log_success "Composer instalado"
}

# Instalar herramientas adicionales
install_additional_tools() {
    log_step "Instalando herramientas adicionales"
    
    # Git (si no está instalado)
    sudo apt install -y git
    
    # Redis para cache
    sudo apt install -y redis-server
    sudo systemctl start redis-server
    sudo systemctl enable redis-server
    
    # Nginx (opcional)
    read -p "¿Instalar Nginx como servidor web? (y/N): " install_nginx
    if [[ $install_nginx =~ ^[Yy]$ ]]; then
        sudo apt install -y nginx
        sudo systemctl start nginx
        sudo systemctl enable nginx
        log_success "Nginx instalado"
    fi
    
    # Supervisor para procesos en background
    sudo apt install -y supervisor
    sudo systemctl start supervisor
    sudo systemctl enable supervisor
    
    # Herramientas de desarrollo
    sudo apt install -y \
        vim \
        htop \
        tree \
        jq \
        unzip \
        zip \
        imagemagick \
        ghostscript
    
    log_success "Herramientas adicionales instaladas"
}

# Configurar firewall
setup_firewall() {
    log_step "Configurando firewall UFW"
    
    sudo ufw --force enable
    sudo ufw default deny incoming
    sudo ufw default allow outgoing
    
    # Permitir servicios necesarios
    sudo ufw allow ssh
    sudo ufw allow 80/tcp    # HTTP
    sudo ufw allow 443/tcp   # HTTPS
    sudo ufw allow 8000/tcp  # Servidor de desarrollo
    sudo ufw allow 3306/tcp  # MySQL (solo si es necesario externamente)
    
    sudo ufw --force reload
    
    log_success "Firewall configurado"
}

# Optimizar configuración del sistema
optimize_system() {
    log_step "Optimizando configuración del sistema"
    
    # Configuración PHP optimizada
    PHP_INI="/etc/php/${PHP_VERSION}/cli/php.ini"
    sudo sed -i 's/memory_limit = .*/memory_limit = 512M/' $PHP_INI
    sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' $PHP_INI
    sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' $PHP_INI
    sudo sed -i 's/post_max_size = .*/post_max_size = 64M/' $PHP_INI
    
    # Configuración MySQL optimizada
    sudo tee -a /etc/mysql/mysql.conf.d/mysqld.cnf << EOF

# Optimizaciones para Yega Dashboard
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
max_connections = 200
query_cache_size = 32M
query_cache_type = 1
thread_cache_size = 16
table_open_cache = 2000
EOF
    
    sudo systemctl restart mysql
    
    log_success "Sistema optimizado"
}

# Función principal
main() {
    echo -e "${PURPLE}"
    cat << "EOF"
┌────────────────────────────────────────────────────────────────────────────────┐
│              🐧 YEGA DASHBOARD - INSTALACIÓN LINUX 🐧              │
│                     Ubuntu/Debian Installer v1.0.0                    │
└────────────────────────────────────────────────────────────────────────────────┘
EOF
    echo -e "${NC}\n"
    
    # Verificar que se ejecuta como usuario con sudo
    if [ "$EUID" -eq 0 ]; then
        log_error "No ejecutes este script como root. Úsalo con un usuario que tenga acceso sudo."
        exit 1
    fi
    
    # Verificar que es Ubuntu/Debian
    if [ ! -f /etc/debian_version ]; then
        log_error "Este script está diseñado para sistemas Ubuntu/Debian."
        exit 1
    fi
    
    # Ejecutar instalación
    update_repositories
    install_php
    install_mysql
    install_nodejs
    install_composer
    install_additional_tools
    setup_firewall
    optimize_system
    
    echo -e "\n${GREEN}✅ Instalación del sistema completada${NC}\n"
    echo -e "${YELLOW}Ahora puedes ejecutar el instalador principal:${NC}"
    echo -e "${GREEN}./code/setup/install.sh${NC}\n"
}

# Ejecutar
main "$@"