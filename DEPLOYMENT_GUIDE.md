# 🚀 Deployment Guide - CAT System

## Deployment Options

### 1. Development Deployment (Local)
Untuk testing dan development di local machine.

### 2. Production Deployment (Server)
Untuk production environment dengan proper web server.

### 3. Docker Deployment (Containerized)
Untuk deployment yang portable dan scalable.

---

## 1. Development Deployment

### Requirements
- Python 3.8+
- PHP 8.1+
- Composer

### Quick Deploy
```cmd
# Clone/download project
git clone https://github.com/RafiSuwargana/CAT_SYSTEM.git
cd CAT_SYSTEM

# Install dependencies
pip install -r requirements.txt
cd cat_flask && composer install

# Run with launcher
start_hybrid_system.bat
```

**Access:**
- CAT System: http://localhost:8000/cat/hybrid
- API: http://127.0.0.1:5000

---

## 2. Production Deployment

### Server Requirements
- **OS**: Ubuntu 20.04+ / CentOS 8+ / Windows Server
- **Python**: 3.8+
- **PHP**: 8.1+ with extensions (mbstring, xml, json, sqlite3)
- **Web Server**: Nginx or Apache
- **Process Manager**: PM2 or Supervisor
- **Domain**: Optional (dapat menggunakan IP)

### Step 1: Server Setup (Ubuntu)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Python
sudo apt install python3 python3-pip -y

# Install PHP
sudo apt install php8.1 php8.1-fpm php8.1-mbstring php8.1-xml php8.1-sqlite3 -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Nginx
sudo apt install nginx -y

# Install PM2 for process management
sudo npm install -g pm2
```

### Step 2: Deploy Application

```bash
# Clone project
cd /var/www
sudo git clone https://github.com/RafiSuwargana/CAT_SYSTEM.git cat-system
sudo chown -R www-data:www-data cat-system
cd cat-system

# Install Python dependencies
pip3 install -r requirements.txt

# Install Laravel dependencies
cd cat_flask
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Step 3: Configure Services

#### Flask API Service (PM2)
```bash
# Create PM2 config
cat > ecosystem.config.js << 'EOF'
module.exports = {
  apps: [{
    name: 'cat-api',
    script: 'python3',
    args: 'cat_api.py',
    cwd: '/var/www/cat-system',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 5000
    }
  }]
}
EOF

# Start Flask API
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

#### Nginx Configuration
```bash
# Create Nginx config
sudo tee /etc/nginx/sites-available/cat-system << 'EOF'
server {
    listen 80;
    server_name your-domain.com;  # Ganti dengan domain Anda
    root /var/www/cat-system/cat_flask/public;
    
    index index.php index.html;
    
    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Flask API proxy
    location /api/ {
        proxy_pass http://127.0.0.1:5000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
}
EOF

# Enable site
sudo ln -s /etc/nginx/sites-available/cat-system /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 4: Environment Configuration

#### Laravel (.env)
```bash
cd /var/www/cat-system/cat_flask
cp .env.example .env

# Edit .env
sudo tee .env << 'EOF'
APP_NAME=CAT_System
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=http://your-domain.com

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/cat-system/cat_flask/database/database.sqlite

FLASK_API_URL=http://127.0.0.1:5000

LOG_CHANNEL=daily
LOG_LEVEL=info
EOF

php artisan key:generate
```

### Step 5: SSL Certificate (Optional)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Get SSL certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 3. Docker Deployment

### Dockerfile for Flask API
```dockerfile
# Dockerfile.flask
FROM python:3.9-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY cat_api.py Parameter_Item_IST.csv ./

EXPOSE 5000

CMD ["python", "cat_api.py"]
```

### Dockerfile for Laravel
```dockerfile
# Dockerfile.laravel  
FROM php:8.1-fpm

WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application
COPY cat_flask/ .

# Install Laravel dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

### Docker Compose
```yaml
# docker-compose.yml
version: '3.8'

services:
  flask-api:
    build:
      context: .
      dockerfile: Dockerfile.flask
    ports:
      - "5000:5000"
    volumes:
      - ./cat_api.log:/app/cat_api.log
    restart: unless-stopped

  laravel-app:
    build:
      context: .
      dockerfile: Dockerfile.laravel
    volumes:
      - ./cat_flask:/var/www
    restart: unless-stopped
    depends_on:
      - flask-api

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./cat_flask/public:/var/www/public
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - laravel-app
    restart: unless-stopped
```

### Deploy with Docker
```bash
# Build and run
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f
```

---

## Monitoring & Maintenance

### Log Monitoring
```bash
# Flask API logs
tail -f /var/www/cat-system/cat_api.log

# Laravel logs
tail -f /var/www/cat-system/cat_flask/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# PM2 logs
pm2 logs cat-api
```

### Health Checks
```bash
# API health
curl http://localhost:5000/health

# Laravel health
curl http://localhost/

# Full system test
curl http://localhost/cat/hybrid
```

### Backup Strategy
```bash
# Database backup
cp /var/www/cat-system/cat_flask/database/database.sqlite /backup/

# Application backup
tar -czf /backup/cat-system-$(date +%Y%m%d).tar.gz /var/www/cat-system/

# Automated backup script
cat > /usr/local/bin/backup-cat.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/cat-system"
mkdir -p $BACKUP_DIR

# Database backup
cp /var/www/cat-system/cat_flask/database/database.sqlite $BACKUP_DIR/database_$DATE.sqlite

# Log backup
cp /var/www/cat-system/cat_api.log $BACKUP_DIR/api_log_$DATE.log

# Keep only last 7 days
find $BACKUP_DIR -name "*.sqlite" -mtime +7 -delete
find $BACKUP_DIR -name "*.log" -mtime +7 -delete

echo "Backup completed: $DATE"
EOF

chmod +x /usr/local/bin/backup-cat.sh

# Add to crontab
echo "0 2 * * * /usr/local/bin/backup-cat.sh" | crontab -
```

### Performance Optimization

#### Flask API Optimization
```python
# In cat_api.py - optimize grid points
theta_range = np.linspace(-6, 6, 201)  # Reduced from 1001
```

#### Laravel Optimization
```bash
# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Enable OPcache
# Add to php.ini:
# opcache.enable=1
# opcache.memory_consumption=128
# opcache.max_accelerated_files=4000
```

#### Nginx Optimization
```nginx
# Add to nginx.conf
gzip on;
gzip_vary on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

# Cache static files
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 1M;
    add_header Cache-Control "public, immutable";
}
```

---

## Security Considerations

### Production Security
```bash
# Laravel security
php artisan config:cache  # Hide .env
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache

# Remove development files
rm -f cat_flask/.env.example
rm -rf cat_flask/tests
```

### Firewall Configuration
```bash
# UFW (Ubuntu)
sudo ufw allow 22    # SSH
sudo ufw allow 80    # HTTP
sudo ufw allow 443   # HTTPS
sudo ufw enable

# Block direct access to Flask API
sudo ufw deny 5000
```

### SSL Configuration
```nginx
# Force HTTPS redirect
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    
    # ... rest of config
}
```

---

## Troubleshooting Production Issues

### Common Issues

#### Flask API not starting
```bash
# Check Python dependencies
pip3 list | grep -E "(flask|numpy|pandas|scipy)"

# Check port availability
sudo netstat -tlnp | grep :5000

# Check PM2 status
pm2 status
pm2 logs cat-api
```

#### Laravel not working
```bash
# Check PHP-FPM
sudo systemctl status php8.1-fpm

# Check permissions
ls -la /var/www/cat-system/cat_flask/storage/

# Check Laravel logs
tail -f /var/www/cat-system/cat_flask/storage/logs/laravel.log
```

#### Database issues
```bash
# Check SQLite file
ls -la /var/www/cat-system/cat_flask/database/database.sqlite

# Test database connection
cd /var/www/cat-system/cat_flask
php artisan tinker
# In tinker: DB::connection()->getPdo();
```

### Performance Issues
```bash
# Monitor system resources
htop
iotop
df -h

# Check Flask API performance
curl -w "%{time_total}\n" http://localhost:5000/health

# Check Laravel performance
curl -w "%{time_total}\n" http://localhost/
```

## Deployment Checklist

### Pre-Deployment
- [ ] Server requirements met
- [ ] Domain/DNS configured
- [ ] SSL certificate ready (if needed)
- [ ] Backup strategy planned

### Deployment
- [ ] Application files uploaded
- [ ] Dependencies installed
- [ ] Environment configured
- [ ] Database setup
- [ ] Web server configured
- [ ] Process manager setup

### Post-Deployment  
- [ ] Health checks passing
- [ ] Logs monitoring setup
- [ ] Backup automated
- [ ] Performance optimized
- [ ] Security hardened

**Deployment complete! Your CAT System is ready for production! 🚀**
