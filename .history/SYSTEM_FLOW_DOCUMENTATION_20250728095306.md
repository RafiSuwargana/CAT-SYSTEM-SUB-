# 📋 Dokumentasi Alur Sistem CAT (Computer Adaptive Testing)

## 🏗️ Arsitektur Sistem

Sistem CAT ini menggunakan **arsitektur hybrid** yang menggabungkan Laravel (PHP) dan Flask (Python):

- **Laravel**: Frontend, database management, session handling, UI
- **Flask API**: IRT calculations, theta estimation, item selection
- **SQLite**: Database untuk menyimpan sessions, responses, dan item parameters

## 📁 Struktur File dan Alur Penggunaan

### 1. 🐍 **Flask API Layer** (Python)

#### **File Utama:**
- **`cat_api.py`** - Server Flask API utama

**Fungsi:**
- IRT 3PL calculations (probability, information, likelihood)
- Theta estimation menggunakan MAP (real-time) dan EAP (final scoring)
- Item selection menggunakan Maximum Fisher Information (MI)
- Stopping criteria evaluation
- Performance monitoring dan logging

**Endpoints:**
```
GET  /health                    - Health check
POST /api/estimate-theta        - Estimasi theta MAP (real-time)
POST /api/select-item          - Pemilihan item berikutnya (MI)
POST /api/final-score          - Skor final dengan EAP
POST /api/stopping-criteria    - Cek kriteria berhenti
POST /api/calculate-score      - Konversi theta ke skor IQ
GET  /api/item-bank           - Info item bank
POST /api/test-calculation     - Endpoint testing
```

#### **Dependencies:**
- **`requirements.txt`** - Python package dependencies
- **`Parameter_Item_IST.csv`** - Item bank parameters (a, b, g, u)

---

### 2. 🎯 **Laravel Application Layer** (PHP)

#### **A. Routes & Entry Points**

**`cat_flask/routes/web.php`**
```
/ → HomeController@index
/cat/hybrid → View cat.hybrid (Main UI)
/api/* → HybridCATController (API endpoints)
```

#### **B. Controllers**

**`cat_flask/app/Http/Controllers/HybridCATController.php`**
- **Fungsi:** Main controller untuk CAT system
- **Dependencies:** HybridCATService, FlaskApiService, PerformanceMonitorService
- **Methods:**
  - `startTest()` - Mulai sesi baru
  - `submitResponse()` - Submit jawaban dan dapatkan item berikutnya
  - `getSessionHistory()` - Riwayat sesi
  - `getFlaskApiHealth()` - Status Flask API
  - `switchApiSource()` - Toggle Flask/Laravel calculation

**`cat_flask/app/Http/Controllers/HomeController.php`**
- **Fungsi:** Homepage controller
- **Methods:** `index()` - Tampilan utama

**`cat_flask/app/Http/Controllers/TestController.php`**
- **Fungsi:** Testing endpoints
- **Methods:** `simpleTest()`, `testDatabase()`

#### **C. Services (Business Logic)**

**`cat_flask/app/Services/HybridCATService.php`**
- **Fungsi:** Core service - orchestrates CAT process
- **Dependencies:** FlaskApiService, CATService (fallback), PerformanceMonitorService
- **Flow:**
  1. Check Flask API availability
  2. Use Flask for calculations (preferred) or fallback to Laravel
  3. Manage database operations
  4. Handle session lifecycle

**`cat_flask/app/Services/FlaskApiService.php`**
- **Fungsi:** HTTP client untuk komunikasi dengan Flask API
- **Methods:**
  - `estimateTheta()` - Request theta estimation
  - `selectNextItem()` - Request item selection
  - `calculateScore()` - Request score calculation
  - `checkStoppingCriteria()` - Request stopping check
  - `testConnection()` - Health check

**`cat_flask/app/Services/PerformanceMonitorService.php`**
- **Fungsi:** Performance monitoring dan logging
- **Features:** Memory usage, CPU load, process timing

**`cat_flask/app/Services/CATService.php`**
- **Fungsi:** Fallback service jika Flask API tidak tersedia
- **Features:** Native PHP implementation IRT calculations

#### **D. Models (Database)**

**`cat_flask/app/Models/TestSession.php`**
- **Fungsi:** Model untuk session management
- **Fields:** session_id, theta, standard_error, test_completed, stop_reason, final_score
- **Relations:** hasMany(TestResponse, UsedItem)

**`cat_flask/app/Models/TestResponse.php`**
- **Fungsi:** Model untuk menyimpan responses peserta
- **Fields:** session_id, item_id, item_order, answer, response_time, theta_after_response
- **Relations:** belongsTo(TestSession, ItemParameter)

**`cat_flask/app/Models/ItemParameter.php`**
- **Fungsi:** Model untuk item bank
- **Fields:** id, a_parameter, b_parameter, g_parameter, u_parameter
- **Relations:** hasMany(TestResponse, UsedItem)

**`cat_flask/app/Models/UsedItem.php`**
- **Fungsi:** Model untuk tracking item yang sudah digunakan
- **Fields:** session_id, item_id, used_at
- **Relations:** belongsTo(TestSession, ItemParameter)

---

### 3. 🔧 **Configuration & Bootstrap**

**`cat_flask/bootstrap/app.php`**
- **Fungsi:** Laravel application bootstrap
- **Configure:** Routing, middleware, exceptions

**`cat_flask/config/cat.php`**
- **Fungsi:** CAT system configuration
- **Settings:** Flask API URL, timeouts, test parameters

**`cat_flask/app/Providers/AppServiceProvider.php`**
- **Fungsi:** Service container registration
- **Registers:** FlaskApiService, HybridCATService, PerformanceMonitorService

---

### 4. 🏃‍♂️ **Console Commands** (Development Tools)

**`cat_flask/app/Console/Commands/StartFlaskApiCommand.php`**
- **Fungsi:** Start Flask API dari Laravel command
- **Usage:** `php artisan cat:start-flask-api`

**`cat_flask/app/Console/Commands/TestFlaskApiCommand.php`**
- **Fungsi:** Test Flask API endpoints
- **Usage:** `php artisan cat:test-flask-api`

**`cat_flask/app/Console/Commands/TestPerformanceMonitor.php`**
- **Fungsi:** Test performance monitoring
- **Usage:** `php artisan cat:test-performance-monitor`

---

### 5. 🗃️ **Database**

**`cat_flask/database/database.sqlite`**
- **Fungsi:** SQLite database file
- **Tables:** test_sessions, test_responses, item_parameters, used_items

**`cat_flask/database/migrations/`**
- **Fungsi:** Database schema definitions
- **Files:** Create tables untuk semua models

---

### 6. 🎨 **Frontend Layer (User Interface)**

#### **A. Views & Templates (Blade PHP)**

**🔹 `cat_flask/resources/views/cat/hybrid.blade.php`**
- **Fungsi:** Main CAT test interface (Single Page Application)
- **Komponen UI:**
  - Header dengan branding dan API status
  - Control panel (Start Test, Submit Response buttons)
  - Item display area (menampilkan soal)
  - Real-time statistics (theta, SE, probability, information)
  - Response history dan progress tracking
  - Chart visualization (theta progression)
  - Final results display
- **Dependencies:** Bootstrap 5, Font Awesome, Chart.js
- **Features:**
  - Responsive design
  - Real-time updates
  - API health monitoring
  - Performance metrics display

#### **B. JavaScript Client (Frontend Logic)**

**🔹 `cat_flask/public/js/cat-hybrid.js`**
- **Fungsi:** Frontend application logic dan API communication
- **Key Functions:**
  ```javascript
  startTest()           - Initialize new test session
  submitResponse(answer) - Submit user answer
  checkFlaskApiHealth() - Monitor Flask API status
  updateChart()         - Update theta progression chart
  displayItem()         - Show current question
  handleTestCompletion() - Process final results
  ```
- **API Integration:**
  - CSRF token handling untuk security
  - Error handling dan fallback
  - Real-time status updates
  - Performance monitoring
- **Data Management:**
  ```javascript
  testData = {
    responses: [],        // User responses history
    thetaHistory: [],     // Theta progression
    seHistory: [],        // SE progression
    itemCount: 0,         // Current item number
    apiSource: 'flask'    // Current API source
  }
  ```

#### **C. Static Assets**

**`cat_flask/public/css/`**
- **Fungsi:** Custom CSS styles
- **Features:** Theme, animations, responsive design

**`cat_flask/public/js/`**
- **Fungsi:** JavaScript files
- **Files:** cat-hybrid.js (main frontend logic)

**`cat_flask/public/favicon.ico`**
- **Fungsi:** Website icon

---

### 7. 🌐 **Frontend-to-Backend Communication Flow**

#### **A. AJAX Request Structure**
```javascript
// Example: Starting test
fetch('/api/start-test', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
})
.then(response => response.json())
.then(data => {
    // Handle response
    displayItem(data.item);
    updateTheta(data.theta);
})
.catch(error => {
    // Error handling
    updateStatus('Error: ' + error.message, 'danger');
});
```

#### **B. Real-time UI Updates**
- **Theta Display:** Updated setelah setiap response
- **Progress Bar:** Shows item progression (1/30, 2/30, etc.)
- **API Status:** Live monitoring Flask API health
- **Chart Updates:** Real-time theta dan SE visualization
- **Response History:** Scrollable list of all responses

#### **C. State Management**
```javascript
// Global state tracking
currentSession = {
    sessionId: 'CAT_1234567890_1234',
    currentItem: {...},
    theta: 0.0,
    se: 1.0,
    itemNumber: 1,
    apiSource: 'flask'
}
```

---

## 🔄 Alur Proses CAT System (Frontend ke Backend)

### **1. System Startup & Initial Load**
```
🌐 User Browser
├── GET / → routes/web.php → HomeController@index
├── GET /cat/hybrid → View: cat/hybrid.blade.php
├── Load: cat-hybrid.js, Bootstrap, Chart.js
├── Execute: initializeApp()
├── GET /api/flask-health → HybridCATController@getFlaskApiHealth
└── Display: Ready state dengan API status
```

### **2. User Interaction: Start Test**
```
🖱️ User clicks "Mulai Tes"
├── Frontend: startTest() function
├── AJAX POST /api/start-test
│   ├── Headers: CSRF token, Content-Type
│   └── Body: {} (empty)
├── Laravel: routes/web.php → HybridCATController@startTest
├── Service: HybridCATService@startSession
│   ├── Generate session_id: 'CAT_timestamp_random'
│   ├── Create TestSession record in database
│   ├── Check Flask API health
│   └── Select first item (via Flask or fallback)
├── Flask API: POST localhost:5000/api/select-item
│   ├── Execute: select_next_item_mi()
│   ├── Calculate: Fisher Information
│   └── Return: Best item for θ=0
├── Database: Save session to test_sessions table
├── Response: JSON with session_id, item, theta, se
└── Frontend: 
    ├── Store currentSession object
    ├── Display item in UI
    ├── Enable response buttons
    ├── Show progress bar (1/30)
    └── Update API source indicator
```

### **3. User Interaction: Submit Answer**
```
🖱️ User clicks "Benar" or "Salah" 
├── Frontend: submitResponse(1 or 0)
├── AJAX POST /api/submit-response
│   ├── Body: {session_id, item_id, answer}
│   └── Headers: CSRF token
├── Laravel: HybridCATController@submitResponse
├── Service: HybridCATService@submitResponse
│   ├── Validate session exists
│   ├── Save response to database
│   │   ├── Table: test_responses
│   │   └── Fields: session_id, item_id, answer, theta_after
│   ├── Mark item as used
│   │   ├── Table: used_items  
│   │   └── Fields: session_id, item_id, used_at
│   ├── Request theta estimation from Flask
│   │   ├── POST localhost:5000/api/estimate-theta
│   │   ├── Body: {responses[], theta_old}
│   │   ├── Execute: estimate_theta_map()
│   │   └── Return: new theta, SE
│   ├── Check stopping criteria
│   │   ├── POST localhost:5000/api/stopping-criteria
│   │   ├── Execute: check_stopping_criteria()
│   │   └── Return: should_stop, reason
│   └── If continue: Select next item
│       ├── POST localhost:5000/api/select-item
│       ├── Execute: select_next_item_mi()
│       └── Return: next item
├── Database Updates:
│   ├── test_responses: New response record
│   ├── used_items: Mark item as used
│   └── test_sessions: Update theta, SE
├── Response: JSON with new item or completion
└── Frontend:
    ├── Add response to history display
    ├── Update theta progression chart
    ├── Display new item OR final results
    ├── Update progress bar
    └── Show real-time statistics
```

### **4. Flask API Calculation Flow (Detail)**
```
🔄 Laravel Request → Flask API
├── HTTP POST localhost:5000/api/estimate-theta
├── cat_api.py receives request
├── Validate request data format
├── Execute calculation:
│   ├── log_estimate_theta_map() - Performance logging
│   ├── estimate_theta_map(responses, theta_old)
│   │   ├── Bayesian inference dengan MAP
│   │   ├── Quadrature integration (-6 to +6)
│   │   ├── Apply change constraints
│   │   └── Calculate SE from Fisher Information
│   ├── log_process_performance() - Memory & CPU
│   └── Return theta, SE, method='MAP'
├── Performance log to cat_api.log
└── JSON response to Laravel
```

### **5. Frontend Real-time Updates**
```
📊 JavaScript Updates (setiap response)
├── updateChart() - Add point to theta progression
├── displayResponseResult() - Show response history
│   ├── Item number & parameters
│   ├── User answer (✓ or ✗)
│   ├── Theta before → after
│   └── SE before → after
├── updateProgress() - Progress bar (X/30)
├── updateStatus() - Status message & API source
└── API Health Check (every 30 seconds)
    ├── GET /api/flask-health
    ├── Update status indicator
    └── Show Flask online/offline/fallback
```

### **6. Test Completion Flow**
```
🏁 Test Ends (any stopping criteria met)
├── Flask API: check_stopping_criteria() returns true
├── HybridCATService: test_completed = true
├── Final Score Calculation:
│   ├── POST localhost:5000/api/final-score
│   ├── Execute: estimate_theta_eap() - Final theta
│   ├── Execute: calculate_score() - IQ score
│   └── Return: final_theta, SE_EAP, final_score
├── Database: Update test_sessions
│   ├── test_completed = true
│   ├── stop_reason = "reason"
│   └── final_score = calculated_score
├── Frontend: handleTestCompletion()
│   ├── Display final results card
│   ├── Show complete session history  
│   ├── Display final chart
│   ├── Disable all buttons
│   └── Enable "Start New Test"
└── Performance: log_final_scoring()
```

### **7. Database Operations Flow**
```
🗄️ Database Layer (SQLite)
├── test_sessions table
│   ├── Primary Key: session_id
│   ├── Fields: theta, standard_error, test_completed
│   └── Relations: hasMany(responses, usedItems)
├── test_responses table  
│   ├── Fields: session_id, item_id, answer, theta_after
│   └── Relations: belongsTo(session, itemParameter)
├── item_parameters table
│   ├── Fields: id, a_parameter, b_parameter, g_parameter
│   └── Relations: hasMany(responses, usedItems)
└── used_items table
    ├── Fields: session_id, item_id, used_at
    └── Relations: belongsTo(session, itemParameter)
```

### **8. Fallback Mechanism Flow**
```
⚠️ Flask API Unavailable
├── HybridCATService detects Flask API down
├── Log warning: "Flask API tidak tersedia"
├── Switch to: CATService (PHP implementation)
├── Continue test dengan Laravel calculations
├── Frontend shows: "API: LARAVEL (Fallback)"
└── All functionality tetap berjalan normal
```

---

## 🎯 **File Hierarchy dengan Highlight Penggunaan**

### **📱 Frontend Files (User Interface)**
```
🔹 UTAMA - cat_flask/resources/views/cat/hybrid.blade.php
   ├── Main UI template (HTML + Blade PHP)
   ├── Integrate: Bootstrap 5, Font Awesome, Chart.js
   ├── Contains: Test interface, buttons, charts, status displays
   └── Calls: cat-hybrid.js functions

🔹 UTAMA - cat_flask/public/js/cat-hybrid.js  
   ├── Frontend application logic (713 lines)
   ├── Functions: startTest(), submitResponse(), updateChart()
   ├── API Communication: AJAX calls to Laravel endpoints
   ├── State Management: currentSession, testData objects
   └── Real-time: Health monitoring, progress updates

🔹 SUPPORTING - cat_flask/public/css/
   └── Custom styling and theme files

🔹 SUPPORTING - cat_flask/public/favicon.ico
   └── Website icon
```

### **🌐 Laravel Backend Files (PHP Layer)**
```
🔹 ROUTING - cat_flask/routes/web.php
   ├── URL mapping to controllers
   ├── Routes: /, /cat/hybrid, /api/*
   └── CSRF protection setup

🔹 MAIN CONTROLLER - cat_flask/app/Http/Controllers/HybridCATController.php
   ├── Primary CAT controller (handles all test operations)
   ├── Methods: startTest(), submitResponse(), getSessionHistory()
   ├── Dependencies: HybridCATService, FlaskApiService
   └── JSON API responses to frontend

🔹 CORE SERVICE - cat_flask/app/Services/HybridCATService.php
   ├── Business logic orchestration
   ├── Database operations coordination
   ├── Flask API communication management
   └── Fallback mechanism handling

🔹 API CLIENT - cat_flask/app/Services/FlaskApiService.php
   ├── HTTP client for Flask API communication
   ├── Methods: estimateTheta(), selectNextItem(), calculateScore()
   ├── Error handling and validation
   └── Request/response formatting

🔹 FALLBACK SERVICE - cat_flask/app/Services/CATService.php
   ├── PHP implementation of IRT calculations
   ├── Used when Flask API unavailable
   └── Backup calculation engine

🔹 MONITORING - cat_flask/app/Services/PerformanceMonitorService.php
   ├── Performance tracking and logging
   ├── Memory and CPU monitoring
   └── Process timing measurement
```

### **🗄️ Database Files (Data Layer)**
```
🔹 DATABASE - cat_flask/database/database.sqlite
   ├── Main database file
   └── Tables: test_sessions, test_responses, item_parameters, used_items

🔹 MODELS - cat_flask/app/Models/
   ├── TestSession.php - Session management
   ├── TestResponse.php - Individual responses  
   ├── ItemParameter.php - Item bank
   ├── UsedItem.php - Used item tracking
   └── User.php - Laravel default

🔹 MIGRATIONS - cat_flask/database/migrations/
   └── Database schema definitions
```

### **🐍 Flask API Files (Python Calculation Engine)**
```
🔹 MAIN API - cat_api.py
   ├── Flask server (1000+ lines)
   ├── IRT 3PL calculations
   ├── Endpoints: /health, /api/estimate-theta, /api/select-item
   ├── Functions: estimate_theta_map(), select_next_item_mi()
   ├── Performance logging to cat_api.log
   └── Item bank loading from CSV

🔹 ITEM BANK - Parameter_Item_IST.csv
   ├── Item parameters (a, b, g, u)
   ├── Loaded at Flask startup
   └── Used for all IRT calculations

🔹 DEPENDENCIES - requirements.txt
   ├── Python package requirements
   ├── Flask, numpy, pandas, scipy, psutil
   └── Used for pip install

🔹 LOGS - cat_api.log
   ├── Performance monitoring output
   ├── Process timing and resource usage
   └── API request logging
```

### **⚙️ Configuration Files**
```
🔹 LARAVEL CONFIG - cat_flask/config/
   ├── app.php - Main Laravel config
   ├── cat.php - CAT-specific settings
   ├── database.php - Database configuration
   └── Other Laravel configs

🔹 BOOTSTRAP - cat_flask/bootstrap/app.php
   ├── Laravel application initialization
   └── Service provider registration

🔹 SERVICE PROVIDER - cat_flask/app/Providers/AppServiceProvider.php
   ├── Dependency injection container
   ├── Register: FlaskApiService, HybridCATService
   └── Service binding and resolution

🔹 ENVIRONMENT - cat_flask/.env
   ├── Environment variables
   ├── Database paths, API URLs
   └── Application secrets
```

### **🛠️ Development Tools**
```
🔹 CONSOLE COMMANDS - cat_flask/app/Console/Commands/
   ├── StartFlaskApiCommand.php - Start Flask from Laravel
   ├── TestFlaskApiCommand.php - Test API endpoints
   └── TestPerformanceMonitor.php - Performance testing

🔹 BATCH FILES - Development scripts
   ├── debug_flask.bat - Debug Flask API
   ├── restart_flask_api.bat - Restart Flask
   ├── start_hybrid_system.bat - Start complete system
   └── simple_restart.bat - Quick restart

🔹 DEPENDENCIES - Package management
   ├── composer.json - PHP dependencies (Laravel)
   ├── composer.lock - Locked PHP versions
   └── requirements.txt - Python dependencies
```

---

## 🚀 Execution Commands

### **Development:**
```bash
# Start Laravel
php artisan serve

# Start Flask API  
python cat_api.py

# Run tests
php artisan cat:test-flask-api
```

### **Production:**
```bash
# Start with PM2
pm2 start cat_api.py --name cat-api

# Nginx proxy setup (see DEPLOYMENT_GUIDE.md)
```

---

## 📊 Performance Monitoring

**Log Files:**
- **`cat_api.log`** - Flask API performance logs
- **`cat_flask/storage/logs/laravel.log`** - Laravel application logs

**Monitoring Functions:**
- Memory usage tracking
- CPU load monitoring  
- Response time measurement
- API endpoint performance

---

## 🔧 Configuration Files

**Environment:**
- **`.env`** - Laravel environment configuration
- **`config/cat.php`** - CAT-specific settings

**Dependencies:**
- **`composer.json`** - PHP dependencies (Laravel)
- **`requirements.txt`** - Python dependencies (Flask)

**Development Scripts:**
- **`debug_flask.bat`** - Debug Flask API
- **`restart_flask_api.bat`** - Restart Flask API
- **`start_hybrid_system.bat`** - Start complete system

---

Esta documentación proporciona una visión completa del flujo de archivos y procesos en el sistema CAT, facilitando el mantenimiento y desarrollo futuro del sistema.
