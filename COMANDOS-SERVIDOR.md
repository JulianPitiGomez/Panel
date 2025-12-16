# Comandos para Instalar en el Servidor Ubuntu (como root)

## Paso 1: Conectarse al servidor por SSH

Desde tu computadora local (Windows), abre PowerShell o CMD y ejecuta:

```bash
ssh root@tu-ip-del-servidor
```

O si tienes usuario con sudo:
```bash
ssh tu-usuario@tu-ip-del-servidor
```

Luego cambia a root:
```bash
sudo su
```

---

## Paso 2: Descargar el script de instalación

Una vez conectado como root en el servidor Ubuntu, ejecuta:

```bash
cd /tmp
wget https://raw.githubusercontent.com/JulianPitiGomez/Panel/main/install-server.sh
chmod +x install-server.sh
```

---

## Paso 3: Ejecutar el script de instalación

```bash
bash install-server.sh
```

El script te pedirá la siguiente información:

1. **Nombre de la base de datos** (default: panel_db)
2. **Usuario de MySQL** (default: panel_user)
3. **Contraseña de MySQL** (debes elegir una segura)
4. **Dominio o IP del servidor** (ejemplo: panel.tudominio.com o la IP)

El script hará automáticamente:
- ✅ Actualizar el sistema
- ✅ Instalar Apache, PHP 8.2, MySQL, Composer, Node.js
- ✅ Clonar el proyecto desde GitHub
- ✅ Instalar todas las dependencias
- ✅ Configurar la base de datos
- ✅ Configurar Apache
- ✅ Compilar los assets
- ✅ Configurar permisos
- ✅ Optimizar Laravel

---

## Paso 4: Verificar la instalación

Después de que el script termine, verifica que todo funcione:

```bash
# Ver si Apache está corriendo
systemctl status apache2

# Ver logs si hay problemas
tail -f /var/log/apache2/panel-error.log
tail -f /var/www/panel/storage/logs/laravel.log
```

Abre tu navegador y ve a: `http://tu-dominio-o-ip`

---

## Paso 5 (Opcional): Configurar SSL con Let's Encrypt

Si tienes un dominio (no funciona con IP):

```bash
apt install certbot python3-certbot-apache -y
certbot --apache -d tu-dominio.com -d www.tu-dominio.com
```

---

## Comandos Útiles para el Futuro

### Actualizar el proyecto cuando hagas cambios:

```bash
cd /var/www/panel
git pull origin main
sudo -u www-data composer install --optimize-autoloader --no-dev
sudo -u www-data npm install
sudo -u www-data npm run build
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
systemctl restart apache2
```

### Ver logs en tiempo real:

```bash
# Logs de Apache
tail -f /var/log/apache2/panel-error.log

# Logs de Laravel
tail -f /var/www/panel/storage/logs/laravel.log
```

### Reiniciar servicios:

```bash
systemctl restart apache2
systemctl restart mysql
```

### Verificar estado de servicios:

```bash
systemctl status apache2
systemctl status mysql
```

### Hacer backup de la base de datos:

```bash
mysqldump -u panel_user -p panel_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar backup:

```bash
mysql -u panel_user -p panel_db < backup_FECHA.sql
```

---

## Solución de Problemas Comunes

### Error 500 - Internal Server Error

```bash
# Ver qué está fallando
tail -100 /var/www/panel/storage/logs/laravel.log

# Verificar permisos
chown -R www-data:www-data /var/www/panel
chmod -R 775 /var/www/panel/storage
chmod -R 775 /var/www/panel/bootstrap/cache

# Limpiar caché
cd /var/www/panel
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan view:clear
```

### Apache no inicia

```bash
# Ver errores de configuración
apache2ctl configtest

# Ver logs
journalctl -xe
```

### Problema con base de datos

```bash
# Verificar que MySQL está corriendo
systemctl status mysql

# Conectarse a MySQL para verificar
mysql -u panel_user -p
```

### Permisos de archivos

```bash
# Resetear todos los permisos
cd /var/www/panel
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

## Resumen Rápido (Solo comandos)

```bash
# Conectar
ssh root@tu-ip

# Descargar e instalar
cd /tmp
wget https://raw.githubusercontent.com/JulianPitiGomez/Panel/main/install-server.sh
chmod +x install-server.sh
bash install-server.sh

# Seguir las instrucciones del script
# Al terminar, abrir http://tu-dominio-o-ip en el navegador
```

---

## Información Importante

- **Proyecto**: `/var/www/panel`
- **Configuración**: `/var/www/panel/.env`
- **Logs Apache**: `/var/log/apache2/panel-error.log`
- **Logs Laravel**: `/var/www/panel/storage/logs/laravel.log`
- **Config Apache**: `/etc/apache2/sites-available/panel.conf`
- **Usuario web**: `www-data` (Apache corre con este usuario)
