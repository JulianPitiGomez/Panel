#!/bin/bash

# Script de instalación automática para Panel Resto en Ubuntu
# Ejecutar como root: sudo bash install-server.sh

set -e  # Detener si hay errores

echo "=========================================="
echo "Instalación de Panel Resto en Ubuntu"
echo "=========================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root: sudo bash install-server.sh"
    exit 1
fi

# 1. Actualizar el sistema
print_message "Actualizando el sistema..."
apt update && apt upgrade -y

# 2. Instalar Apache
print_message "Instalando Apache..."
apt install apache2 -y

# 3. Instalar PHP 8.2 y extensiones
print_message "Instalando PHP 8.2 y extensiones..."
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl -y

# 4. Instalar MySQL
print_message "Instalando MySQL..."
apt install mysql-server -y

# 5. Instalar Composer
print_message "Instalando Composer..."
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

# 6. Instalar Node.js 20
print_message "Instalando Node.js y npm..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install nodejs -y
fi

# 7. Habilitar módulos de Apache
print_message "Habilitando módulos de Apache..."
a2enmod rewrite
a2enmod ssl

# 8. Clonar el proyecto
print_message "Clonando el proyecto desde GitHub..."
cd /var/www
if [ -d "panel" ]; then
    print_warning "El directorio /var/www/panel ya existe. Eliminándolo..."
    rm -rf panel
fi
git clone https://github.com/JulianPitiGomez/Panel.git panel

# 9. Configurar permisos
print_message "Configurando permisos..."
chown -R www-data:www-data /var/www/panel
chmod -R 755 /var/www/panel

# 10. Instalar dependencias de Composer
print_message "Instalando dependencias de PHP (esto puede tomar varios minutos)..."
cd /var/www/panel
sudo -u www-data composer install --optimize-autoloader --no-dev

# 11. Copiar .env
print_message "Configurando archivo .env..."
if [ ! -f .env ]; then
    cp .env.example .env
fi

# 12. Generar APP_KEY
print_message "Generando clave de aplicación..."
sudo -u www-data php artisan key:generate

# 13. Instalar dependencias de Node
print_message "Instalando dependencias de Node.js (esto puede tomar varios minutos)..."
sudo -u www-data npm install

# 14. Compilar assets
print_message "Compilando assets..."
sudo -u www-data npm run build

# 15. Configurar permisos de storage
print_message "Configurando permisos de storage y cache..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 16. Pedir información de configuración
echo ""
echo "=========================================="
echo "Configuración de Base de Datos"
echo "=========================================="
echo ""

read -p "Nombre de la base de datos [panel_db]: " DB_NAME
DB_NAME=${DB_NAME:-panel_db}

read -p "Usuario de MySQL [panel_user]: " DB_USER
DB_USER=${DB_USER:-panel_user}

read -sp "Contraseña de MySQL: " DB_PASS
echo ""

read -p "Dominio o IP del servidor [localhost]: " SERVER_DOMAIN
SERVER_DOMAIN=${SERVER_DOMAIN:-localhost}

# 17. Crear base de datos y usuario
print_message "Creando base de datos y usuario..."
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# 18. Configurar .env
print_message "Configurando archivo .env..."
sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env
sed -i "s|APP_URL=http://localhost|APP_URL=http://${SERVER_DOMAIN}|" .env
sed -i "s/DB_DATABASE=laravel/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=root/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env

# 19. Ejecutar migraciones
print_message "Ejecutando migraciones de base de datos..."
sudo -u www-data php artisan migrate --force

# 20. Configurar Apache VirtualHost
print_message "Configurando Apache VirtualHost..."
cat > /etc/apache2/sites-available/panel.conf << EOF
<VirtualHost *:80>
    ServerName ${SERVER_DOMAIN}
    ServerAdmin admin@${SERVER_DOMAIN}
    DocumentRoot /var/www/panel/public

    <Directory /var/www/panel/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/panel-error.log
    CustomLog \${APACHE_LOG_DIR}/panel-access.log combined
</VirtualHost>
EOF

# 21. Habilitar el sitio
print_message "Habilitando el sitio en Apache..."
a2ensite panel.conf
a2dissite 000-default.conf

# 22. Optimizar Laravel
print_message "Optimizando Laravel para producción..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 23. Reiniciar Apache
print_message "Reiniciando Apache..."
systemctl restart apache2

# 24. Configurar firewall (si ufw está instalado)
if command -v ufw &> /dev/null; then
    print_message "Configurando firewall..."
    ufw allow 'Apache Full'
    ufw allow OpenSSH
    echo "y" | ufw enable
fi

echo ""
echo "=========================================="
print_message "¡Instalación completada exitosamente!"
echo "=========================================="
echo ""
echo "Información de acceso:"
echo "  URL: http://${SERVER_DOMAIN}"
echo "  Base de datos: ${DB_NAME}"
echo "  Usuario BD: ${DB_USER}"
echo ""
echo "Archivos importantes:"
echo "  Proyecto: /var/www/panel"
echo "  Logs Apache: /var/log/apache2/panel-error.log"
echo "  Logs Laravel: /var/www/panel/storage/logs/laravel.log"
echo ""
print_warning "IMPORTANTE: Configura SSL con Let's Encrypt ejecutando:"
echo "  certbot --apache -d ${SERVER_DOMAIN}"
echo ""
