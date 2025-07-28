@echo off
echo ========================================
echo  CAT Hybrid System Launcher
echo ========================================
echo.

echo Current directory: %CD%
echo.

echo Step 1: Check required files...
if not exist "cat_api.py" (
    echo ✗ cat_api.py not found in current directory!
    echo Please run this script from the correct folder
    pause
    exit /b 1
)
if not exist "Parameter_Item_IST.csv" (
    echo ✗ Parameter_Item_IST.csv not found!
    pause
    exit /b 1
)
echo ✓ Required files found
echo.

echo Step 2: Starting Flask API (Port 5000)...
start "Flask API" cmd /k "cd /d %CD% && python cat_api.py"

echo Step 3: Waiting for Flask API to start...
timeout /t 5 /nobreak > nul

echo Step 4: Starting Laravel Server (Port 8000)...
if exist "cat_flask\artisan" (
    echo ✓ Laravel project found in cat_flask folder
    start "Laravel Server" cmd /k "cd /d %CD%\cat_flask && php artisan serve --port=8000"
) else (
    echo ✓ Using PHP built-in server
    start "Laravel Server" cmd /k "cd /d %CD% && php -S localhost:8000 -t public"
)

echo.
echo System is starting up...
echo.
echo URLs to access:
echo  - Flask API: http://127.0.0.1:5000/health
echo  - Laravel Web: http://localhost:8000
echo  - CAT Hybrid: http://localhost:8000/cat/hybrid
echo.
echo Opening browser in 3 seconds...
timeout /t 3 /nobreak > nul
start http://localhost:8000/cat/hybrid
echo.
echo Press any key to continue...
pause > nul

echo.
echo ✓ Both servers started successfully!
echo.
echo Press any key to stop all servers...
pause > nul

echo.
echo Stopping servers...
taskkill /f /im python.exe /fi "WINDOWTITLE eq Flask API*" > nul 2>&1
taskkill /f /im php.exe /fi "WINDOWTITLE eq Laravel Server*" > nul 2>&1
echo ✓ All servers stopped

echo.
echo ========================================
echo CAT Hybrid System shutdown complete
echo ========================================
pause
