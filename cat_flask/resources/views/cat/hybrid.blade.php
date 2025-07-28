<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAT System - Hybrid (Flask + Laravel)</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --info-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .header-section {
            background: var(--primary-gradient);
            color: white;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
            position: relative;
            overflow: hidden;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23ffffff" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="%23ffffff" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="%23ffffff" opacity="0.1"/><circle cx="20" cy="80" r="0.5" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .header-section > * {
            position: relative;
            z-index: 1;
        }
        
        .card {
            box-shadow: var(--card-shadow);
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .card:hover {
            box-shadow: var(--hover-shadow);
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: none;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
            font-weight: 600;
            color: #495057;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        #item-display {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        #item-display:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
        }
        
        .btn-success {
            background: var(--success-gradient);
        }
        
        .btn-danger {
            background: var(--secondary-gradient);
        }
        
        .btn-success:hover, .btn-danger:hover, .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .progress {
            height: 15px;
            border-radius: 10px;
            background: #f8f9fa;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar {
            background: var(--success-gradient);
            transition: width 0.6s ease;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, .2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, .2) 50%,
                rgba(255, 255, 255, .2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 50px 50px;
            animation: move 2s linear infinite;
        }
        
        @keyframes move {
            0% {
                background-position: 0 0;
            }
            100% {
                background-position: 50px 50px;
            }
        }
        
        .alert {
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .alert-info {
            background: var(--info-gradient);
            color: white;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
            color: #2d3436;
        }
        
        .alert-success {
            background: var(--success-gradient);
            color: white;
        }
        
        .alert-danger {
            background: var(--secondary-gradient);
            color: white;
        }
        
        .badge {
            border-radius: 50px;
            padding: 8px 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }
        
        .table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: var(--card-shadow);
        }
        
        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .info-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border: 2px solid #e1bee7;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .algorithm-info {
            background: linear-gradient(135deg, #fff9c4 0%, #ffeaa7 100%);
            border: 2px solid #fdcb6e;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(102, 126, 234, 0); }
            100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
        }
        
        .api-status {
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .api-source-badge {
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="header-section">
            <div class="container">
                <div class="row align-items-center py-4">
                    <div class="col-md-8">
                        <h1 class="display-6 mb-2">
                            <i class="fas fa-brain me-3"></i>
                            CAT System - Hybrid Mode
                        </h1>
                        <p class="lead mb-0">
                            Computerized Adaptive Testing dengan Flask API Integration
                        </p>
                        <div class="api-status">
                            <span class="status-indicator bg-success"></span>
                            <span id="api-status" class="badge bg-secondary">Checking API Status...</span>
                            <span id="api-source" class="badge bg-info api-source-badge">UNKNOWN</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="info-card">
                            <h6 class="mb-2">
                                <i class="fas fa-cog me-2"></i>
                                Mode: Hybrid
                            </h6>
                            <p class="mb-0 small">
                                Primary: Flask API (Python)<br>
                                Fallback: Laravel (PHP)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="container">
            <div class="row">
                <!-- Left Column - Test Interface -->
                <div class="col-lg-8">
                    <!-- Status Display -->
                    <div id="status-display" class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="status-text">Sistem siap. Klik "Mulai Tes" untuk memulai.</span>
                    </div>
                    
                    <!-- Loading Indicator -->
                    <div id="loading" class="text-center" style="display: none;">
                        <div class="loading-spinner"></div>
                        <p>Memproses...</p>
                    </div>
                    
                    <!-- Test Control Panel -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-play-circle me-2"></i>
                                Kontrol Tes
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <button id="btn-start" class="btn btn-primary btn-lg me-3" onclick="startTest()">
                                <i class="fas fa-play me-2"></i>
                                Mulai Tes
                            </button>
                            <button id="btn-correct" class="btn btn-success btn-lg me-3" onclick="submitResponse(1)" disabled>
                                <i class="fas fa-check me-2"></i>
                                Benar
                            </button>
                            <button id="btn-incorrect" class="btn btn-danger btn-lg" onclick="submitResponse(0)" disabled>
                                <i class="fas fa-times me-2"></i>
                                Salah
                            </button>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>
                                Progress Tes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="progress">
                                <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%">
                                    0/30
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Item Display -->
                    <div id="item-display" class="card mb-4" style="display: none;">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-question-circle me-2"></i>
                                Item Soal
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th>ID Item</th>
                                                <td id="item-id">-</td>
                                            </tr>
                                            <tr>
                                                <th>Parameter a</th>
                                                <td id="param-a">-</td>
                                            </tr>
                                            <tr>
                                                <th>Parameter b</th>
                                                <td id="param-b">-</td>
                                            </tr>
                                            <tr>
                                                <th>Parameter g</th>
                                                <td id="param-g">-</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th>Theta (θ)</th>
                                                <td id="current-theta">-</td>
                                            </tr>
                                            <tr>
                                                <th>Probability</th>
                                                <td id="probability">-</td>
                                            </tr>
                                            <tr>
                                                <th>Information</th>
                                                <td id="information">-</td>
                                            </tr>
                                            <tr>
                                                <th>Computation</th>
                                                <td id="computation-source">-</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Final Results -->
                    <div id="final-results-card" class="card mb-4" style="display: none;">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-trophy me-2"></i>
                                Hasil Akhir
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="final-results-summary">
                                <!-- Results will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - History & Chart -->
                <div class="col-lg-4">
                    <!-- Response History -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Riwayat Respon
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="results-display" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-chart-line fa-3x mb-3 opacity-50"></i>
                                    <p>Hasil akan ditampilkan setelah tes dimulai...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-area me-2"></i>
                                Grafik Perkembangan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="progressChart" style="display: none;"></canvas>
                                <div id="chart-placeholder" class="text-center text-muted py-4">
                                    <i class="fas fa-chart-line fa-3x mb-3 opacity-50"></i>
                                    <p>Grafik akan muncul setelah tes dimulai...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/cat-hybrid.js') }}"></script>
</body>
</html>
