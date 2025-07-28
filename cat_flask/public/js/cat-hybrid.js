/**
 * Frontend JavaScript untuk CAT System dengan Flask API Integration
 * 
 * Perubahan utama:
 * - Menggunakan endpoint hybrid API
 * - Menampilkan API source info
 * - Fallback handling
 * - Health check Flask API
 * - MAP (Maximum A Posteriori) untuk estimasi real-time
 * - MI (Maximum Fisher Information) untuk pemilihan item
 * - EAP (Expected A Posteriori) untuk skor akhir
 */

// Global variables
let currentSession = null;
let testData = {
    responses: [],
    thetaHistory: [0],
    seHistory: [1.0],
    itemCount: 0,
    apiSource: 'unknown'
};

// API Configuration
const API_CONFIG = {
    // Main endpoints (hybrid)
    START_TEST: '/api/start-test',
    SUBMIT_RESPONSE: '/api/submit-response',
    SESSION_HISTORY: '/api/session-history',
    
    // Flask API management
    FLASK_HEALTH: '/api/flask-health',
    API_INFO: '/api/api-info',
    
    // Test endpoints
    TEST_ESTIMATE_THETA: '/api/test/estimate-theta',
    TEST_NEXT_ITEM: '/api/test/next-item',
    TEST_CALCULATE_SCORE: '/api/test/calculate-score',
    TEST_STOPPING_CRITERIA: '/api/test/stopping-criteria'
};

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    checkFlaskApiHealth();
});

/**
 * Initialize application
 */
function initializeApp() {
    updateStatus('Sistem siap. Klik "Mulai Tes" untuk memulai.', 'info');
    resetUI();
    
    // Show API info
    showApiInfo();
    
    // Setup periodic health check
    setInterval(checkFlaskApiHealth, 30000); // Check every 30 seconds
}

/**
 * Check Flask API health status
 */
async function checkFlaskApiHealth() {
    try {
        const response = await fetch(API_CONFIG.FLASK_HEALTH);
        const data = await response.json();
        
        if (data.status === 'healthy') {
            updateApiStatus('Flask API: Online', 'success');
        } else {
            updateApiStatus('Flask API: Error', 'danger');
        }
    } catch (error) {
        updateApiStatus('Flask API: Offline (Using Laravel fallback)', 'warning');
    }
}

/**
 * Show API information
 */
async function showApiInfo() {
    try {
        const response = await fetch(API_CONFIG.API_INFO);
        const data = await response.json();
        
        testData.apiSource = data.current_source;
        
        // Update UI to show API source
        const apiSourceElement = document.getElementById('api-source');
        if (apiSourceElement) {
            apiSourceElement.textContent = data.current_source.toUpperCase();
            apiSourceElement.className = `badge ${data.current_source === 'flask' ? 'bg-success' : 'bg-warning'}`;
        }
        
        console.log('API Info:', data);
    } catch (error) {
        console.error('Failed to get API info:', error);
    }
}

/**
 * Update API status display
 */
function updateApiStatus(message, type) {
    const statusElement = document.getElementById('api-status');
    if (statusElement) {
        statusElement.textContent = message;
        statusElement.className = `badge bg-${type}`;
    }
}

/**
 * Start new test session
 */
async function startTest() {
    // Reset all global data before starting new test
    testData = {
        responses: [],
        thetaHistory: [0],
        seHistory: [1.0],
        itemCount: 0,
        apiSource: 'unknown'
    };
    currentSession = null;
    resetUI();

    showLoading(true);
    updateStatus('Memulai sesi tes baru...', 'info');

    try {
        const response = await fetch(API_CONFIG.START_TEST, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        // Store session data
        currentSession = {
            sessionId: data.session_id,
            currentItem: data.item,
            theta: data.theta,
            se: data.se,
            itemNumber: data.item_number,
            apiSource: data.api_source || 'unknown'
        };

        // Update test data
        testData.apiSource = data.api_source || 'unknown';
        testData.itemCount = 1;
        testData.responses = [];
        testData.thetaHistory = [data.theta];
        testData.seHistory = [data.se];

        // Update UI
        displayItem(data.item, data.theta, data.probability, data.information);
        updateProgress(1, 30);
        updateStatus(`Tes dimulai! API Source: ${data.api_source?.toUpperCase() || 'UNKNOWN'}`, 'success');

        // Enable response buttons
        document.getElementById('btn-correct').disabled = false;
        document.getElementById('btn-incorrect').disabled = false;
        document.getElementById('btn-start').disabled = true;

        // Show API source info
        showApiSourceInfo(data.api_source);

    } catch (error) {
        console.error('Error starting test:', error);
        updateStatus('Error memulai tes: ' + error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

/**
 * Submit response
 */
async function submitResponse(answer) {
    if (!currentSession) {
        updateStatus('Tidak ada sesi aktif', 'danger');
        return;
    }
    
    showLoading(true);
    updateStatus('Memproses jawaban...', 'info');
    
    // Disable response buttons
    document.getElementById('btn-correct').disabled = true;
    document.getElementById('btn-incorrect').disabled = true;
    
    try {
        const response = await fetch(API_CONFIG.SUBMIT_RESPONSE, {
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
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
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
            se_after: data.se
        });
        
        // Update histories
        testData.thetaHistory.push(data.theta);
        testData.seHistory.push(data.se);
        
        // Display response result
        displayResponseResult(
            answer,
            currentSession.theta,
            data.theta,
            currentSession.se,
            data.se,
            testData.itemCount
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
            updateStatus(`Item ${data.item_number} - API: ${data.api_source?.toUpperCase() || 'UNKNOWN'}`, 'primary');
            
            // Re-enable response buttons
            document.getElementById('btn-correct').disabled = false;
            document.getElementById('btn-incorrect').disabled = false;
        }
        
    } catch (error) {
        console.error('Error submitting response:', error);
        updateStatus('Error memproses jawaban: ' + error.message, 'danger');
        
        // Re-enable response buttons on error
        document.getElementById('btn-correct').disabled = false;
        document.getElementById('btn-incorrect').disabled = false;
    } finally {
        showLoading(false);
    }
}

/**
 * Handle test completion
 */
function handleTestCompletion(data) {
    updateStatus('Tes selesai!', 'success');
    
    // Disable all buttons
    document.getElementById('btn-correct').disabled = true;
    document.getElementById('btn-incorrect').disabled = true;
    
    // Hide item display
    document.getElementById('item-display').style.display = 'none';
    
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

/**
 * Display final results
 */
function displayFinalResults(results) {
    const finalResultsCard = document.getElementById('final-results-card');
    const finalResultsSummary = document.getElementById('final-results-summary');
    
    // Calculate statistics
    const correctAnswers = testData.responses.filter(r => r.answer === 1).length;
    const incorrectAnswers = testData.responses.filter(r => r.answer === 0).length;
    const accuracyRate = (correctAnswers / testData.responses.length * 100).toFixed(1);
    
    // Ensure values are numbers
    const finalScore = parseFloat(results.finalScore) || 0;
    const finalTheta = parseFloat(results.finalTheta) || 0;
    const finalSE = parseFloat(results.finalSE) || 0;
    const totalItems = parseInt(results.totalItems) || 0;
    
    finalResultsSummary.innerHTML = `
        <div class="row g-3">
            <div class="col-md-6">
                <div class="bg-light p-3 rounded">
                    <h6 class="text-primary mb-2"><i class="fas fa-trophy me-2"></i>Skor Akhir</h6>
                    <p class="h3 mb-0 text-success">${finalScore.toFixed(1)}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-light p-3 rounded">
                    <h6 class="text-info mb-2"><i class="fas fa-brain me-2"></i>Kemampuan (θ)</h6>
                    <p class="h4 mb-0">${finalTheta.toFixed(3)}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-light p-3 rounded">
                    <h6 class="text-warning mb-2"><i class="fas fa-chart-line me-2"></i>Standard Error</h6>
                    <p class="h4 mb-0">${finalSE.toFixed(3)}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-light p-3 rounded">
                    <h6 class="text-secondary mb-2"><i class="fas fa-list me-2"></i>Total Item</h6>
                    <p class="h4 mb-0">${totalItems}</p>
                </div>
            </div>
            <div class="col-12">
                <div class="bg-light p-3 rounded">
                    <h6 class="text-success mb-2"><i class="fas fa-check-circle me-2"></i>Statistik Jawaban</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <span class="badge bg-success fs-6">${correctAnswers}</span>
                            <p class="mb-0 small">Benar</p>
                        </div>
                        <div class="col-4">
                            <span class="badge bg-danger fs-6">${incorrectAnswers}</span>
                            <p class="mb-0 small">Salah</p>
                        </div>
                        <div class="col-4">
                            <span class="badge bg-primary fs-6">${accuracyRate}%</span>
                            <p class="mb-0 small">Akurasi</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="alert alert-info">
                    <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Informasi Penghentian</h6>
                    <p class="mb-1"><strong>Alasan:</strong> ${results.stopReason}</p>
                    <p class="mb-1"><strong>Computation:</strong> ${results.apiSource.toUpperCase()}</p>
                    <p class="mb-0"><strong>Method:</strong> MAP untuk estimasi real-time, EAP untuk skor akhir</p>
                </div>
            </div>
        </div>
    `;
    
    finalResultsCard.style.display = 'block';
    
    // Scroll to results
    finalResultsCard.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Show API source information
 */
function showApiSourceInfo(apiSource) {
    const statusElement = document.getElementById('status-text');
    const currentText = statusElement.textContent;
    
    const sourceInfo = apiSource === 'flask' ? 
        'menggunakan Flask API (Python calculation)' : 
        'menggunakan Laravel fallback (PHP calculation)';
        
    statusElement.innerHTML = `${currentText} - ${sourceInfo}`;
}

/**
 * Test Flask API endpoints (untuk debugging)
 */
async function testFlaskEndpoints() {
    console.log('Testing Flask API endpoints...');
    
    try {
        // Test estimate theta
        const testResponses = [
            { a: 1.5, b: -1.0, g: 0.2, answer: 1 },
            { a: 2.0, b: 0.5, g: 0.25, answer: 0 }
        ];
        
        const thetaResponse = await fetch(API_CONFIG.TEST_ESTIMATE_THETA, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ responses: testResponses })
        });
        
        const thetaData = await thetaResponse.json();
        console.log('Theta estimation test:', thetaData);
        
        // Test next item
        const itemResponse = await fetch(API_CONFIG.TEST_NEXT_ITEM, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                theta: 0.5,
                used_item_ids: [1, 2, 3],
                responses: testResponses
            })
        });
        
        const itemData = await itemResponse.json();
        console.log('Next item test:', itemData);
        
        // Test calculate score
        const scoreResponse = await fetch(API_CONFIG.TEST_CALCULATE_SCORE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ theta: 0.5 })
        });
        
        const scoreData = await scoreResponse.json();
        console.log('Score calculation test:', scoreData);
        
        // Test stopping criteria
        const stopResponse = await fetch(API_CONFIG.TEST_STOPPING_CRITERIA, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                responses: testResponses,
                se_eap: 0.8,
                used_item_ids: [1, 2]
            })
        });
        
        const stopData = await stopResponse.json();
        console.log('Stopping criteria test:', stopData);
        
    } catch (error) {
        console.error('Flask API test failed:', error);
    }
}

// Existing functions remain the same...
// (displayItem, updateProgress, updateStatus, showLoading, displayResponseResult, updateChart, etc.)

// Add to existing functions or create new ones as needed
function displayItem(item, theta, probability, information) {
    document.getElementById('item-display').style.display = 'block';
    document.getElementById('item-id').textContent = item.id;
    
    // Ensure parameters are numbers before calling toFixed
    const a = parseFloat(item.a) || 0;
    const b = parseFloat(item.b) || 0;
    const g = parseFloat(item.g) || 0;
    const thetaValue = parseFloat(theta) || 0;
    const probValue = parseFloat(probability) || 0;
    const infoValue = parseFloat(information) || 0;
    
    document.getElementById('param-a').textContent = a.toFixed(3);
    document.getElementById('param-b').textContent = b.toFixed(3);
    document.getElementById('param-g').textContent = g.toFixed(3);
    document.getElementById('current-theta').textContent = thetaValue.toFixed(3);
    document.getElementById('probability').textContent = (probValue * 100).toFixed(1) + '%';
    document.getElementById('information').textContent = infoValue.toFixed(3);
    
    // Update computation source
    const computationSource = document.getElementById('computation-source');
    if (computationSource) {
        computationSource.innerHTML = `<span class="badge ${testData.apiSource === 'flask' ? 'bg-success' : 'bg-warning'}">${testData.apiSource.toUpperCase()}</span>`;
    }
}

function updateProgress(current, total) {
    const percentage = (current / total) * 100;
    const progressBar = document.getElementById('progress-bar');
    progressBar.style.width = percentage + '%';
    progressBar.textContent = `${current}/${total}`;
}

function updateStatus(message, type) {
    const statusDisplay = document.getElementById('status-display');
    const statusText = document.getElementById('status-text');
    
    statusText.textContent = message;
    statusDisplay.className = `alert alert-${type}`;
}

function showLoading(show) {
    const loading = document.getElementById('loading');
    loading.style.display = show ? 'block' : 'none';
}

function displayResponseResult(answer, thetaBefore, thetaAfter, seBefore, seAfter, itemNumber) {
    const resultsDisplay = document.getElementById('results-display');
    const answerText = answer === 1 ? 'Benar' : 'Salah';
    const answerClass = answer === 1 ? 'success' : 'danger';
    
    // Ensure values are numbers
    const thetaBeforeNum = parseFloat(thetaBefore) || 0;
    const thetaAfterNum = parseFloat(thetaAfter) || 0;
    const seBeforeNum = parseFloat(seBefore) || 0;
    const seAfterNum = parseFloat(seAfter) || 0;
    
    const thetaChange = thetaAfterNum - thetaBeforeNum;
    const seChange = seAfterNum - seBeforeNum;
    
    const resultHTML = `
        <div class="border rounded p-3 mb-3 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Item ${itemNumber}</h6>
                <span class="badge bg-${answerClass}">${answerText}</span>
            </div>
            <div class="row">
                <div class="col-6">
                    <small class="text-muted">θ: ${thetaBeforeNum.toFixed(3)} → ${thetaAfterNum.toFixed(3)}</small>
                    <small class="text-info d-block">(Δ: ${thetaChange >= 0 ? '+' : ''}${thetaChange.toFixed(3)})</small>
                </div>
                <div class="col-6">
                    <small class="text-muted">SE: ${seBeforeNum.toFixed(3)} → ${seAfterNum.toFixed(3)}</small>
                    <small class="text-warning d-block">(Δ: ${seChange >= 0 ? '+' : ''}${seChange.toFixed(3)})</small>
                </div>
            </div>
        </div>
    `;
    
    resultsDisplay.innerHTML = resultHTML + resultsDisplay.innerHTML;
}

// Global chart variable
let chart = null;

function updateChart() {
    // Initialize chart if not exists
    if (!chart) {
        initChart();
    }
    
    // Update chart data
    if (chart) {
        chart.data.labels = testData.thetaHistory.map((_, index) => `Item ${index + 1}`);
        chart.data.datasets[0].data = testData.thetaHistory;
        chart.data.datasets[1].data = testData.seHistory;
        chart.update();
    }
}

function initChart() {
    // Destroy existing chart if it exists
    if (chart) {
        chart.destroy();
        chart = null;
    }
    
    // Hide placeholder and show canvas
    const placeholder = document.getElementById('chart-placeholder');
    const canvas = document.getElementById('progressChart');
    
    if (placeholder) placeholder.style.display = 'none';
    if (canvas) {
        canvas.style.display = 'block';
        
        const ctx = canvas.getContext('2d');
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Theta (θ)',
                        data: [],
                        borderColor: '#4facfe',
                        backgroundColor: 'rgba(79, 172, 254, 0.1)',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'SE_EAP',
                        data: [],
                        borderColor: '#f093fb',
                        backgroundColor: 'rgba(240, 147, 251, 0.1)',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Perkembangan Estimasi Kemampuan (Theta) dan SE EAP'
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Theta (θ)'
                        },
                        min: -3,
                        max: 3
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'SE EAP'
                        },
                        min: 0,
                        max: 2,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
}

function resetUI() {
    document.getElementById('item-display').style.display = 'none';
    document.getElementById('final-results-card').style.display = 'none';
    document.getElementById('progress-bar').style.width = '0%';
    document.getElementById('progress-bar').textContent = '0/30';
    document.getElementById('results-display').innerHTML = `
        <div class="text-center text-muted py-4">
            <i class="fas fa-chart-line fa-3x mb-3 opacity-50"></i>
            <p>Hasil akan ditampilkan setelah tes dimulai...</p>
        </div>
    `;
    
    // Reset chart
    if (chart) {
        chart.destroy();
        chart = null;
    }
    
    // Show placeholder
    const placeholder = document.getElementById('chart-placeholder');
    const canvas = document.getElementById('progressChart');
    
    if (placeholder) placeholder.style.display = 'block';
    if (canvas) canvas.style.display = 'none';
}

// Utility function to test connection
window.testFlaskConnection = testFlaskEndpoints;
