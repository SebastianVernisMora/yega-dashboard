# PowerShell Script para Instalación en Windows - Dashboard Yega
# Versión: 1.0.0
# Descripción: Instalación automatizada para Windows usando Chocolatey

param(
    [switch]$Dev,
    [switch]$Production,
    [switch]$Silent,
    [string]$GitHubToken = "",
    [string]$DatabasePassword = ""
)

# Colores para PowerShell
$colors = @{
    Red = "Red"
    Green = "Green"
    Yellow = "Yellow"
    Blue = "Blue"
    Purple = "Magenta"
    Cyan = "Cyan"
}

# Funciones de logging
function Write-InfoLog {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor $colors.Blue
}

function Write-SuccessLog {
    param([string]$Message)
    Write-Host "[SUCCESS] $Message" -ForegroundColor $colors.Green
}

function Write-ErrorLog {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor $colors.Red
}

function Write-StepLog {
    param([string]$Message)
    Write-Host "`n--- $Message ---" -ForegroundColor $colors.Cyan
}

# Banner de bienvenida
function Show-Banner {
    Write-Host @"
╔════════════════════════════════════════════════════════════════════════════════╗
║                    🚀 YEGA DASHBOARD - INSTALACIÓN WINDOWS 🚀                 ║
║                         PowerShell Installer v1.0.0                          ║
╠════════════════════════════════════════════════════════════════════════════════╣
║ • Verificación de requisitos del sistema                                      ║
║ • Instalación de dependencias PHP y Node.js                                  ║
║ • Configuración de base de datos MySQL                                       ║
║ • Setup de variables de entorno                                              ║
║ • Migraciones de Prisma                                                      ║
║ • Compilación de assets frontend                                             ║
║ • Inicio del servidor de desarrollo                                          ║
╚════════════════════════════════════════════════════════════════════════════════╝
"@ -ForegroundColor $colors.Purple
}

# Verificar si se ejecuta como administrador
function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# Instalar Chocolatey
function Install-Chocolatey {
    Write-StepLog "Verificando/Instalando Chocolatey"
    
    if (!(Get-Command choco -ErrorAction SilentlyContinue)) {
        Write-InfoLog "Chocolatey no encontrado. Instalando..."
        Set-ExecutionPolicy Bypass -Scope Process -Force
        [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
        iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
        
        # Actualizar PATH
        $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    } else {
        Write-InfoLog "Chocolatey ya instalado. Actualizando..."
        choco upgrade chocolatey -y
    }
    
    Write-SuccessLog "Chocolatey listo"
}

# Instalar PHP
function Install-PHP {
    Write-StepLog "Instalando PHP 8.2"
    
    # Instalar PHP via Chocolatey
    choco install php --version=8.2.0 -y
    
    # Configurar PHP
    $phpIniPath = "C:\tools\php82\php.ini"
    if (Test-Path "C:\tools\php82\php.ini-development") {
        Copy-Item "C:\tools\php82\php.ini-development" $phpIniPath
    }
    
    # Configuraciones optimizadas
    $phpConfig = @"
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
extension_dir = "C:\tools\php82\ext"
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip
"@
    
    Add-Content -Path $phpIniPath -Value $phpConfig
    
    Write-SuccessLog "PHP 8.2 instalado"
}

# Instalar MySQL
function Install-MySQL {
    Write-StepLog "Instalando MySQL 8.0"
    
    choco install mysql --version=8.0.33 -y
    
    # Configurar MySQL
    $mysqlService = Get-Service -Name "MySQL80" -ErrorAction SilentlyContinue
    if ($mysqlService) {
        Start-Service -Name "MySQL80"
        Set-Service -Name "MySQL80" -StartupType Automatic
    }
    
    Write-SuccessLog "MySQL 8.0 instalado"
}

# Instalar Node.js
function Install-NodeJS {
    Write-StepLog "Instalando Node.js 18"
    
    choco install nodejs --version=18.17.0 -y
    
    # Actualizar NPM
    npm install -g npm@latest
    
    # Instalar herramientas globales
    npm install -g yarn pm2 nodemon typescript
    
    Write-SuccessLog "Node.js 18 instalado"
}

# Instalar Composer
function Install-Composer {
    Write-StepLog "Instalando Composer"
    
    choco install composer -y
    
    Write-SuccessLog "Composer instalado"
}

# Instalar Git
function Install-Git {
    Write-StepLog "Instalando Git"
    
    choco install git -y
    
    Write-SuccessLog "Git instalado"
}

# Configurar base de datos
function Setup-Database {
    Write-StepLog "Configurando base de datos"
    
    if (!$Silent) {
        if (!$DatabasePassword) {
            $DatabasePassword = Read-Host "Ingresa una contraseña para el usuario yega_user" -AsSecureString
            $DatabasePassword = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($DatabasePassword))
        }
    }
    
    # Crear script SQL
    $sqlScript = @"
CREATE DATABASE IF NOT EXISTS yega_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'yega_user'@'localhost' IDENTIFIED BY '$DatabasePassword';
GRANT ALL PRIVILEGES ON yega_dashboard.* TO 'yega_user'@'localhost';
FLUSH PRIVILEGES;
"@
    
    $sqlScript | Out-File -FilePath "setup_db.sql" -Encoding UTF8
    
    # Ejecutar script SQL
    mysql -u root -p < setup_db.sql
    
    Remove-Item "setup_db.sql"
    
    Write-SuccessLog "Base de datos configurada"
}

# Configurar variables de entorno
function Setup-Environment {
    Write-StepLog "Configurando variables de entorno"
    
    if (!(Test-Path ".env")) {
        $envContent = @"
APP_NAME="Yega Dashboard"
APP_ENV=$(if ($Production) { "production" } else { "development" })
APP_DEBUG=$(if ($Production) { "false" } else { "true" })
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=yega_dashboard
DB_USERNAME=yega_user
DB_PASSWORD=$DatabasePassword

DATABASE_URL="mysql://yega_user:$DatabasePassword@localhost:3306/yega_dashboard"

GITHUB_TOKEN=$GitHubToken
GITHUB_API_URL=https://api.github.com

CACHE_DRIVER=file
SESSION_DRIVER=file
LOG_CHANNEL=daily
LOG_LEVEL=info
"@
        
        $envContent | Out-File -FilePath ".env" -Encoding UTF8
        
        Write-SuccessLog "Variables de entorno configuradas"
    } else {
        Write-InfoLog "Archivo .env ya existe, saltando configuración"
    }
}

# Instalar dependencias
function Install-Dependencies {
    Write-StepLog "Instalando dependencias"
    
    # Dependencias PHP
    if ($Production) {
        composer install --no-dev --optimize-autoloader --no-interaction
    } else {
        composer install --no-interaction
    }
    
    # Dependencias Node.js
    npm install
    
    Write-SuccessLog "Dependencias instaladas"
}

# Ejecutar migraciones
function Run-Migrations {
    Write-StepLog "Ejecutando migraciones"
    
    npx prisma generate
    
    if ($Dev) {
        npx prisma migrate dev --name "instalacion_inicial"
    } else {
        npx prisma migrate deploy
    }
    
    Write-SuccessLog "Migraciones ejecutadas"
}

# Compilar assets
function Build-Assets {
    Write-StepLog "Compilando assets"
    
    if ($Production) {
        npm run build
    } else {
        Start-Job -ScriptBlock { npm run dev }
    }
    
    Write-SuccessLog "Assets compilados"
}

# Función principal
function Main {
    Show-Banner
    
    # Verificar permisos de administrador
    if (!(Test-Administrator)) {
        Write-ErrorLog "Este script debe ejecutarse como Administrador."
        Write-InfoLog "Haz clic derecho en PowerShell y selecciona 'Ejecutar como administrador'."
        exit 1
    }
    
    # Verificar que estamos en el directorio correcto
    if (!(Test-Path "composer.json") -or !(Test-Path "package.json")) {
        Write-ErrorLog "No se encontraron archivos composer.json o package.json."
        Write-ErrorLog "Asegúrate de estar en el directorio raíz del proyecto Yega Dashboard."
        exit 1
    }
    
    try {
        Install-Chocolatey
        Install-Git
        Install-PHP
        Install-MySQL
        Install-NodeJS
        Install-Composer
        Setup-Database
        Setup-Environment
        Install-Dependencies
        Run-Migrations
        Build-Assets
        
        Write-Host "`n✅ INSTALACIÓN COMPLETADA" -ForegroundColor $colors.Green
        Write-Host "`nPróximos pasos:" -ForegroundColor $colors.Yellow
        Write-Host "1. Configurar tu token de GitHub en el archivo .env" -ForegroundColor $colors.Yellow
        Write-Host "2. Iniciar el servidor: composer serve" -ForegroundColor $colors.Green
        Write-Host "3. Abrir en el navegador: http://localhost:8000" -ForegroundColor $colors.Blue
        
    } catch {
        Write-ErrorLog "Error durante la instalación: $($_.Exception.Message)"
        exit 1
    }
}

# Ejecutar script principal
Main