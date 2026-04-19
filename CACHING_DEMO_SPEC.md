# Caching Demo Project Specification

## Project Overview
Build an interactive Laravel/MySQL web application that demonstrates the importance of caching through two parallel flows: one uncached (slow) and one cached (fast). Users will see real-time performance differences in response times, request counts, and cache hit/miss behavior.

## Tech Stack
- **Backend**: Laravel 11+ (PHP 8.1+)
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (vanilla or Vue.js optional)
- **Caching**: Laravel Cache Facade (File or Redis backend)
- **API**: External weather API (OpenWeatherMap or similar free tier)

---

## Part 1: Database Caching Demo

### 1.1 Database Structure

#### Migration: `photos` table
```
Schema:
- id (primary key, auto-increment)
- title (string, max 255)
- url (string, URL to image)
- description (text, nullable)
- metadata (JSON or text, simulated metadata)
- created_at (timestamp)
- updated_at (timestamp)

Seed: 200-500 photo records with realistic data
```

#### Migration: `cache_stats` table (for logging)
```
Schema:
- id (primary key, auto-increment)
- endpoint (string: 'photos-uncached' or 'photos-cached')
- cache_status (string: 'HIT', 'MISS', 'N/A')
- response_time_ms (integer, milliseconds)
- user_session_id (string)
- created_at (timestamp)
- updated_at (timestamp)

Purpose: Track every request's cache behavior and timing
```

### 1.2 API Endpoints for Photos

#### Endpoint 1: `/api/photos-uncached`
**Method**: GET  
**Purpose**: Retrieve all photos WITHOUT caching; simulate slow query  
**Response**:
```json
{
  "status": "success",
  "cache_hit": false,
  "response_time_ms": 1250,
  "data": [
    {
      "id": 1,
      "title": "Photo 1",
      "url": "https://...",
      "description": "...",
      "metadata": {...}
    },
    ...
  ]
}
```

**Implementation Details**:
- Query: `Photo::all()` (or with pagination if needed)
- Add artificial delay: `usleep(1000000)` (1 second) to simulate slow DB query
- Measure query time with `microtime(true)` before and after DB query
- Log to `cache_stats` table with `cache_status = 'N/A'`
- Always return fresh data, never check cache

#### Endpoint 2: `/api/photos-cached`
**Method**: GET  
**Purpose**: Retrieve all photos WITH caching; show cache hits/misses  
**Response**:
```json
{
  "status": "success",
  "cache_hit": true,
  "response_time_ms": 45,
  "data": [
    {
      "id": 1,
      "title": "Photo 1",
      "url": "https://...",
      "description": "...",
      "metadata": {...}
    },
    ...
  ]
}
```

**Implementation Details**:
- Use Laravel's `Cache::remember('photos-all', 600, function() { ... })`
- Cache TTL: 10 minutes (600 seconds)
- On cache hit: return data with `response_time_ms` = time to retrieve from cache (typically < 100ms)
- On cache miss: query DB (with same 1-second artificial delay), cache result, return data
- Measure total response time (cache fetch + optional DB query)
- Log to `cache_stats` table with `cache_status = 'HIT'` or `'MISS'`
- Include `cache_hit` boolean in response so frontend can display visually

---

## Part 2: API Rate-Limiting Caching Demo

### 2.1 Database Structure

#### Migration: `api_requests` table
```
Schema:
- id (primary key, auto-increment)
- user_session_id (string, from session)
- endpoint (string: 'weather-uncached' or 'weather-cached')
- api_call_count (integer, how many times the external API was called)
- request_count (integer, how many times the user made a request)
- response_time_ms (integer)
- created_at (timestamp)
- updated_at (timestamp)

Purpose: Track user requests vs. actual API calls (demonstrate API call reduction through caching)
```

### 2.2 External API Integration

#### API Choice: OpenWeatherMap (Free Tier)
- **API Endpoint**: `https://api.openweathermap.org/data/2.5/weather?q={city}&appid={api_key}`
- **Free Key**: Sign up at https://openweathermap.org/api (free tier includes current weather)
- **Rate Limit**: 60 requests/minute (uncached version will hit this quickly; cached version won't)
- **Response**: JSON with temp, humidity, weather description, etc.

### 2.3 API Endpoints for Weather

#### Endpoint 1: `/api/weather-uncached`
**Method**: GET  
**Query Parameters**: `city` (string, e.g., "London")  
**Purpose**: Fetch weather WITHOUT caching; count every external API call  
**Response**:
```json
{
  "status": "success",
  "cache_hit": false,
  "external_api_calls": 1,
  "user_total_requests": 5,
  "response_time_ms": 850,
  "data": {
    "city": "London",
    "temp": 15.5,
    "humidity": 65,
    "description": "Cloudy"
  }
}
```

**Implementation Details**:
- Retrieve `user_session_id` from session (or generate if missing)
- Increment user request counter in `api_requests` table
- Call external API using `Http::get()` (Guzzle)
- Increment API call counter in `api_requests` table
- Measure HTTP request time with `microtime(true)`
- Log response with `cache_status = 'N/A'` (no caching)
- Return `external_api_calls` (always = 1) and `user_total_requests` (cumulative)

#### Endpoint 2: `/api/weather-cached`
**Method**: GET  
**Query Parameters**: `city` (string, e.g., "London")  
**Purpose**: Fetch weather WITH caching; show API call reduction  
**Response**:
```json
{
  "status": "success",
  "cache_hit": true,
  "external_api_calls": 0,
  "user_total_requests": 5,
  "response_time_ms": 12,
  "data": {
    "city": "London",
    "temp": 15.5,
    "humidity": 65,
    "description": "Cloudy"
  }
}
```

**Implementation Details**:
- Retrieve `user_session_id` from session (or generate if missing)
- Increment user request counter in `api_requests` table
- Use Laravel's `Cache::remember("weather-{$city}", 600, function() { ... })`
- Cache TTL: 10 minutes (600 seconds) — same as photos
- On cache hit:
  - Return cached data
  - Set `external_api_calls = 0` (no external call made)
  - Set `cache_hit = true`
- On cache miss:
  - Call external API using `Http::get()`
  - Increment API call counter to 1
  - Cache result for 10 minutes
  - Set `cache_hit = false`
- Always return `user_total_requests` (cumulative per session)
- Log response with `cache_status = 'HIT'` or `'MISS'`

---

## Part 3: Frontend Demo Page

### 3.1 Layout

**Two-column layout** (side-by-side, responsive):

```
┌─────────────────────────────────────────────────────┐
│                    Caching Demo                      │
└─────────────────────────────────────────────────────┘
┌──────────────────────┬──────────────────────────────┐
│   WITHOUT CACHING    │      WITH CACHING            │
│   (Slow)             │      (Fast)                   │
├──────────────────────┼──────────────────────────────┤
│                      │                              │
│  📊 Database Demo    │  📊 Database Demo            │
│  ────────────────    │  ────────────────            │
│  ⏱ Response: 1250ms  │  ⏱ Response: 45ms           │
│  🔴 Cache: MISS      │  🟢 Cache: HIT              │
│  [Load Photos]       │  [Load Photos]               │
│                      │                              │
│  📊 API Demo         │  📊 API Demo                 │
│  ────────────────    │  ────────────────            │
│  🌍 City: London     │  🌍 City: London             │
│  ⏱ Response: 850ms   │  ⏱ Response: 12ms            │
│  📞 API Calls: 5/5   │  📞 API Calls: 5/1           │
│  [Load Weather]      │  [Load Weather]              │
│                      │                              │
│  [Clear Cache]       │  [Clear Cache]               │
│                      │                              │
└──────────────────────┴──────────────────────────────┘
```

### 3.2 Sections & Features

#### Section 1: Database Photo Demo
**Left Column (Uncached)**:
- Button: "Load Photos" — calls `/api/photos-uncached`
- Display:
  - Response time in milliseconds (large, red/warning color)
  - Cache status badge: "MISS" (red) or "N/A" (gray)
  - Grid of 5-10 photos (title, image, description)
  - Request counter: "Requests made: X"

**Right Column (Cached)**:
- Button: "Load Photos" — calls `/api/photos-cached`
- Display:
  - Response time in milliseconds (large, green/success color)
  - Cache status badge: "HIT" (green) or "MISS" (yellow)
  - Grid of 5-10 photos (same layout as uncached)
  - Request counter: "Requests made: X"

**Interaction**:
- Load both endpoints simultaneously (Promise.all)
- Show side-by-side timing comparison
- First click: both show MISS (or N/A), timing shows difference
- Subsequent clicks (within 10 minutes): cached shows HIT with much faster response

#### Section 2: Weather API Demo
**Left Column (Uncached)**:
- Input field: "Enter city name" (default: "London")
- Button: "Load Weather" — calls `/api/weather-uncached?city={input}`
- Display:
  - Response time in milliseconds
  - Cache status: "N/A" (no caching)
  - Weather data: city, temperature, humidity, description
  - **Key metric**: "API Calls Made: 5 | External API Calls: 5"
    - Shows every user request = every external API call
  - Encourage user to click button 5-10 times to see it increment

**Right Column (Cached)**:
- Same input field and button for `/api/weather-cached?city={input}`
- Display:
  - Response time in milliseconds
  - Cache status: "HIT" (green) or "MISS" (yellow)
  - Weather data: same structure
  - **Key metric**: "API Calls Made: 5 | External API Calls: 1"
    - Shows user requests ≠ external API calls due to caching
  - Demonstrates that multiple user requests = 1 external API call

**Interaction**:
- Load both endpoints simultaneously
- First 3 clicks show response time difference
- Clicks 4+ on cached version show "HIT" and instant response
- The API call count divergence becomes obvious after 3-5 clicks

#### Section 3: Global Controls
- **Clear Cache Button**: Clears all Laravel cache (photos + weather)
  - Calls `/api/clear-cache` (POST)
  - Resets cache to MISS state so user can see cache behavior again
- **Request Counter Reset**: Optional, to reset user session request counts

### 3.3 Frontend Code Structure

#### HTML
```
- Header: "Caching Demo - Learn Why Caching Matters"
- Two main containers: #uncached-column, #cached-column
- Sections within each:
  - Photos demo (button, stats, image grid)
  - Weather demo (input, button, stats, weather card)
  - Both columns have identical layouts, different API endpoints
- Global controls at bottom: Clear Cache, Reset Session
```

#### CSS
- Responsive grid (2 columns on desktop, 1 on mobile)
- Color coding:
  - Uncached: Red/Orange (#FF6B6B or similar)
  - Cached: Green (#51CF66 or similar)
  - Hit badge: Green
  - Miss badge: Yellow (#FFD93D)
- Large, readable fonts for timing (18-24px)
- Cards with shadow for photos/weather data
- Smooth transitions on button clicks

#### JavaScript
```javascript
// Utility: Measure request time with microtime precision
async function fetchWithTiming(url) {
  const startTime = performance.now();
  const response = await fetch(url);
  const endTime = performance.now();
  const data = await response.json();
  return {
    ...data,
    client_response_time_ms: Math.round(endTime - startTime)
  };
}

// Photos Demo
async function loadPhotos() {
  // Call both endpoints simultaneously
  const [uncached, cached] = await Promise.all([
    fetchWithTiming('/api/photos-uncached'),
    fetchWithTiming('/api/photos-cached')
  ]);
  
  // Update DOM with results
  updatePhotosDisplay(uncached, cached);
}

// Weather Demo
async function loadWeather(city) {
  const [uncached, cached] = await Promise.all([
    fetchWithTiming(`/api/weather-uncached?city=${city}`),
    fetchWithTiming(`/api/weather-cached?city=${city}`)
  ]);
  
  // Update DOM with results
  updateWeatherDisplay(uncached, cached);
}

// Clear Cache
async function clearCache() {
  await fetch('/api/clear-cache', { method: 'POST' });
  alert('Cache cleared. Reload the page.');
}
```

---

## Part 4: Laravel Implementation Details

### 4.1 Project Structure
```
laravel-caching-demo/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── CachingDemoController.php
│   │   ├── Middleware/
│   │   │   └── TrackSession.php (middleware to ensure session ID)
│   ├── Models/
│   │   ├── Photo.php
│   │   ├── CacheStat.php
│   │   └── ApiRequest.php
├── database/
│   ├── migrations/
│   │   ├── create_photos_table.php
│   │   ├── create_cache_stats_table.php
│   │   └── create_api_requests_table.php
│   ├── seeders/
│   │   ├── PhotoSeeder.php
│   ├── factories/
│   │   └── PhotoFactory.php
├── routes/
│   ├── api.php (all API endpoints)
│   ├── web.php (demo page route)
├── resources/
│   ├── views/
│   │   └── demo.blade.php
│   ├── css/
│   │   └── demo.css
│   ├── js/
│   │   └── demo.js
├── config/
│   └── cache.php (ensure cache driver is configured)
└── .env (include OPENWEATHERMAP_API_KEY)
```

### 4.2 Controller Methods

#### CachingDemoController.php

```php
class CachingDemoController extends Controller
{
    // Database Caching Demo - Uncached
    public function photosUncached(Request $request)
    {
        $startTime = microtime(true);
        
        // Simulate slow query with artificial delay
        usleep(1000000); // 1 second
        
        $photos = Photo::all()->take(10);
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        // Log to cache_stats
        CacheStat::create([
            'endpoint' => 'photos-uncached',
            'cache_status' => 'N/A',
            'response_time_ms' => $responseTime,
            'user_session_id' => session()->getId(),
        ]);
        
        return response()->json([
            'status' => 'success',
            'cache_hit' => false,
            'response_time_ms' => $responseTime,
            'data' => $photos,
        ]);
    }

    // Database Caching Demo - Cached
    public function photosCached(Request $request)
    {
        $startTime = microtime(true);
        $cacheKey = 'photos-all';
        $cacheHit = false;
        
        if (Cache::has($cacheKey)) {
            $cacheHit = true;
            $photos = Cache::get($cacheKey);
        } else {
            // Simulate slow query with artificial delay
            usleep(1000000); // 1 second
            $photos = Photo::all()->take(10);
            Cache::put($cacheKey, $photos, 600); // 10 minutes
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        // Log to cache_stats
        CacheStat::create([
            'endpoint' => 'photos-cached',
            'cache_status' => $cacheHit ? 'HIT' : 'MISS',
            'response_time_ms' => $responseTime,
            'user_session_id' => session()->getId(),
        ]);
        
        return response()->json([
            'status' => 'success',
            'cache_hit' => $cacheHit,
            'response_time_ms' => $responseTime,
            'data' => $photos,
        ]);
    }

    // Weather API Demo - Uncached
    public function weatherUncached(Request $request)
    {
        $city = $request->query('city', 'London');
        $startTime = microtime(true);
        $sessionId = session()->getId();
        
        // Call external API
        try {
            $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
                'q' => $city,
                'appid' => config('services.openweathermap.key'),
                'units' => 'metric',
            ]);
            
            $weatherData = $response->json();
        } catch (\Exception $e) {
            return response()->json(['error' => 'API call failed'], 500);
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        // Track request
        $record = ApiRequest::firstOrCreate(
            ['user_session_id' => $sessionId, 'endpoint' => 'weather-uncached'],
            ['request_count' => 0, 'api_call_count' => 0]
        );
        $record->increment('request_count');
        $record->increment('api_call_count');
        
        return response()->json([
            'status' => 'success',
            'cache_hit' => false,
            'external_api_calls' => $record->api_call_count,
            'user_total_requests' => $record->request_count,
            'response_time_ms' => $responseTime,
            'data' => [
                'city' => $weatherData['name'] ?? $city,
                'temp' => $weatherData['main']['temp'] ?? null,
                'humidity' => $weatherData['main']['humidity'] ?? null,
                'description' => $weatherData['weather'][0]['description'] ?? null,
            ],
        ]);
    }

    // Weather API Demo - Cached
    public function weatherCached(Request $request)
    {
        $city = $request->query('city', 'London');
        $startTime = microtime(true);
        $sessionId = session()->getId();
        $cacheKey = "weather-{$city}";
        $cacheHit = false;
        $apiCallsIncrement = 0;
        
        if (Cache::has($cacheKey)) {
            $cacheHit = true;
            $weatherData = Cache::get($cacheKey);
        } else {
            // Call external API
            try {
                $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
                    'q' => $city,
                    'appid' => config('services.openweathermap.key'),
                    'units' => 'metric',
                ]);
                
                $weatherData = $response->json();
                Cache::put($cacheKey, $weatherData, 600); // 10 minutes
                $apiCallsIncrement = 1;
            } catch (\Exception $e) {
                return response()->json(['error' => 'API call failed'], 500);
            }
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        // Track request
        $record = ApiRequest::firstOrCreate(
            ['user_session_id' => $sessionId, 'endpoint' => 'weather-cached'],
            ['request_count' => 0, 'api_call_count' => 0]
        );
        $record->increment('request_count');
        if ($apiCallsIncrement > 0) {
            $record->increment('api_call_count');
        }
        
        return response()->json([
            'status' => 'success',
            'cache_hit' => $cacheHit,
            'external_api_calls' => $apiCallsIncrement,
            'user_total_requests' => $record->request_count,
            'response_time_ms' => $responseTime,
            'data' => [
                'city' => $weatherData['name'] ?? $city,
                'temp' => $weatherData['main']['temp'] ?? null,
                'humidity' => $weatherData['main']['humidity'] ?? null,
                'description' => $weatherData['weather'][0]['description'] ?? null,
            ],
        ]);
    }

    // Clear Cache
    public function clearCache(Request $request)
    {
        Cache::forget('photos-all');
        // Also clear all weather caches (iterate through common cities)
        foreach (['London', 'Paris', 'Tokyo', 'New York', 'Sydney'] as $city) {
            Cache::forget("weather-{$city}");
        }
        
        return response()->json(['status' => 'success', 'message' => 'Cache cleared']);
    }

    // Demo Page View
    public function index()
    {
        return view('demo');
    }
}
```

### 4.3 Routes (routes/api.php)

```php
Route::get('/photos-uncached', [CachingDemoController::class, 'photosUncached']);
Route::get('/photos-cached', [CachingDemoController::class, 'photosCached']);
Route::get('/weather-uncached', [CachingDemoController::class, 'weatherUncached']);
Route::get('/weather-cached', [CachingDemoController::class, 'weatherCached']);
Route::post('/clear-cache', [CachingDemoController::class, 'clearCache']);

// Web route for demo page
Route::get('/', [CachingDemoController::class, 'index'])->name('demo');
```

### 4.4 Environment Configuration (.env)

```
CACHE_DRIVER=file
# or: CACHE_DRIVER=redis (if using Redis)

OPENWEATHERMAP_API_KEY=your_free_api_key_here
```

### 4.5 Config Setup (config/services.php)

```php
'openweathermap' => [
    'key' => env('OPENWEATHERMAP_API_KEY'),
],
```

---

## Part 5: Key Metrics & User Experience

### 5.1 Expected Performance Differences

**Database Demo** (Photo Loading):
- Uncached: ~1200-1300ms (1s artificial delay + network)
- Cached (first call): ~1200-1300ms (MISS, same as uncached)
- Cached (subsequent calls): ~40-80ms (HIT, retrieves from cache)
- **Visual impact**: Green badge + 15-20x faster response

**API Demo** (Weather Loading):
- Uncached: ~700-900ms per request (external API call)
- Cached (first call): ~700-900ms (MISS, external API call)
- Cached (subsequent calls): ~10-30ms (HIT, cache retrieval)
- **API call reduction**: After 5 user requests:
  - Uncached: 5 requests to external API
  - Cached: 1 request to external API
- **Visual impact**: "API Calls Made: 5 | External API Calls: 1"

### 5.2 User Workflow (Demo Script)

1. **Page Load**: Both columns show "Load Photos" and "Load Weather" buttons
2. **Click "Load Photos"**: 
   - Uncached side takes ~1.2s to respond (HIT badge in red)
   - Cached side takes ~1.2s to respond (MISS badge in yellow)
   - Photos display identically
   - Point out: "Both slow on first load"
3. **Click "Load Photos" again** (within 10 minutes):
   - Uncached side takes ~1.2s again (still MISS)
   - Cached side takes ~0.05s (HIT badge in green)
   - **"See the difference? Cached is 20x faster!"**
4. **Load Weather (default city: London)**:
   - Uncached: ~800ms, API Calls Made: 1 | External: 1
   - Cached: ~800ms, API Calls Made: 1 | External: 1 (both miss on first call)
5. **Click "Load Weather" 4 more times** (5 clicks total):
   - Uncached: ~800ms each time, External API calls: 5 total
   - Cached: Gets faster each time, hits red after 2nd click, External API calls: stays at 1
   - **"Your app made 5 requests, but the external API only got 1 call!"**
6. **Change city** (e.g., "Paris"):
   - New cache key, so both miss initially
   - Next calls to Paris show cache hits for cached version
   - Uncached always misses
7. **Click "Clear Cache"**:
   - All cache resets to MISS state
   - Cached version starts fresh
   - User can repeat the demo

---

## Part 6: Implementation Phases for AI Coding Agent

### Phase 1: Database Setup & Seeding
**Prompt to AI Agent**:
```
Create the Laravel migrations, models, and seeders for the caching demo:

1. Photo Migration: id, title, url, description, metadata (JSON), timestamps
2. CacheStat Migration: id, endpoint, cache_status, response_time_ms, user_session_id, timestamps
3. ApiRequest Migration: id, user_session_id, endpoint, request_count, api_call_count, timestamps
4. PhotoFactory: Generate 300 realistic photo records (titles, descriptions, fake image URLs)
5. PhotoSeeder: Seed 300 photos using PhotoFactory
6. Models: Photo, CacheStat, ApiRequest with proper relationships

Include: php artisan migrate:fresh --seed command documentation.
```

### Phase 2: Photo Caching Endpoints
**Prompt to AI Agent**:
```
Create two API endpoints in CachingDemoController:

1. GET /api/photos-uncached:
   - Query Photo::all() with 1-second artificial delay
   - Measure response time in milliseconds
   - Log to cache_stats table with cache_status='N/A'
   - Return JSON with: status, cache_hit=false, response_time_ms, data=[photos]

2. GET /api/photos-cached:
   - Use Laravel Cache::remember('photos-all', 600, fn => Photo::all())
   - Log to cache_stats with cache_status='HIT' or 'MISS'
   - Return JSON with: status, cache_hit=true/false, response_time_ms, data=[photos]

Both endpoints must measure timing with microtime(true) and include session tracking.
```

### Phase 3: Weather API Endpoints
**Prompt to AI Agent**:
```
Create two API endpoints for weather caching:

1. GET /api/weather-uncached?city={city}:
   - Query external API: https://api.openweathermap.org/data/2.5/weather
   - Parameters: q={city}, appid={OPENWEATHERMAP_API_KEY}, units=metric
   - Track in api_requests table: increment request_count and api_call_count
   - Return JSON with: status, cache_hit=false, external_api_calls, user_total_requests, response_time_ms, data={weather}

2. GET /api/weather-cached?city={city}:
   - Use Cache::remember("weather-{city}", 600, fn => Api call)
   - Track in api_requests: increment request_count, increment api_call_count only on miss
   - Return external_api_calls=1 on miss, 0 on hit
   - Return JSON with: status, cache_hit, external_api_calls, user_total_requests, response_time_ms, data={weather}

Use Http::get() (Guzzle) for API calls. Handle errors gracefully.
```

### Phase 4: Clear Cache Endpoint
**Prompt to AI Agent**:
```
Create a POST endpoint /api/clear-cache that:
- Forgets cache key 'photos-all'
- Forgets all weather cache keys: weather-London, weather-Paris, weather-Tokyo, etc.
- Returns JSON: { status: 'success', message: 'Cache cleared' }
```

### Phase 5: Frontend & Demo Page
**Prompt to AI Agent**:
```
Create a Blade template (demo.blade.php) and JavaScript (demo.js) with:

1. Two-column responsive layout (left=uncached, right=cached)
2. Photo Demo Section:
   - Button: "Load Photos" (calls both endpoints simultaneously)
   - Display: response_time_ms (large font), cache_hit badge (green/yellow/red), 5-10 photos in grid
3. Weather Demo Section:
   - Input: City name (default: London)
   - Button: "Load Weather"
   - Display: response_time_ms, cache_hit badge, weather card (city, temp, humidity, description)
   - KEY METRIC: "API Calls Made: X | External API Calls: Y"
4. Global Controls:
   - Clear Cache button
   - Session reset option

JavaScript:
- fetchWithTiming() utility to measure client-side request time
- loadPhotos() function using Promise.all() for both endpoints
- loadWeather() function with city parameter
- clearCache() function with POST request
- DOM update functions for displaying results

CSS:
- Red/Orange for uncached, Green for cached
- Cards, shadows, responsive grid
- Large, readable fonts for timing display
```

### Phase 6: Configuration & Testing
**Prompt to AI Agent**:
```
1. Set up config/services.php with openweathermap key
2. Update .env template with OPENWEATHERMAP_API_KEY and CACHE_DRIVER=file
3. Add routes in routes/api.php and routes/web.php
4. Create middleware (if needed) to ensure session tracking
5. Generate fresh database: php artisan migrate:fresh --seed
6. Test all endpoints with curl or Postman
7. Document API responses and expected behavior
```

---

## Part 7: Testing Checklist

- [ ] Seed database with 300 photos successfully
- [ ] `/api/photos-uncached` returns ~1200ms response (every time)
- [ ] `/api/photos-cached` first call returns ~1200ms (MISS), second returns ~50ms (HIT)
- [ ] Cache stats logged correctly to database
- [ ] `/api/weather-uncached?city=London` calls external API, logs request count
- [ ] `/api/weather-cached?city=London` first call = 1 external API call, second = 0 external calls
- [ ] API request tracking shows correct increment pattern
- [ ] `/api/clear-cache` resets cache, next calls show MISS
- [ ] Frontend loads all sections, buttons work
- [ ] Side-by-side timing display is visible and correct
- [ ] Cache hit/miss badges display with correct colors
- [ ] Weather API call counter diverges after 3-5 clicks (uncached: 5 calls, cached: 1 call)
- [ ] Responsive design works on mobile
- [ ] Session tracking works across multiple requests from same user

---

## Part 8: Stretch Goals (Optional)

- Add real-time chart visualization (ApexCharts) showing response time trends over 10 requests
- Display cache memory usage with Laravel's cache stats
- Add cache invalidation scenarios (e.g., "user updates a photo, invalidate cache")
- Add Redis as cache backend option with performance comparison
- Add query logging to show actual DB queries (uncached = many, cached = few)
- Add database indexing optimization demo (separate section)
- Multi-user simulation (show how caching reduces server load across users)

---

## Notes for AI Coding Agent

1. **Always measure with microtime(true)**: Client-side timing (JavaScript) may include network delay; server-side timing is more accurate.
2. **Session ID is critical**: Use `session()->getId()` or `session('user_id')` to track individual users across requests.
3. **Cache TTL must be consistent**: Use 600 seconds (10 minutes) for all cache entries so timing is predictable.
4. **Artificial delays are intentional**: The 1-second delay on database queries makes the difference obvious; don't remove it.
5. **External API errors**: Handle network failures gracefully; return 500 error with message rather than crashing.
6. **Logging**: Every request should log to either `cache_stats` or `api_requests` table for later analysis.
7. **Responsive design**: Demo should work on desktop (side-by-side) and mobile (stacked).
8. **Color coding is pedagogical**: Red=slow/bad, Green=fast/good, Yellow=uncertain (miss). Don't change these conventions.

---

**End of Specification**
