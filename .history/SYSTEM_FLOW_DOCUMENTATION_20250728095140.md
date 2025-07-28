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

## 🔄 Alur Proses CAT System

### **1. System Startup**
```
1. Laravel Bootstrap (bootstrap/app.php)
2. Load Configuration (config/*)
3. Register Services (Providers/AppServiceProvider.php)
4. Start Flask API (cat_api.py)
5. Route Registration (routes/web.php)
```

### **2. Test Session Flow**
```
1. User → /cat/hybrid → HomeController@index
2. Frontend → POST /api/start-test → HybridCATController@startTest
3. HybridCATController → HybridCATService@startSession
4. HybridCATService → FlaskApiService@selectNextItem
5. FlaskApiService → HTTP POST localhost:5000/api/select-item
6. Flask API → cat_api.py → select_next_item_mi()
7. Response: First item returned to frontend
```

### **3. Answer Submission Flow**
```
1. User submits answer → POST /api/submit-response
2. HybridCATController@submitResponse
3. HybridCATService@submitResponse:
   a. Save response to database (TestResponse)
   b. Mark item as used (UsedItem)
   c. Request theta estimation (FlaskApiService)
   d. Request stopping criteria check
   e. If continue: Request next item
   f. If stop: Calculate final score
4. Return result to frontend
```

### **4. Flask API Calculation Flow**
```
1. Laravel → HTTP Request → Flask API
2. cat_api.py receives request
3. Validate request data
4. Execute calculation:
   - estimate_theta_map() untuk real-time
   - estimate_theta_eap() untuk final scoring
   - select_next_item_mi() untuk item selection
   - check_stopping_criteria() untuk stopping
5. Log performance (log_process_performance)
6. Return JSON response
```

### **5. Database Operations**
```
TestSession (session management)
↓ hasMany
TestResponse (individual responses)
↓ belongsTo  
ItemParameter (item bank)
↑ hasMany
UsedItem (used item tracking)
```

### **6. Fallback Mechanism**
```
1. HybridCATService checks Flask API health
2. If Flask API available: Use FlaskApiService
3. If Flask API unavailable: Use CATService (PHP fallback)
4. Log warning about fallback usage
5. Continue test with available calculation method
```

---

## 🔍 Key Dependencies

### **Between Services:**
- **HybridCATService** depends on: FlaskApiService, CATService, PerformanceMonitorService
- **FlaskApiService** depends on: Flask API (cat_api.py)
- **Controllers** depend on: HybridCATService

### **Between Models:**
- **TestSession** has many: TestResponse, UsedItem
- **TestResponse** belongs to: TestSession, ItemParameter
- **UsedItem** belongs to: TestSession, ItemParameter

### **API Dependencies:**
- **Laravel API** → **Flask API** (primary calculation engine)
- **Flask API** → **Parameter_Item_IST.csv** (item bank)
- **All services** → **SQLite Database** (data persistence)

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
