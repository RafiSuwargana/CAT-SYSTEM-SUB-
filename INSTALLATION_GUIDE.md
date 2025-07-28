# 📦 Installation Guide - CAT System

## Prerequisites

### System Requirements
- **OS**: Windows 10/11, Linux, macOS
- **Python**: 3.8 atau lebih baru
- **PHP**: 8.1 atau lebih baru  
- **Composer**: Latest version
- **Git**: Untuk clone project (optional)

### Check Existing Installation
```cmd
# Check Python
python --version

# Check PHP
php -v

# Check Composer
composer --version
```

## Installation Steps

### 1. Download/Clone Project
```cmd
# Option A: Clone from Git
git clone https://github.com/RafiSuwargana/CAT_SYSTEM.git
cd CAT_SYSTEM

# Option B: Download ZIP dan extract
# Extract ke folder: c:\Users\user\Documents\cat_flask
```

### 2. Python Setup
```cmd
# Navigate to project folder
cd c:\Users\user\Documents\cat_flask

# Install Python dependencies
pip install -r requirements.txt

# Verify Flask API
python cat_api.py
# Should show: "Starting CAT Flask API Server v1.0.0"
# Press Ctrl+C to stop
```

### 3. Laravel Setup
```cmd
# Navigate to Laravel folder
cd cat_flask

# Install Composer dependencies
composer install

# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database (if not exists)
php artisan migrate

# Test Laravel server
php artisan serve --port=8000
# Should show: "Laravel development server started"
# Press Ctrl+C to stop
```

### 4. Verify Installation
```cmd
# Go back to root project folder
cd ..

# Run the launcher
start_hybrid_system.bat
```

## Troubleshooting Installation

### Python Issues

#### Error: "python is not recognized"
```cmd
# Download Python from: https://python.org
# During installation, check "Add Python to PATH"
```

#### Error: "pip install failed"
```cmd
# Update pip
python -m pip install --upgrade pip

# Install dependencies one by one
pip install flask
pip install flask-cors
pip install numpy
pip install pandas
pip install scipy
```

### PHP Issues

#### Error: "php is not recognized"
```cmd
# Download PHP from: https://windows.php.net/download/
# Add PHP folder to system PATH
```

#### Error: "composer not found"
```cmd
# Download Composer from: https://getcomposer.org/download/
# Follow installation wizard
```

### Laravel Issues

#### Error: "vendor folder not found"
```cmd
cd cat_flask
composer install
```

#### Error: "No application encryption key"
```cmd
cd cat_flask
php artisan key:generate
```

#### Error: "SQLite database not found"
```cmd
cd cat_flask
# Create empty database file
type nul > database\database.sqlite
php artisan migrate
```

### File Permission Issues (Linux/macOS)

```bash
# Make sure files are executable
chmod +x start_hybrid_system.bat
chmod 755 storage/ -R
chmod 755 bootstrap/cache/ -R
```

## Directory Structure After Installation

```
cat_flask/
├── cat_api.py                     ✅ Python API server
├── Parameter_Item_IST.csv         ✅ Item bank data
├── requirements.txt               ✅ Python dependencies
├── start_hybrid_system.bat        ✅ Launcher script
├── cat_api.log                    📝 Generated after first run
└── cat_flask/                     ✅ Laravel application
    ├── .env                       ✅ Environment config
    ├── vendor/                    ✅ Composer dependencies
    ├── database/
    │   └── database.sqlite        ✅ SQLite database
    └── storage/
        └── logs/                  📝 Laravel logs
```

## Environment Configuration

### Python API (.env or config in cat_api.py)
```python
API_VERSION = "1.0.0"
PORT = 5000
HOST = "127.0.0.1"
```

### Laravel (.env)
```env
APP_NAME=CAT_Laravel
APP_ENV=local
APP_KEY=base64:xxxxx
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

FLASK_API_URL=http://127.0.0.1:5000
```

## Development Dependencies (Optional)

For development/debugging:
```cmd
# Python development tools
pip install pytest
pip install black
pip install flake8

# Laravel development tools
cd cat_flask
composer require --dev phpunit/phpunit
composer require --dev laravel/telescope
```

## Performance Optimization

### Python API Optimization
```python
# In cat_api.py, change grid points for 5x speed boost:
# From: theta_range = np.linspace(-6, 6, 1001)
# To:   theta_range = np.linspace(-6, 6, 201)
```

### Laravel Optimization
```cmd
cd cat_flask
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Verification Checklist

After installation, verify:

- [ ] `python cat_api.py` runs without errors
- [ ] `cd cat_flask && php artisan serve` runs without errors  
- [ ] `start_hybrid_system.bat` launches both servers
- [ ] http://127.0.0.1:5000/health returns `{"status": "healthy"}`
- [ ] http://localhost:8000 shows Laravel welcome page
- [ ] http://localhost:8000/cat/hybrid shows CAT system interface

## Next Steps

1. 📖 Read the main [README.md](README.md) for detailed usage
2. 🚀 Follow [QUICK_START.md](QUICK_START.md) for immediate testing
3. 🔧 Customize configuration in `cat_flask/config/cat.php`
4. 📊 Monitor logs in `cat_api.log` and `cat_flask/storage/logs/`

## Support

If you encounter issues:
1. Check the [Troubleshooting section](#troubleshooting-installation)
2. Verify all prerequisites are installed
3. Ensure ports 5000 and 8000 are available
4. Check file permissions and paths

**Installation complete! Ready to run CAT System! 🎉**
