# 📋 Dokumentasi Alur Sistem CAT (Computer Adaptive Testing)

*📖 Panduan lengkap untuk developer baru yang ingin memahami dan mengimplementasikan sistem CAT*

## 🚀 **Quick Start untuk Developer Baru**

### **Prerequisites:**
```bash
# Tools yang dibutuhkan:
- PHP 8.1+ (dengan Composer)
- Python 3.8+ (dengan pip)
- SQLite3
- Git
```

### **Setup Development Environment (5 menit):**
```bash
# 1. Clone repository
git clone https://github.com/RafiSuwargana/CAT_SYSTEM.git
cd CAT_SYSTEM

# 2. Install Laravel dependencies
cd cat_flask
composer install
cp .env.example .env
php artisan key:generate

# 3. Install Python dependencies
cd ..
pip install -r requirements.txt

# 4. Start development servers
# Terminal 1: Flask API
python cat_api.py

# Terminal 2: Laravel
cd cat_flask
php artisan serve

# 5. Test system
# Browser: http://localhost:8000/cat/hybrid
```

### **Struktur Project untuk Developer:**
```
📁 CAT_SYSTEM/
├── 🐍 cat_api.py              ← Python Flask API (main calculation engine)
├── 📊 Parameter_Item_IST.csv  ← Item bank data
├── 📋 requirements.txt        ← Python dependencies
└── 📁 cat_flask/              ← Laravel application
    ├── 🌐 routes/web.php       ← URL routing
    ├── 🎯 app/Http/Controllers/ ← Request handlers
    ├── 🔧 app/Services/        ← Business logic
    ├── 🗄️ app/Models/          ← Database entities
    ├── 🎨 resources/views/     ← Frontend templates
    ├── 📱 public/js/           ← Frontend JavaScript
    └── 🗃️ database/            ← Database & migrations
```

---

## 🏗️ Arsitektur Sistem

Sistem CAT ini menggunakan **arsitektur hybrid** yang menggabungkan Laravel (PHP) dan Flask (Python):

- **Laravel**: Frontend, database management, session handling, UI
- **Flask API**: IRT calculations, theta estimation, item selection (MANDATORY)
- **SQLite**: Database untuk menyimpan sessions, responses, dan item parameters

> **⚠️ PENTING**: Flask API adalah komponen WAJIB. Sistem tidak akan berfungsi tanpa Flask API yang aktif.

---

## � **Execution Commands & Startup**

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
- **Dependencies:** FlaskApiService, PerformanceMonitorService
- **Flow:**
  1. Check Flask API availability (MANDATORY)
  2. Use Flask for all calculations
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

## �️ **Database Schema untuk Developer**

### **📋 Table Structures:**

#### **🔹 test_sessions** (Session Management)
```sql
CREATE TABLE test_sessions (
    session_id VARCHAR(255) PRIMARY KEY,    -- 'CAT_timestamp_random'
    theta DECIMAL(8,6) DEFAULT 0.0,         -- Current ability estimate
    standard_error DECIMAL(8,6) DEFAULT 1.0, -- Current SE
    test_completed BOOLEAN DEFAULT 0,       -- Test completion status
    stop_reason TEXT NULL,                  -- Why test stopped
    final_score DECIMAL(5,2) NULL,         -- Final IQ score
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### **🔹 test_responses** (Individual Answers)
```sql
CREATE TABLE test_responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(255),                -- FK to test_sessions
    item_id VARCHAR(50),                   -- FK to item_parameters
    item_order INTEGER,                    -- Question sequence (1,2,3...)
    answer INTEGER,                        -- 0=wrong, 1=correct
    response_time INTEGER NULL,            -- Time in milliseconds
    theta_after_response DECIMAL(8,6),     -- Theta after this response
    created_at TIMESTAMP,
    
    FOREIGN KEY (session_id) REFERENCES test_sessions(session_id),
    FOREIGN KEY (item_id) REFERENCES item_parameters(id)
);
```

#### **🔹 item_parameters** (Question Bank)
```sql
CREATE TABLE item_parameters (
    id VARCHAR(50) PRIMARY KEY,            -- Item identifier
    a_parameter DECIMAL(8,6),              -- Discrimination
    b_parameter DECIMAL(8,6),              -- Difficulty
    g_parameter DECIMAL(8,6),              -- Guessing
    u_parameter DECIMAL(8,6) DEFAULT 1.0,  -- Upper asymptote
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### **🔹 used_items** (Usage Tracking)
```sql
CREATE TABLE used_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(255),               -- FK to test_sessions
    item_id VARCHAR(50),                  -- FK to item_parameters
    used_at TIMESTAMP,
    
    FOREIGN KEY (session_id) REFERENCES test_sessions(session_id),
    FOREIGN KEY (item_id) REFERENCES item_parameters(id),
    UNIQUE(session_id, item_id)           -- Prevent duplicate usage
);
```

### **📊 Data Flow Example:**
```
1. User starts test → test_sessions record created
2. User answers Q1  → test_responses record + used_items record
3. User answers Q2  → test_responses record + used_items record
4. Test completes   → test_sessions.test_completed = true
```

---

## 🌐 **API Endpoints & Examples untuk Developer**

### **🔹 Laravel API Endpoints (Frontend ↔ Laravel)**

#### **Start Test Session**
```javascript
// Request
POST /api/start-test
Headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': 'csrf_token_here'
}
Body: {}

// Response
{
    "session_id": "CAT_1643723456_7890",
    "item": {
        "id": "item_001",
        "a": 1.5,
        "b": -0.8,
        "g": 0.2,
        "u": 1.0
    },
    "theta": 0.0,
    "se": 1.0,
    "probability": 0.65,
    "information": 0.34,
    "item_number": 1,
    "api_source": "flask"
}
```

#### **Submit Response**
```javascript
// Request
POST /api/submit-response
Body: {
    "session_id": "CAT_1643723456_7890",
    "item_id": "item_001", 
    "answer": 1  // 1=correct, 0=incorrect
}

// Response (Continue)
{
    "test_completed": false,
    "item": {
        "id": "item_045",
        "a": 2.1,
        "b": 0.3,
        "g": 0.15
    },
    "theta": 0.75,
    "se": 0.65,
    "probability": 0.72,
    "information": 1.23,
    "item_number": 2,
    "api_source": "flask"
}

// Response (Test Complete)
{
    "test_completed": true,
    "final_theta": 1.85,
    "se_eap": 0.22,
    "final_score": 127.75,
    "total_items": 15,
    "stop_reason": "SE_EAP mencapai 0.25",
    "api_source": "flask"
}
```

### **🔹 Flask API Endpoints (Laravel ↔ Flask)**

#### **Estimate Theta**
```python
# Request
POST localhost:5000/api/estimate-theta
{
    "responses": [
        {"a": 1.5, "b": -0.8, "g": 0.2, "answer": 1},
        {"a": 2.1, "b": 0.3, "g": 0.15, "answer": 0}
    ],
    "theta_old": 0.0
}

# Response
{
    "theta": 0.42,
    "se": 0.78,
    "method": "MAP",
    "n_responses": 2,
    "theta_old": 0.0
}
```

#### **Select Next Item**
```python
# Request
POST localhost:5000/api/select-item
{
    "theta": 0.42,
    "used_item_ids": ["item_001", "item_045"],
    "responses": [...]
}

# Response
{
    "item": {
        "id": "item_123",
        "a": 1.8,
        "b": 0.5,
        "g": 0.18,
        "u": 1.0
    },
    "probability": 0.68,
    "information": 1.45,
    "fisher_information": 1.45,
    "method": "MI",
    "available_items": 148
}
```

---

## 💻 **Code Implementation Examples**

### **🔹 Frontend JavaScript Patterns**

#### **Making API Calls**
```javascript
// Standard pattern untuk API calls
async function makeApiCall(endpoint, data = {}) {
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        return result;
    } catch (error) {
        console.error('API call failed:', error);
        updateStatus('Error: ' + error.message, 'danger');
        throw error;
    }
}

// Usage example
async function startTest() {
    const data = await makeApiCall('/api/start-test');
    displayItem(data.item);
    updateTheta(data.theta);
}
```

#### **State Management**
```javascript
// Global state object
const appState = {
    currentSession: null,
    testData: {
        responses: [],
        thetaHistory: [0],
        seHistory: [1.0],
        itemCount: 0,
        apiSource: 'unknown'
    },
    
    // State update methods
    updateTheta(newTheta) {
        this.testData.thetaHistory.push(newTheta);
        this.updateChart();
    },
    
    addResponse(response) {
        this.testData.responses.push(response);
        this.displayResponseHistory();
    },
    
    reset() {
        this.currentSession = null;
        this.testData = {
            responses: [],
            thetaHistory: [0],
            seHistory: [1.0],
            itemCount: 0,
            apiSource: 'unknown'
        };
    }
};
```

### **🔹 Laravel Controller Patterns**

#### **Standard Controller Method**
```php
// app/Http/Controllers/HybridCATController.php
public function startTest(): JsonResponse
{
    try {
        // Input validation (if needed)
        // $request->validate([...]);
        
        // Call service layer
        $result = $this->hybridCatService->startSession();
        
        // Return JSON response
        return response()->json($result);
        
    } catch (Exception $e) {
        // Error handling
        Log::error('Error starting test: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

#### **Service Layer Pattern**
```php
// app/Services/HybridCATService.php
public function startSession(): array
{
    DB::beginTransaction();
    
    try {
        // 1. Generate session ID
        $sessionId = 'CAT_' . time() . '_' . rand(1000, 9999);
        
        // 2. Create database record
        $session = TestSession::create([
            'session_id' => $sessionId,
            'theta' => 0.0,
            'standard_error' => 1.0,
            'test_completed' => false
        ]);
        
        // 3. Select first item
        $itemData = $this->selectFirstItem();
        
        // 4. Commit transaction
        DB::commit();
        
        // 5. Return result
        return [
            'session_id' => $sessionId,
            'item' => $itemData['item'],
            'theta' => 0.0,
            'se' => 1.0,
            'probability' => $itemData['probability'],
            'information' => $itemData['information'],
            'item_number' => 1,
            'api_source' => $this->useFlaskApi ? 'flask' : 'laravel'
        ];
        
    } catch (Exception $e) {
        DB::rollback();
        throw $e;
    }
}
```

### **🔹 Database Model Patterns**

#### **Model dengan Relationships**
```php
// app/Models/TestSession.php
class TestSession extends Model
{
    protected $primaryKey = 'session_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'session_id', 'theta', 'standard_error', 
        'test_completed', 'stop_reason', 'final_score'
    ];
    
    protected $casts = [
        'theta' => 'decimal:6',
        'standard_error' => 'decimal:6',
        'test_completed' => 'boolean',
        'final_score' => 'decimal:2',
    ];
    
    // Relationships
    public function responses(): HasMany
    {
        return $this->hasMany(TestResponse::class, 'session_id', 'session_id')
                    ->orderBy('item_order');
    }
    
    public function usedItems(): HasMany
    {
        return $this->hasMany(UsedItem::class, 'session_id', 'session_id');
    }
    
    // Helper methods
    public function getLatestTheta(): float
    {
        $latestResponse = $this->responses()->latest()->first();
        return $latestResponse ? $latestResponse->theta_after_response : $this->theta;
    }
    
    public function getTotalResponses(): int
    {
        return $this->responses()->count();
    }
}
```

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

## 🔍 **Dependencies & File Relationships**

### **🔄 Service Dependencies:**
```
HybridCATController
├── → HybridCATService (core business logic)
├── → FlaskApiService (API communication)  
└── → PerformanceMonitorService (monitoring)

HybridCATService  
├── → FlaskApiService (preferred calculations)
├── → CATService (fallback calculations)
├── → PerformanceMonitorService (logging)
└── → Models (database operations)

FlaskApiService
├── → Flask API HTTP endpoints (cat_api.py)
├── → Error handling & validation
└── → Request/response formatting

Frontend (cat-hybrid.js)
├── → Laravel API endpoints (/api/*)
├── → CSRF token handling
├── → Real-time UI updates
└── → Chart.js visualization
```

### **�️ Database Model Relationships:**
```
TestSession (1) ←→ (Many) TestResponse
TestSession (1) ←→ (Many) UsedItem  
ItemParameter (1) ←→ (Many) TestResponse
ItemParameter (1) ←→ (Many) UsedItem

Flow:
1. TestSession created → Generate session_id
2. TestResponse created → Links to session & item
3. UsedItem created → Tracks item usage
4. ItemParameter referenced → Provides a,b,g,u values
```

### **🌐 API Communication Chain:**
```
🖥️ User Browser (cat-hybrid.js)
    ↓ AJAX POST /api/start-test
�🚀 Laravel Route (web.php)
    ↓ Route to controller
🎯 HybridCATController@startTest  
    ↓ Call service method
🔧 HybridCATService@startSession
    ↓ HTTP request
🌍 FlaskApiService@selectNextItem
    ↓ POST localhost:5000/api/select-item
🐍 Flask API (cat_api.py)
    ↓ IRT calculation
📊 select_next_item_mi() function
    ↓ JSON response
🔙 Response chain back to browser
    ↓ Update UI
🎨 Frontend displays item & statistics
```

### **📁 File Loading & Initialization Order:**
```
1. 🐍 Flask API Startup (cat_api.py)
   ├── Load Parameter_Item_IST.csv → ITEM_BANK
   ├── Initialize logging → cat_api.log  
   ├── Setup endpoints & CORS
   └── Start server on localhost:5000

2. 🚀 Laravel Bootstrap (bootstrap/app.php)
   ├── Load configuration files (config/*)
   ├── Register services (AppServiceProvider.php)
   ├── Initialize database connection
   └── Setup routing (routes/web.php)

3. 🌐 Frontend Load (hybrid.blade.php)
   ├── Load cat-hybrid.js
   ├── Initialize Chart.js
   ├── Setup CSRF token
   ├── Check Flask API health
   └── Ready for user interaction
```

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

---

## 🛠️ **Development Workflow untuk Developer Baru**

### **📝 Cara Menambah Fitur Baru:**

#### **1. Frontend (JavaScript)**
```javascript
// Langkah 1: Tambah function di cat-hybrid.js
async function newFeature() {
    try {
        showLoading(true);
        const data = await makeApiCall('/api/new-endpoint', requestData);
        updateUI(data);
    } catch (error) {
        handleError(error);
    } finally {
        showLoading(false);
    }
}

// Langkah 2: Tambah button/trigger di hybrid.blade.php
<button onclick="newFeature()" class="btn btn-primary">New Feature</button>

// Langkah 3: Tambah UI element untuk hasil
<div id="new-feature-result" style="display: none;">
    <!-- Result display -->
</div>
```

#### **2. Backend (Laravel Route → Controller → Service)**
```php
// Langkah 1: Tambah route di routes/web.php
Route::post('/api/new-endpoint', [HybridCATController::class, 'newFeature'])->name('api.new-feature');

// Langkah 2: Tambah method di HybridCATController.php
public function newFeature(Request $request): JsonResponse
{
    try {
        $request->validate([
            'required_field' => 'required|string'
        ]);
        
        $result = $this->hybridCatService->processNewFeature($request->all());
        return response()->json($result);
        
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

// Langkah 3: Tambah method di HybridCATService.php
public function processNewFeature(array $data): array
{
    // Business logic here
    return ['success' => true, 'data' => $result];
}
```

### **🔧 Debugging & Testing Commands:**

```bash
# Laravel Debugging
tail -f cat_flask/storage/logs/laravel.log    # Watch logs
php artisan route:list                        # See all routes
php artisan tinker                           # Interactive shell

# Flask API Testing
curl http://localhost:5000/health            # Health check
curl -X POST http://localhost:5000/api/estimate-theta \
  -H "Content-Type: application/json" \
  -d '{"responses":[{"a":1.5,"b":0.0,"g":0.2,"answer":1}]}'

# Development Commands
php artisan serve                            # Start Laravel
python cat_api.py                           # Start Flask API
php artisan cat:test-flask-api              # Test Flask endpoints
```

---

## 🐛 **Common Issues & Solutions untuk Developer**

### **🔹 Flask API Connection Issues**
```javascript
// Problem: 502 Bad Gateway atau connection refused
// Check: Is Flask API running on port 5000?
// Solution: Start Flask API dengan python cat_api.py

// Check in browser: http://localhost:5000/health
// Should return: {"status": "healthy", "version": "1.0.0"}
```

### **🔹 CSRF Token Issues** 
```javascript
// Problem: 419 CSRF token mismatch
// Solution: Always include CSRF token dalam headers
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    'Content-Type': 'application/json'
}
```

### **🔹 Database Issues**
```bash
# Problem: Database file tidak ada
# Solution: Buat database dan run migrations
touch cat_flask/database/database.sqlite
cd cat_flask
php artisan migrate

# Problem: Permission denied pada database
# Solution: Fix permissions
chmod 664 database/database.sqlite
chmod 775 database/
```

---

## 📚 **Learning Path untuk Developer Baru**

### **🎯 Sequence Belajar yang Recommended:**

#### **Week 1: Understanding the Architecture**
- ✅ Study file structure dan architecture diagram
- ✅ Understand Laravel MVC pattern (routes → controllers → services → models)  
- ✅ Understand Frontend-Backend communication (AJAX → Laravel API)
- ✅ Learn database schema dan relationships

#### **Week 2: Hands-on Development**
- ✅ Setup development environment  
- ✅ Run the system dan test all features
- ✅ Make small modifications (change UI text, add console.log)
- ✅ Add simple new API endpoint dan frontend button

#### **Week 3: Advanced Implementation**
- ✅ Understand IRT calculations dan business logic
- ✅ Learn Flask API integration dan fallback mechanism
- ✅ Practice debugging dengan logs dan browser dev tools
- ✅ Implement new feature end-to-end

### **🔗 Essential Concepts:**

#### **Laravel (PHP Backend):**
- **Routing:** URLs → Controllers
- **Controllers:** Handle HTTP requests 
- **Services:** Business logic separation
- **Models:** Database interaction dengan Eloquent ORM
- **Middleware:** Request processing pipeline

#### **Frontend (JavaScript):**
- **AJAX/Fetch:** API communication
- **DOM Manipulation:** Update UI elements
- **Event Handling:** User interactions
- **State Management:** Track application state
- **Error Handling:** User-friendly error messages

#### **Flask (Python API):**
- **REST Endpoints:** HTTP API creation
- **JSON Processing:** Request/response handling
- **Mathematical Libraries:** NumPy, SciPy untuk calculations
- **CORS:** Cross-origin request handling

---

## 🚀 **Quick Reference untuk Implementation**

### **🔹 File Modification Checklist:**

#### **Adding New Frontend Feature:**
```
□ 1. Add JavaScript function in public/js/cat-hybrid.js
□ 2. Add UI element in resources/views/cat/hybrid.blade.php  
□ 3. Add CSS styling if needed
□ 4. Test frontend functionality
```

#### **Adding New Backend Endpoint:**
```
□ 1. Add route in routes/web.php
□ 2. Add controller method in app/Http/Controllers/HybridCATController.php
□ 3. Add service method in app/Services/HybridCATService.php
□ 4. Add database operations if needed (models, migrations)
□ 5. Test API endpoint dengan curl atau Postman
```

#### **Adding New Database Table:**
```
□ 1. Create migration: php artisan make:migration create_table_name
□ 2. Define schema in migration file
□ 3. Create model: php artisan make:model ModelName
□ 4. Define relationships in model
□ 5. Run migration: php artisan migrate
```

### **🔹 Testing Workflow:**
```
1. 🐍 Test Flask API: curl localhost:5000/health
2. 🚀 Test Laravel: php artisan serve → browser localhost:8000
3. 🌐 Test Frontend: Open /cat/hybrid → click buttons → check console
4. 🗄️ Test Database: php artisan tinker → Model::all()
5. 🔗 Test Integration: Full CAT test flow dari start sampai finish
```

**Dokumentasi ini sekarang comprehensive untuk developer baru yang ingin memahami dan mengimplementasikan sistem CAT! 🎯**
