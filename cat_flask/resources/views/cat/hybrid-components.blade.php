<!-- Status Display dengan API Source Info -->
<div id="status-display" class="alert alert-secondary">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong><i class="fas fa-info-circle me-1"></i>Status:</strong> 
            <span id="status-text">Klik 'Mulai Tes' untuk memulai</span>
        </div>
        <div>
            <span class="badge bg-secondary me-2" id="api-source">CHECKING...</span>
            <span class="badge bg-info" id="api-status">Initializing...</span>
        </div>
    </div>
</div>

<!-- API Information Panel (tambahkan setelah status display) -->
<div id="api-info-panel" class="card border-info mb-3" style="display: none;">
    <div class="card-body p-3">
        <h6 class="card-title text-info mb-2">
            <i class="fas fa-cogs me-2"></i>API Information
        </h6>
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">Calculation Engine:</small>
                <p class="mb-1" id="calculation-engine">-</p>
            </div>
            <div class="col-md-6">
                <small class="text-muted">Flask API Status:</small>
                <p class="mb-1" id="flask-status">-</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">Response Time:</small>
                <p class="mb-1" id="response-time">-</p>
            </div>
            <div class="col-md-6">
                <small class="text-muted">Fallback Available:</small>
                <p class="mb-1" id="fallback-status">Yes (Laravel)</p>
            </div>
        </div>
    </div>
</div>

<!-- Tambahkan button untuk toggle API info -->
<div class="text-center mb-3">
    <button class="btn btn-sm btn-outline-info" onclick="toggleApiInfo()">
        <i class="fas fa-info-circle me-1"></i>API Info
    </button>
    <button class="btn btn-sm btn-outline-secondary" onclick="testFlaskConnection()">
        <i class="fas fa-flask me-1"></i>Test Flask
    </button>
</div>

<!-- Modifikasi Control Buttons untuk menampilkan API source -->
<div class="text-center">
    <button id="btn-start" class="btn btn-primary btn-lg me-3 mb-2" onclick="startTest()">
        <i class="fas fa-play me-2"></i>Mulai Tes
    </button>
    <button id="btn-correct" class="btn btn-success me-2 mb-2" onclick="submitResponse(1)" disabled>
        <i class="fas fa-check me-2"></i>Benar
    </button>
    <button id="btn-incorrect" class="btn btn-danger mb-2" onclick="submitResponse(0)" disabled>
        <i class="fas fa-times me-2"></i>Salah
    </button>
</div>

<!-- API Source indicator di final results -->
<div class="col-12" id="api-source-info" style="display: none;">
    <div class="alert alert-info">
        <h6><i class="fas fa-server me-2"></i>Computation Details</h6>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Calculation Engine:</strong> <span id="final-api-source">-</span></p>
                <p class="mb-1"><strong>Total API Calls:</strong> <span id="total-api-calls">-</span></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Average Response Time:</strong> <span id="avg-response-time">-</span></p>
                <p class="mb-1"><strong>Fallback Events:</strong> <span id="fallback-events">0</span></p>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk handling API info -->
<script>
function toggleApiInfo() {
    const panel = document.getElementById('api-info-panel');
    const isVisible = panel.style.display !== 'none';
    panel.style.display = isVisible ? 'none' : 'block';
    
    if (!isVisible) {
        updateApiInfoPanel();
    }
}

function updateApiInfoPanel() {
    // Update calculation engine
    const engine = testData.apiSource === 'flask' ? 
        'Flask API (Python)' : 'Laravel (PHP)';
    document.getElementById('calculation-engine').textContent = engine;
    
    // Update Flask status
    fetch('/api/flask-health')
        .then(response => response.json())
        .then(data => {
            const status = data.status === 'healthy' ? 
                'Online ✅' : 'Offline ❌';
            document.getElementById('flask-status').textContent = status;
        })
        .catch(() => {
            document.getElementById('flask-status').textContent = 'Offline ❌';
        });
}

// Modified startTest function with API source tracking
async function startTest() {
    const startTime = Date.now();
    showLoading(true);
    updateStatus('Memulai sesi tes baru...', 'info');
    
    try {
        const response = await fetch('/api/start-test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const endTime = Date.now();
        const responseTime = endTime - startTime;
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Update API tracking
        testData.apiSource = data.api_source || 'unknown';
        testData.apiCalls = (testData.apiCalls || 0) + 1;
        testData.totalResponseTime = (testData.totalResponseTime || 0) + responseTime;
        
        // Store session data
        currentSession = {
            sessionId: data.session_id,
            currentItem: data.item,
            theta: data.theta,
            se: data.se,
            itemNumber: data.item_number,
            apiSource: data.api_source || 'unknown'
        };
        
        // Update UI
        displayItem(data.item, data.theta, data.probability, data.information);
        updateProgress(1, 30);
        
        const statusMsg = `Tes dimulai! Engine: ${data.api_source?.toUpperCase() || 'UNKNOWN'} (${responseTime}ms)`;
        updateStatus(statusMsg, 'success');
        
        // Update API source display
        document.getElementById('api-source').textContent = data.api_source?.toUpperCase() || 'UNKNOWN';
        document.getElementById('api-source').className = `badge ${data.api_source === 'flask' ? 'bg-success' : 'bg-warning'}`;
        
        // Update response time display
        document.getElementById('response-time').textContent = `${responseTime}ms`;
        
        // Enable response buttons
        document.getElementById('btn-correct').disabled = false;
        document.getElementById('btn-incorrect').disabled = false;
        document.getElementById('btn-start').disabled = true;
        
    } catch (error) {
        console.error('Error starting test:', error);
        updateStatus('Error memulai tes: ' + error.message, 'danger');
        
        // Track fallback events
        testData.fallbackEvents = (testData.fallbackEvents || 0) + 1;
        document.getElementById('fallback-events').textContent = testData.fallbackEvents;
        
    } finally {
        showLoading(false);
    }
}

// Modified submitResponse function with API source tracking
async function submitResponse(answer) {
    if (!currentSession) {
        updateStatus('Tidak ada sesi aktif', 'danger');
        return;
    }
    
    const startTime = Date.now();
    showLoading(true);
    updateStatus('Memproses jawaban...', 'info');
    
    // Disable response buttons
    document.getElementById('btn-correct').disabled = true;
    document.getElementById('btn-incorrect').disabled = true;
    
    try {
        const response = await fetch('/api/submit-response', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                session_id: currentSession.sessionId,
                item_id: currentSession.currentItem.id,
                answer: answer
            })
        });
        
        const endTime = Date.now();
        const responseTime = endTime - startTime;
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Update API tracking
        testData.apiCalls = (testData.apiCalls || 0) + 1;
        testData.totalResponseTime = (testData.totalResponseTime || 0) + responseTime;
        
        // Store response
        testData.responses.push({
            item_id: currentSession.currentItem.id,
            answer: answer,
            item_parameters: {
                a: currentSession.currentItem.a,
                b: currentSession.currentItem.b,
                g: currentSession.currentItem.g
            },
            theta_before: currentSession.theta,
            theta_after: data.theta,
            se_before: currentSession.se,
            se_after: data.se,
            api_source: data.api_source,
            response_time: responseTime
        });
        
        // Update histories
        testData.thetaHistory.push(data.theta);
        testData.seHistory.push(data.se);
        
        // Display response result with API info
        displayResponseResult(
            answer,
            currentSession.theta,
            data.theta,
            currentSession.se,
            data.se,
            testData.itemCount,
            data.api_source,
            responseTime
        );
        
        // Update chart
        updateChart();
        
        if (data.test_completed) {
            // Test completed
            handleTestCompletion(data);
        } else {
            // Continue with next item
            currentSession.currentItem = data.item;
            currentSession.theta = data.theta;
            currentSession.se = data.se;
            currentSession.itemNumber = data.item_number;
            testData.itemCount = data.item_number;
            
            // Update UI
            displayItem(data.item, data.theta, data.probability, data.information);
            updateProgress(data.item_number, 30);
            
            const statusMsg = `Item ${data.item_number} - ${data.api_source?.toUpperCase() || 'UNKNOWN'} (${responseTime}ms)`;
            updateStatus(statusMsg, 'primary');
            
            // Re-enable response buttons
            document.getElementById('btn-correct').disabled = false;
            document.getElementById('btn-incorrect').disabled = false;
        }
        
    } catch (error) {
        console.error('Error submitting response:', error);
        updateStatus('Error memproses jawaban: ' + error.message, 'danger');
        
        // Track fallback events
        testData.fallbackEvents = (testData.fallbackEvents || 0) + 1;
        document.getElementById('fallback-events').textContent = testData.fallbackEvents;
        
        // Re-enable response buttons on error
        document.getElementById('btn-correct').disabled = false;
        document.getElementById('btn-incorrect').disabled = false;
    } finally {
        showLoading(false);
    }
}

// Modified displayResponseResult to include API info
function displayResponseResult(answer, thetaBefore, thetaAfter, seBefore, seAfter, itemNumber, apiSource, responseTime) {
    const resultsDisplay = document.getElementById('results-display');
    const answerText = answer === 1 ? 'Benar' : 'Salah';
    const answerClass = answer === 1 ? 'success' : 'danger';
    const thetaChange = thetaAfter - thetaBefore;
    const seChange = seAfter - seBefore;
    
    const resultHTML = `
        <div class="border rounded p-3 mb-3 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Item ${itemNumber}</h6>
                <div>
                    <span class="badge bg-${answerClass}">${answerText}</span>
                    <span class="badge bg-${apiSource === 'flask' ? 'success' : 'warning'} ms-1">${apiSource?.toUpperCase() || 'UNKNOWN'}</span>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <small class="text-muted">θ: ${thetaBefore.toFixed(3)} → ${thetaAfter.toFixed(3)}</small>
                    <small class="text-info d-block">(Δ: ${thetaChange >= 0 ? '+' : ''}${thetaChange.toFixed(3)})</small>
                </div>
                <div class="col-4">
                    <small class="text-muted">SE: ${seBefore.toFixed(3)} → ${seAfter.toFixed(3)}</small>
                    <small class="text-warning d-block">(Δ: ${seChange >= 0 ? '+' : ''}${seChange.toFixed(3)})</small>
                </div>
                <div class="col-4">
                    <small class="text-muted">Response Time:</small>
                    <small class="text-secondary d-block">${responseTime}ms</small>
                </div>
            </div>
        </div>
    `;
    
    resultsDisplay.innerHTML = resultHTML + resultsDisplay.innerHTML;
}

// Modified handleTestCompletion to show API statistics
function handleTestCompletion(data) {
    updateStatus('Tes selesai!', 'success');
    
    // Disable all buttons
    document.getElementById('btn-correct').disabled = true;
    document.getElementById('btn-incorrect').disabled = true;
    
    // Hide item display
    document.getElementById('item-display').style.display = 'none';
    
    // Calculate API statistics
    const avgResponseTime = testData.totalResponseTime / testData.apiCalls;
    
    // Update API statistics display
    document.getElementById('total-api-calls').textContent = testData.apiCalls || 0;
    document.getElementById('avg-response-time').textContent = `${avgResponseTime.toFixed(0)}ms`;
    document.getElementById('fallback-events').textContent = testData.fallbackEvents || 0;
    document.getElementById('final-api-source').textContent = testData.apiSource?.toUpperCase() || 'UNKNOWN';
    
    // Show API source info
    document.getElementById('api-source-info').style.display = 'block';
    
    // Show final results
    displayFinalResults({
        finalScore: data.final_score,
        finalTheta: data.theta,
        finalSE: data.se,
        totalItems: data.total_items || testData.itemCount,
        stopReason: data.stop_reason,
        apiSource: data.api_source || testData.apiSource
    });
    
    // Enable start button for new test
    document.getElementById('btn-start').disabled = false;
    document.getElementById('btn-start').innerHTML = '<i class="fas fa-redo me-2"></i>Mulai Tes Baru';
}

// Initialize tracking data
let testData = {
    responses: [],
    thetaHistory: [0],
    seHistory: [1.0],
    itemCount: 0,
    apiSource: 'unknown',
    apiCalls: 0,
    totalResponseTime: 0,
    fallbackEvents: 0
};
</script>
