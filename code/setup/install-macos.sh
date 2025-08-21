#!/bin/bash

# =============================================================================
# Script de Instalación para macOS - Dashboard Yega
# Versión: 1.0.0
# Descripción: Instalación optimizada para macOS usando Homebrew
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

# Versiones
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

# Verificar macOS
check_macos() {
    if [[ "$OSTYPE" != "darwin"* ]]; then
        log_error "Este script está diseñado para macOS."
        exit 1
    fi
    
    MACOS_VERSION=$(sw_vers -productVersion)
    log_info "macOS detectado: $MACOS_VERSION"
}

# Instalar Homebrew
install_homebrew() {
    log_step "Verificando/Instalando Homebrew"
    
    if ! command -v brew &> /dev/null; then
        log_info "Homebrew no encontrado. Instalando..."
        /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
        
        # Añadir Homebrew al PATH
        echo 'eval "$(/opt/homebrew/bin/brew shellenv)"' >> ~/.zprofile
        eval "$(/opt/homebrew/bin/brew shellenv)"
    else
        log_info "Homebrew ya instalado. Actualizando..."
        brew update
    fi
    
    log_success "Homebrew listo"
}

# Instalar Xcode Command Line Tools
install_xcode_tools() {
    log_step "Instalando Xcode Command Line Tools"
    
    if ! xcode-select -p &> /dev/null; then
        log_info "Instalando Xcode Command Line Tools..."
        xcode-select --install
        
        echo "Presiona Enter cuando termine la instalación de Xcode Command Line Tools..."
        read -r
    else
        log_info "Xcode Command Line Tools ya instalado"
    fi
    
    log_success "Xcode Command Line Tools listo"
}

# Instalar PHP
install_php() {
    log_step "Instalando PHP $PHP_VERSION"
    
    # Instalar PHP y extensiones
    brew install php@${PHP_VERSION}
    
    # Añadir PHP al PATH
    echo 'export PATH="/opt/homebrew/opt/php@'${PHP_VERSION}'/bin:$PATH"' >> ~/.zprofile
    echo 'export PATH="/opt/homebrew/opt/php@'${PHP_VERSION}'/sbin:$PATH"' >> ~/.zprofile
    export PATH="/opt/homebrew/opt/php@${PHP_VERSION}/bin:$PATH"
    export PATH="/opt/homebrew/opt/php@${PHP_VERSION}/sbin:$PATH"
    
    # Instalar extensiones adicionales
    brew install php-cs-fixer phpstan
    
    # Configurar PHP
    PHP_INI="/opt/homebrew/etc/php/${PHP_VERSION}/php.ini"
    sed -i '' 's/memory_limit = .*/memory_limit = 512M/' $PHP_INI
    sed -i '' 's/max_execution_time = .*/max_execution_time = 300/' $PHP_INI
    sed -i '' 's/upload_max_filesize = .*/upload_max_filesize = 64M/' $PHP_INI
    sed -i '' 's/post_max_size = .*/post_max_size = 64M/' $PHP_INI
    
    log_success "PHP $PHP_VERSION instalado"
}

# Instalar MySQL
install_mysql() {
    log_step "Instalando MySQL $MYSQL_VERSION"
    
    brew install mysql@${MySQL_VERSION}
    
    # Iniciar MySQL
    brew services start mysql@${MySQL_VERSION}
    
    # Añadir MySQL al PATH
    echo 'export PATH="/opt/homebrew/opt/mysql@'${MySQL_VERSION}'/bin:$PATH"' >> ~/.zprofile
    export PATH="/opt/homebrew/opt/mysql@${MySQL_VERSION}/bin:$PATH"
    
    # Configuración de seguridad básica
    sleep 5  # Esperar a que MySQL inicie completamente
    
    log_info "Configurando MySQL..."
    mysql -u root << EOF || true
ALTER USER 'root'@'localhost' IDENTIFIED BY 'root';
FLUSH PRIVILEGES;
EOF
    
    log_success "MySQL $MySQL_VERSION instalado"
}

# Instalar Node.js
install_nodejs() {
    log_step "Instalando Node.js $NODE_VERSION"
    
    # Usar Node Version Manager para mejor gestión
    if ! command -v nvm &> /dev/null; then
        log_info "Instalando NVM..."
        curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
        
        # Cargar NVM
        export NVM_DIR="$HOME/.nvm"
        [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
        [ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"
    fi
    
    # Instalar Node.js con NVM
    nvm install ${NODE_VERSION}
    nvm use ${NODE_VERSION}
    nvm alias default ${NODE_VERSION}
    
    # Actualizar NPM
    npm install -g npm@latest
    
    # Instalar herramientas globales
    npm install -g yarn pm2 nodemon typescript
    
    log_success "Node.js $NODE_VERSION instalado"
}

# Instalar Composer
install_composer() {
    log_step "Instalando Composer"
    
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
    
    log_success "Composer instalado"
}

# Instalar herramientas adicionales
install_additional_tools() {
    log_step "Instalando herramientas adicionales"
    
    # Git (normalmente ya viene con macOS)
    brew install git
    
    # Redis
    brew install redis
    brew services start redis
    
    # Nginx (opcional)
    read -p "¿Instalar Nginx como servidor web? (y/N): " install_nginx
    if [[ $install_nginx =~ ^[Yy]$ ]]; then
        brew install nginx
        brew services start nginx
        log_success "Nginx instalado"
    fi
    
    # Herramientas de desarrollo
    brew install \
        wget \
        curl \
        htop \
        tree \
        jq \
        imagemagick \
        ghostscript \
        ffmpeg
    
    log_success "Herramientas adicionales instaladas"
}

# Configurar entorno de desarrollo
setup_dev_environment() {
    log_step "Configurando entorno de desarrollo"
    
    # Configurar Git (si no está configurado)
    if [ -z "$(git config --global user.name)" ]; then
        read -p "Introduce tu nombre para Git: " git_name
        git config --global user.name "$git_name"
    fi
    
    if [ -z "$(git config --global user.email)" ]; then
        read -p "Introduce tu email para Git: " git_email
        git config --global user.email "$git_email"
    fi
    
    # Configurar shell para desarrollo
    if [ "$SHELL" = "/bin/zsh" ]; then
        # Instalar Oh My Zsh si no está instalado
        if [ ! -d "$HOME/.oh-my-zsh" ]; then
            read -p "¿Instalar Oh My Zsh para mejor experiencia en terminal? (y/N): " install_omz
            if [[ $install_omz =~ ^[Yy]$ ]]; then
                sh -c "$(curl -fsSL https://raw.github.com/ohmyzsh/ohmyzsh/master/tools/install.sh)"
            fi
        fi
    fi
    
    log_success "Entorno de desarrollo configurado"
}

# Optimizar configuración
optimize_system() {
    log_step "Optimizando configuración del sistema"
    
    # Configurar límites del sistema para desarrollo
    echo "kern.maxfiles=65536" | sudo tee -a /etc/sysctl.conf
    echo "kern.maxfilesperproc=65536" | sudo tee -a /etc/sysctl.conf
    
    # Configurar MySQL
    MYSQL_CNF="/opt/homebrew/etc/my.cnf"
    if [ ! -f "$MYSQL_CNF" ]; then
        sudo tee "$MYSQL_CNF" << EOF
[mysqld]
# Optimizaciones para Yega Dashboard
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
max_connections = 200
query_cache_size = 32M
query_cache_type = 1
thread_cache_size = 16
table_open_cache = 2000
EOF
    fi
    
    # Reiniciar MySQL para aplicar cambios
    brew services restart mysql@${MySQL_VERSION}
    
    log_success "Sistema optimizado"
}

# Función principal
main() {
    echo -e "${PURPLE}"
    cat << "EOF"
┌────────────────────────────────────────────────────────────────────────────────┐
│                🍎 YEGA DASHBOARD - INSTALACIÓN macOS 🍎                │
│                       Homebrew Installer v1.0.0                       │
└────────────────────────────────────────────────────────────────────────────────┘
EOF
    echo -e "${NC}\n"
    
    check_macos
    install_xcode_tools
    install_homebrew
    install_php
    install_mysql
    install_nodejs
    install_composer
    install_additional_tools
    setup_dev_environment
    optimize_system
    
    echo -e "\n${GREEN}✅ Instalación del sistema completada en macOS${NC}\n"
    echo -e "${YELLOW}Reinicia tu terminal para aplicar todos los cambios.${NC}\n"
    echo -e "${YELLOW}Luego ejecuta el instalador principal:${NC}"
    echo -e "${GREEN}./code/setup/install.sh${NC}\n"
}

# Ejecutar
main "$@"