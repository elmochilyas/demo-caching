<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caching Demo - Learn Why Caching Matters</title>
    <link rel="stylesheet" href="{{ asset('css/demo.css') }}">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Caching Demo</h1>
            <p>Learn Why Caching Matters - UV Index API</p>
        </header>

        <div class="demo-grid">
            <div class="column uncached-column">
                <div class="column-header">
                    <h2>WITHOUT CACHING</h2>
                    <span class="badge badge-slow">Slow</span>
                </div>

                <div class="demo-section">
                    <h3>Database Demo</h3>
                    <div class="stats" id="uncached-photos-stats">
                        <div class="response-time" id="uncached-photos-time">Response: --</div>
                        <div class="cache-status" id="uncached-photos-cache">Cache: N/A</div>
                    </div>
                    <button class="btn btn-danger" onclick="loadPhotos()">Load Photos</button>
                    <div class="photos-grid" id="uncached-photos-grid"></div>
                    <div class="request-counter">Requests: <span id="uncached-photos-requests">0</span></div>
                </div>

                <div class="demo-section">
                    <h3>UV Index API Demo</h3>
                    <div class="lat-lng-inputs">
                        <input type="text" id="uncached-lat" class="lat-lng-input" placeholder="Lat" value="51.5">
                        <input type="text" id="uncached-lng" class="lat-lng-input" placeholder="Lng" value="-0.11">
                    </div>
                    <div class="stats" id="uncached-weather-stats">
                        <div class="response-time" id="uncached-weather-time">Response: --</div>
                        <div class="cache-status">Cache: N/A</div>
                    </div>
                    <button class="btn btn-danger" onclick="loadUV()">Load UV Index</button>
                    <div class="weather-card" id="uncached-weather-card">
                        <div class="weather-data"></div>
                    </div>
                    <div class="api-calls-counter">
                        API Calls Made: <span id="uncached-api-calls">0</span> | External API Calls: <span id="uncached-external-calls">0</span>
                    </div>
                </div>
            </div>

            <div class="column cached-column">
                <div class="column-header">
                    <h2>WITH CACHING</h2>
                    <span class="badge badge-fast">Fast</span>
                </div>

                <div class="demo-section">
                    <h3>Database Demo</h3>
                    <div class="stats" id="cached-photos-stats">
                        <div class="response-time response-time-fast" id="cached-photos-time">Response: --</div>
                        <div class="cache-status" id="cached-photos-cache">Cache: N/A</div>
                    </div>
                    <button class="btn btn-success" onclick="loadPhotos()">Load Photos</button>
                    <div class="photos-grid" id="cached-photos-grid"></div>
                    <div class="request-counter">Requests: <span id="cached-photos-requests">0</span></div>
                </div>

                <div class="demo-section">
                    <h3>UV Index API Demo</h3>
                    <div class="lat-lng-inputs">
                        <input type="text" id="cached-lat" class="lat-lng-input" placeholder="Lat" value="51.5">
                        <input type="text" id="cached-lng" class="lat-lng-input" placeholder="Lng" value="-0.11">
                    </div>
                    <div class="stats" id="cached-weather-stats">
                        <div class="response-time response-time-fast" id="cached-weather-time">Response: --</div>
                        <div class="cache-status" id="cached-weather-cache">Cache: N/A</div>
                    </div>
                    <button class="btn btn-success" onclick="loadUV()">Load UV Index</button>
                    <div class="weather-card" id="cached-weather-card">
                        <div class="weather-data"></div>
                    </div>
                    <div class="api-calls-counter">
                        API Calls Made: <span id="cached-api-calls">0</span> | External API Calls: <span id="cached-external-calls">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="global-controls">
            <button class="btn btn-warning" onclick="clearCache()">Clear Cache</button>
            <button class="btn btn-secondary" onclick="resetSession()">Reset Session</button>
        </div>
    </div>

    <script src="{{ asset('js/demo.js') }}"></script>
</body>
</html>