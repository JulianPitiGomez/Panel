# Guía de Instalación en Servidor Ubuntu

## Requisitos Previos
- Ubuntu 20.04 o superior
- Acceso root o sudo
- Dominio apuntando al servidor (opcional)

## 1. Instalar dependencias necesarias

```bash
# Actualizar el sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache
sudo apt install apache2 -y

# Instalar PHP 8.2 y extensiones necesarias
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl -y

# Instalar MySQL
sudo apt install mysql-server -y

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js y npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y

# Habilitar módulos de Apache necesarios
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

## 2. Clonar el proyecto

```bash
# Ir al directorio web
cd /var/www

# Clonar el repositorio
sudo git clone https://github.com/JulianPitiGomez/Panel.git panel

# Cambiar permisos del directorio
sudo chown -R www-data:www-data /var/www/panel
sudo chmod -R 755 /var/www/panel
```

## 3. Configurar el proyecto Laravel

```bash
# Entrar al directorio
cd /var/www/panel

# Instalar dependencias de PHP
sudo -u www-data composer install --optimize-autoloader --no-dev

# Instalar dependencias de Node.js
sudo -u www-data npm install

# Compilar assets
sudo -u www-data npm run build

# Copiar el archivo de configuración
sudo cp .env.example .env

# Generar la clave de aplicación
sudo -u www-data php artisan key:generate

# Configurar permisos de storage y cache
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 4. Configurar la base de datos

```bash
# Acceder a MySQL
sudo mysql

# Crear la base de datos principal
CREATE DATABASE panel_db;

# Crear usuario y dar permisos
CREATE USER 'panel_user'@'localhost' IDENTIFIED BY 'tu_contraseña_segura';
GRANT ALL PRIVILEGES ON panel_db.* TO 'panel_user'@'localhost';

# Si vas a usar múltiples bases de datos para clientes, dar permisos adicionales
GRANT ALL PRIVILEGES ON *.* TO 'panel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 5. Configurar el archivo .env

```bash
# Editar el archivo .env
sudo nano /var/www/panel/.env
```

Configurar las siguientes variables:

```env
APP_NAME="Panel Resto"
APP_ENV=production
APP_KEY=base64:... (ya generada)
APP_DEBUG=false
APP_URL=http://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=panel_db
DB_USERNAME=panel_user
DB_PASSWORD=tu_contraseña_segura

# Para las conexiones dinámicas de clientes
CLIENT_DB_HOST=127.0.0.1
CLIENT_DB_PORT=3306
CLIENT_DB_USERNAME=panel_user
CLIENT_DB_PASSWORD=tu_contraseña_segura
```

## 6. Ejecutar migraciones

```bash
cd /var/www/panel
sudo -u www-data php artisan migrate --force
```

## 7. Configurar Apache

```bash
# Copiar el archivo de configuración
sudo cp /var/www/panel/panel.conf /etc/apache2/sites-available/panel.conf

# Editar el archivo para ajustar el dominio
sudo nano /etc/apache2/sites-available/panel.conf
```

Cambiar `tu-dominio.com` por tu dominio real o IP del servidor.

```bash
# Habilitar el sitio
sudo a2ensite panel.conf

# Deshabilitar el sitio por defecto (opcional)
sudo a2dissite 000-default.conf

# Reiniciar Apache
sudo systemctl restart apache2
```

## 8. Optimizar Laravel para producción

```bash
cd /var/www/panel
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

## 9. Configurar SSL con Let's Encrypt (Recomendado)

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtener certificado SSL
sudo certbot --apache -d tu-dominio.com -d www.tu-dominio.com

# El certificado se renovará automáticamente
```

## 10. Configurar Firewall

```bash
# Permitir tráfico HTTP y HTTPS
sudo ufw allow 'Apache Full'
sudo ufw allow OpenSSH
sudo ufw enable
```

## 11. Verificar instalación

Visita tu dominio en el navegador: `http://tu-dominio.com`

## 12. Mantenimiento y Actualizaciones

Para actualizar el proyecto en el futuro:

```bash
cd /var/www/panel

# Hacer backup de la base de datos primero
sudo mysqldump -u panel_user -p panel_db > backup_$(date +%Y%m%d).sql

# Descargar cambios
sudo -u www-data git pull origin main

# Actualizar dependencias
sudo -u www-data composer install --optimize-autoloader --no-dev
sudo -u www-data npm install
sudo -u www-data npm run build

# Ejecutar migraciones si hay
sudo -u www-data php artisan migrate --force

# Limpiar y reconstruir caché
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Reiniciar Apache
sudo systemctl restart apache2
```

## Solución de Problemas

### Error 500
```bash
# Verificar logs
sudo tail -f /var/log/apache2/panel-error.log
sudo tail -f /var/www/panel/storage/logs/laravel.log
```

### Problemas de permisos
```bash
sudo chown -R www-data:www-data /var/www/panel
sudo chmod -R 775 /var/www/panel/storage
sudo chmod -R 775 /var/www/panel/bootstrap/cache
```

### Apache no inicia
```bash
# Verificar errores de configuración
sudo apache2ctl configtest

# Ver estado del servicio
sudo systemctl status apache2
```

## Notas Importantes

1. **Seguridad**: Cambia todas las contraseñas por contraseñas seguras
2. **Backup**: Configura backups automáticos de la base de datos
3. **Logs**: Monitorea los logs regularmente
4. **Actualizaciones**: Mantén el servidor y dependencias actualizadas
5. **Bases de datos de clientes**: Cada cliente debe tener su base de datos con el prefijo configurado en la tabla `clientes`
