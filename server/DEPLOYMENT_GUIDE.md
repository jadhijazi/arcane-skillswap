# SkillSwap Backend - Deployment Guide

## Prerequisites

- PHP 8.3+
- MySQL 8.0+
- Composer 2.0+
- Git

## Local Setup

### 1. Clone Repository

```bash
git clone https://github.com/arcane/skillswap.git
cd skillswap/server
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Create `.env` file in project root:

```env
# App Settings
APP_ENV=development
APP_NAME=SkillSwap
APP_DEBUG=true

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=skillswap
DB_USERNAME=root
DB_PASSWORD=password

# JWT Settings
JWT_SECRET=your-super-secret-key-change-in-production
JWT_ISSUER=skillswap.local
JWT_AUDIENCE=skillswap.local
JWT_ACCESS_TTL=900
JWT_REFRESH_TTL=604800

# Platform Settings
PLATFORM_COMMISSION=0.10

# Deployment
TIMEZONE=UTC
```

### 4. Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE skillswap;
USE skillswap;
source db/schema.sql;
source db/seeders.sql;
```

### 5. Run Development Server

```bash
composer start
```

Server runs on `http://localhost:8080`

---

## Production Deployment

### 1. Server Requirements

- Ubuntu 20.04 LTS (or equivalent)
- 2GB RAM minimum
- 10GB disk space
- SSL certificate (Let's Encrypt recommended)

### 2. Install System Dependencies

```bash
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-json php8.3-xml php8.3-curl php8.3-zip php8.3-fpm
  
sudo apt install -y mysql-server
sudo apt install -y nginx
sudo apt install -y composer
```

### 3. Create Application User

```bash
sudo useradd -m -s /bin/bash skillswap
sudo mkdir -p /var/www/skillswap
sudo chown skillswap:skillswap /var/www/skillswap
```

### 4. Deploy Application

```bash
cd /var/www/skillswap
sudo -u skillswap git clone https://github.com/arcane/skillswap.git .
sudo -u skillswap composer install --optimize-autoloader --no-dev
```

### 5. Configure Nginx

Create `/etc/nginx/sites-available/skillswap`:

```nginx
server {
    listen 80;
    server_name api.skillswap.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.skillswap.com;
    
    root /var/www/skillswap/public;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.skillswap.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.skillswap.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # GZIP Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1000;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;
    
    # PHP Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_intercept_errors on;
    }
    
    # Deny Access to Sensitive Files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    location ~ ~$ {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    # Route All Requests to index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Logging
    access_log /var/log/nginx/skillswap_access.log;
    error_log /var/log/nginx/skillswap_error.log;
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/skillswap /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 6. Configure PHP-FPM

Update `/etc/php/8.3/fpm/pool.d/www.conf`:

```ini
user = skillswap
group = skillswap

listen = /run/php/php8.3-fpm.sock

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 2
pm.max_spare_servers = 10

chdir = /var/www/skillswap
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

### 7. Configure MySQL

```sql
CREATE DATABASE skillswap;
CREATE USER 'skillswap_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON skillswap.* TO 'skillswap_user'@'localhost';
FLUSH PRIVILEGES;
```

Import schema and seeders:

```bash
mysql -u skillswap_user -p skillswap < db/schema.sql
mysql -u skillswap_user -p skillswap < db/seeders.sql
```

### 8. Set Environment Variables

Create `/var/www/skillswap/.env`:

```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=localhost
DB_USERNAME=skillswap_user
DB_PASSWORD=strong_password
DB_DATABASE=skillswap
JWT_SECRET=generate-random-string-here
PLATFORM_COMMISSION=0.10
```

### 9. Set File Permissions

```bash
sudo chown -R skillswap:skillswap /var/www/skillswap
sudo chmod -R 755 /var/www/skillswap
sudo chmod -R 775 /var/www/skillswap/logs
sudo chmod -R 775 /var/www/skillswap/var
```

### 10. Install SSL Certificate

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot certonly --standalone -d api.skillswap.com
sudo certbot renew --dry-run
```

### 11. Set Up Systemd Service (Optional)

Create `/etc/systemd/system/skillswap.service`:

```ini
[Unit]
Description=SkillSwap PHP Application
After=network.target

[Service]
Type=simple
User=skillswap
WorkingDirectory=/var/www/skillswap
ExecStart=/usr/bin/php -S 127.0.0.1:9000 public/index.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable skillswap
sudo systemctl start skillswap
```

### 12. Set Up Cron Jobs (If Needed)

```bash
sudo crontab -e -u skillswap
```

Example:

```
# Daily backup at 2 AM
0 2 * * * /usr/bin/mysqldump -u skillswap_user -ppassword skillswap > /var/backups/skillswap_$(date +\%Y\%m\%d).sql
```

---

## Database Backup & Restore

### Backup

```bash
mysqldump -u skillswap_user -p skillswap > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore

```bash
mysql -u skillswap_user -p skillswap < backup_20240101_120000.sql
```

---

## Monitoring & Logs

### View Nginx Logs

```bash
sudo tail -f /var/log/nginx/skillswap_error.log
sudo tail -f /var/log/nginx/skillswap_access.log
```

### View PHP-FPM Logs

```bash
sudo tail -f /var/log/php8.3-fpm.log
```

### View MySQL Logs

```bash
sudo tail -f /var/log/mysql/error.log
```

### Application Logs

```bash
tail -f /var/www/skillswap/logs/app.log
```

---

## Performance Optimization

### 1. Enable Query Caching

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
query_cache_size = 64M
query_cache_type = 1
```

### 2. Optimize PHP

Edit `/etc/php/8.3/fpm/php.ini`:

```ini
memory_limit = 256M
upload_max_filesize = 100M
max_execution_time = 60
opcache.enable = 1
opcache.memory_consumption = 128
```

### 3. Enable Redis Cache (Optional)

```bash
sudo apt install redis-server
sudo systemctl start redis-server
```

### 4. Set Up Load Balancing (Nginx)

Use multiple PHP-FPM workers and Nginx upstream:

```nginx
upstream skillswap_backend {
    server 127.0.0.1:9001;
    server 127.0.0.1:9002;
    server 127.0.0.1:9003;
}

server {
    # ...
    location ~ \.php$ {
        fastcgi_pass skillswap_backend;
        # ...
    }
}
```

---

## Security Checklist

- [ ] Change all default passwords
- [ ] Set `APP_DEBUG=false` in production
- [ ] Generate strong `JWT_SECRET`
- [ ] Enable HTTPS with valid SSL certificate
- [ ] Set proper file permissions (755 for dirs, 644 for files)
- [ ] Disable directory listing in Nginx
- [ ] Keep PHP and MySQL updated
- [ ] Set up firewall rules (UFW):

```bash
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

- [ ] Regular backups
- [ ] Monitor disk space
- [ ] Set up log rotation
- [ ] Enable PHP security extensions (Suhosin, ModSecurity)

---

## Troubleshooting

### 502 Bad Gateway

Check PHP-FPM socket:

```bash
sudo systemctl status php8.3-fpm
ls -la /run/php/php8.3-fpm.sock
```

### Database Connection Error

Check MySQL:

```bash
sudo systemctl status mysql
mysql -u skillswap_user -p -h localhost skillswap
```

### Permission Denied

```bash
sudo chown -R skillswap:skillswap /var/www/skillswap
sudo chmod -R 755 /var/www/skillswap
```

### High Memory Usage

Adjust pool size in PHP-FPM:

```ini
pm.max_children = 10
pm.start_servers = 3
```

---

## Update & Maintenance

### Update Application

```bash
cd /var/www/skillswap
sudo -u skillswap git pull origin main
sudo -u skillswap composer install --optimize-autoloader
```

### Update Database

```bash
# Always backup first
mysqldump -u skillswap_user -p skillswap > backup.sql

# Run migrations if any
cd /var/www/skillswap
php bin/migrate
```

---

## Support & Documentation

- API Documentation: [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- Postman Collection: [postman_collection.json](postman_collection.json)
- Issue Tracker: https://github.com/arcane/skillswap/issues

---

## License

This project is licensed under the MIT License - see LICENSE file for details.
