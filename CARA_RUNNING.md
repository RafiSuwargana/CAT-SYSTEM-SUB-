# 🚀 PANDUAN MENJALANKAN CAT SYSTEM

## 📋 Prasyarat
- Python 3.8+ (untuk Flask API)
- PHP 8.1+ (untuk Laravel)
- MySQL/MariaDB
- Composer
- Git

## 🎯 Cara Menjalankan Sistem (RECOMMENDED)

### **Option 1: Menjalankan Hybrid System (Otomatis)**
```bash
# Dari folder cat_flask
./start_hybrid_system.bat
```

Script ini akan:
1. ✅ Mengecek file yang diperlukan
2. 🐍 Menjalankan Flask API di port 5000
3. 🐘 Menjalankan Laravel server di port 8000
4. 🌐 Membuka browser secara otomatis

### **Option 2: Menjalankan Manual (Step by Step)**

#### **Step 1: Setup Database**
```sql
CREATE DATABASE cat_laravel;
```

#### **Step 2: Setup Laravel**
```bash
# Masuk ke folder Laravel
cd cat_flask

# Install dependencies
composer install

# Copy environment file
copy .env.example .env

# Update .env dengan database credentials
DB_DATABASE=cat_laravel
DB_USERNAME=root
DB_PASSWORD=your_password

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

#### **Step 3: Jalankan Flask API**
```bash
# Dari folder root (cat_flask)
python cat_api.py
```
Flask API akan running di: http://127.0.0.1:5000

#### **Step 4: Jalankan Laravel Server**
```bash
# Dari folder cat_flask/cat_flask
php artisan serve --port=8000
```
Laravel akan running di: http://localhost:8000

## 🌐 URL Akses

| Service | URL | Deskripsi |
|---------|-----|-----------|
| **Flask API** | http://127.0.0.1:5000/health | Health check Flask API |
| **Laravel Web** | http://localhost:8000 | Homepage Laravel |
| **CAT System** | http://localhost:8000/cat/hybrid | **Main CAT Application** |

## 🔧 Troubleshooting

### **Error: Port sudah digunakan**
```bash
# Cek port yang digunakan
netstat -aon | findstr :5000
netstat -aon | findstr :8000

# Kill process jika perlu
taskkill /f /pid [PID_NUMBER]
```

### **Error: Flask API tidak bisa diakses**
```bash
# Cek Python dan dependencies
python --version
pip install flask flask-cors pandas numpy scipy

# Jalankan Flask dengan debug
python cat_api.py
```

### **Error: Laravel tidak bisa start**
```bash
# Cek PHP version
php --version

# Install composer dependencies
composer install

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## 📂 Struktur Project

```
cat_flask/
├── cat_api.py              # Flask API server
├── Parameter_Item_IST.csv  # Item parameters
├── start_hybrid_system.bat # Script auto-start
├── requirements.txt        # Python dependencies
└── cat_flask/             # Laravel project
    ├── app/
    │   ├── Http/Controllers/
    │   │   └── HybridCATController.php
    │   └── Services/
    │       ├── HybridCATService.php
    │       └── FlaskApiService.php
    ├── routes/web.php
    └── resources/views/cat/hybrid.blade.php
```

## 🧪 Testing

### **Test Flask API**
```bash
# Health check
curl http://127.0.0.1:5000/health

# Test estimate theta
curl -X POST http://127.0.0.1:5000/estimate-theta \
  -H "Content-Type: application/json" \
  -d '{"responses": [{"a": 1.2, "b": 0.5, "g": 0.25, "answer": 1}]}'
```

### **Test Laravel API**
```bash
# Test API
curl http://localhost:8000/api/test

# Test database
curl http://localhost:8000/api/test-db
```

## 🚀 Quick Start (Dalam 30 detik)

1. **Buka Command Prompt**
2. **Navigate ke folder project**
   ```bash
   cd c:\Users\user\Documents\cat_flask
   ```
3. **Run script otomatis**
   ```bash
   start_hybrid_system.bat
   ```
4. **Tunggu sampai browser terbuka otomatis**
5. **Akses: http://localhost:8000/cat/hybrid**

## 🔍 Monitoring

- **Flask API Logs**: Lihat terminal Flask API
- **Laravel Logs**: `cat_flask/storage/logs/laravel.log`
- **Browser Console**: F12 untuk debug JavaScript

## 📱 Fitur Sistem

- ✅ **Hybrid Architecture**: Flask API + Laravel Web
- ✅ **Real-time CAT**: Adaptive testing dengan EAP estimation
- ✅ **3PL IRT Model**: Item Response Theory 3 Parameter Logistic
- ✅ **EFI Item Selection**: Expected Fisher Information
- ✅ **Session Management**: Tracking lengkap test sessions
- ✅ **Responsive UI**: Bootstrap 5 interface

---

## 💡 Tips

1. **Gunakan start_hybrid_system.bat** untuk kemudahan
2. **Pastikan port 5000 dan 8000 kosong** sebelum menjalankan
3. **Cek log files** jika ada error
4. **Test Flask API dulu** sebelum Laravel
5. **Gunakan Chrome/Firefox** untuk best experience
