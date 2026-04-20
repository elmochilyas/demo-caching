<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caching Demo - Learn Why Caching Matters</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/demo.css')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <div class="header-badge">
                    <i class="fas fa-bolt"></i>
                    Interactive Demo
                </div>
                <h1>Caching Demo</h1>
                <p>Learn the performance difference between queries with and without caching</p>
            </div>
        </header>

        <div class="intro-cards">
            <div class="intro-card slow-card">
                <div class="intro-icon">
                    <i class="fas fa-spinner"></i>
                </div>
                <h3>Without Caching</h3>
                <p>Every request hits the database directly. Slow and resource-intensive.</p>
            </div>
            <div class="intro-card fast-card">
                <div class="intro-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3>With Caching</h3>
                <p>Data is stored temporarily. First request caches it, subsequent requests are instant.</p>
            </div>
        </div>

        <section class="test-section">
            <div class="section-header">
                <h2><i class="fas fa-database"></i> Database Query Test</h2>
                <span class="section-subtitle">Compare query performance</span>
            </div>
            <button class="btn btn-primary" onclick="runDbCompare()">
                <i class="fas fa-play"></i> Run Test
            </button>
        </section>

        <div class="results-grid">
            <div class="result-card slow-card">
                <div class="card-badge">
                    <i class="fas fa-times-circle"></i> Without Caching
                </div>
                <div class="result-stats">
                    <div class="stat">
                        <span class="stat-label">Response Time</span>
                        <span class="stat-value slow" id="uncached-photos-time">--</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Total Requests</span>
                        <span class="stat-value" id="uncached-photos-requests">0</span>
                    </div>
                </div>
                <div class="photos-grid" id="uncached-photos-grid">
                    <div class="placeholder"><i class="fas fa-images"></i></div>
                </div>
            </div>

            <div class="result-card fast-card">
                <div class="card-badge">
                    <i class="fas fa-check-circle"></i> With Caching
                </div>
                <div class="result-stats">
                    <div class="stat">
                        <span class="stat-label">Response Time</span>
                        <span class="stat-value fast" id="cached-photos-time">--</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Total Requests</span>
                        <span class="stat-value" id="cached-photos-requests">0</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Cache Status</span>
                        <span class="stat-badge" id="cached-photos-cache">--</span>
                    </div>
                </div>
                <div class="photos-grid" id="cached-photos-grid">
                    <div class="placeholder"><i class="fas fa-images"></i></div>
                </div>
            </div>
        </div>

        <section class="test-section">
            <div class="section-header">
                <h2><i class="fas fa-cloud-sun"></i> External API Test</h2>
                <span class="section-subtitle">Compare external API calls</span>
            </div>
            <div class="input-row">
                <input type="text" id="api-lat" class="input" placeholder="Latitude" value="51.5">
                <input type="text" id="api-lng" class="input" placeholder="Longitude" value="-0.11">
            </div>
            <button class="btn btn-primary" onclick="runApiCompare()">
                <i class="fas fa-play"></i> Run Test
            </button>
        </section>

        <div class="results-grid">
            <div class="result-card slow-card">
                <div class="card-badge">
                    <i class="fas fa-times-circle"></i> Without Caching
                </div>
                <div class="result-stats">
                    <div class="stat">
                        <span class="stat-label">Response Time</span>
                        <span class="stat-value slow" id="uncached-weather-time">--</span>
                    </div>
                </div>
                <div class="weather-display" id="uncached-weather-card">
                    <div class="placeholder">Run test to see results</div>
                </div>
                <div class="counter">
                    <span><i class="fas fa-exchange-alt"></i> API Calls: <b id="uncached-api-calls">0</b></span>
                    <span><i class="fas fa-globe"></i> External: <b id="uncached-external-calls">0</b></span>
                </div>
            </div>

            <div class="result-card fast-card">
                <div class="card-badge">
                    <i class="fas fa-check-circle"></i> With Caching
                </div>
                <div class="result-stats">
                    <div class="stat">
                        <span class="stat-label">Response Time</span>
                        <span class="stat-value fast" id="cached-weather-time">--</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Cache Status</span>
                        <span class="stat-badge" id="cached-weather-cache">--</span>
                    </div>
                </div>
                <div class="weather-display" id="cached-weather-card">
                    <div class="placeholder">Run test to see results</div>
                </div>
                <div class="counter">
                    <span><i class="fas fa-exchange-alt"></i> API Calls: <b id="cached-api-calls">0</b></span>
                    <span><i class="fas fa-globe"></i> External: <b id="cached-external-calls">0</b></span>
                </div>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-warning" onclick="clearCache()">
                <i class="fas fa-trash-alt"></i> Clear Cache & Reset
            </button>
            <button class="btn btn-secondary" onclick="location.reload()">
                <i class="fas fa-redo"></i> Refresh
            </button>
        </div>
    </div>

    <script src="<?php echo e(asset('js/demo.js')); ?>"></script>
</body>
</html><?php /**PATH C:\xampp\htdocs\caching\resources\views/demo.blade.php ENDPATH**/ ?>