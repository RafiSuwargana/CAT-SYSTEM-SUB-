# 🚀 Quick Start Guide - CAT System

## Langkah 1: Persiapan

Pastikan yang sudah terinstall:
- ✅ Python 3.8+ (dengan pip)
- ✅ PHP 8.1+ 
- ✅ Composer

## Langkah 2: One-Click Start

```cmd
# Buka folder project
cd c:\Users\user\Documents\cat_flask

# Jalankan launcher
start_hybrid_system.bat
```

**SELESAI!** 🎉 System akan otomatis:
1. Check file yang diperlukan
2. Start Flask API Server (Port 5000)
3. Start Laravel Server (Port 8000) 
4. Buka browser ke CAT System

## Langkah 3: Testing

Setelah launcher jalan, akses:
- **CAT System**: http://localhost:8000/cat/hybrid
- **API Health**: http://127.0.0.1:5000/health

## Jika Ada Masalah

### Python Dependencies Error
```cmd
pip install -r requirements.txt
```

### Laravel Error
```cmd
cd cat_flask
composer install
php artisan key:generate
```

### Port Conflict
- Flask API: Ganti PORT di `cat_api.py`
- Laravel: Ganti port dengan `php artisan serve --port=8001`

## Development Mode

### Manual Start Flask API
```cmd
python cat_api.py
```

### Manual Start Laravel
```cmd
cd cat_flask
php artisan serve --port=8000
```

## File Structure Check

Pastikan ada file ini:
- ✅ `cat_api.py` - Python API server
- ✅ `Parameter_Item_IST.csv` - Item bank data
- ✅ `requirements.txt` - Python dependencies
- ✅ `start_hybrid_system.bat` - Launcher script
- ✅ `cat_flask/` - Laravel application folder

## System Architecture

```
Browser ←→ Laravel (Port 8000) ←→ Flask API (Port 5000) ←→ Item Bank CSV
```

## Ready to Go? 

Execute: **`start_hybrid_system.bat`** 🚀

The launcher handles everything automatically!
