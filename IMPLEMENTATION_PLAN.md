# Caching Demo - Implementation Plan

## Overview

This document provides a comprehensive implementation plan for the Laravel/MySQL Caching Demo project. The project demonstrates the importance of caching through two parallel flows: uncached (slow) and cached (fast).

**Tech Stack:**
- Backend: Laravel 11+ (PHP 8.1+)
- Database: MySQL 8.0+
- Frontend: HTML5, CSS3, JavaScript (vanilla)
- Caching: Laravel Cache Facade (File or Redis backend)
- External API: OpenWeatherMap

---

## Phase 1: Database Setup & Seeding

### Tasks

1. **Create Photo Migration**
   - File: `database/migrations/xxxx_xx_xx_create_photos_table.php`
   - Schema:
     - `id` - primary key, auto-increment
     - `title` - string, max 255
     - `url` - string, URL to image
     - `description` - text, nullable
     - `metadata` - JSON or text, simulated metadata
     - `created_at` - timestamp
     - `updated_at` - timestamp

2. **Create CacheStat Migration**
   - File: `database/migrations/xxxx_xx_xx_create_cache_stats_table.php`
   - Schema:
     - `id` - primary key, auto-increment
     - `endpoint` - string ('photos-uncached' or 'photos-cached')
     - `cache_status` - string ('HIT', 'MISS', 'N/A')
     - `response_time_ms` - integer
     - `user_session_id` - string
     - `created_at` - timestamp
     - `updated_at` - timestamp

3. **Create ApiRequest Migration**
   - File: `database/migrations/xxxx_xx_xx_create_api_requests_table.php`
   - Schema:
     - `id` - primary key, auto-increment
     - `user_session_id` - string
     - `endpoint` - string ('weather-uncached' or 'weather-cached')
     - `api_call_count` - integer
     - `request_count` - integer
     - `response_time_ms` - integer
     - `created_at` - timestamp
     - `updated_at` - timestamp

4. **Create Photo Model**
   - File: `app/Models/Photo.php`
   - Features:
     - Fillable: title, url, description, metadata
     - Casts: metadata as array

5. **Create CacheStat Model**
   - File: `app/Models/CacheStat.php`
   - Features:
     - Fillable: endpoint, cache_status, response_time_ms, user_session_id

6. **Create ApiRequest Model**
   - File: `app/Models/ApiRequest.php`
   - Features:
     - Fillable: user_session_id, endpoint, request_count, api_call_count

7. **Create PhotoFactory**
   - File: `database/factories/PhotoFactory.php`
   - Generate 300 realistic photo records with:
     - Titles: "Photo 1", "Photo 2", etc.
     - URLs: Fake image URLs from placeholder services
     - Descriptions: Lorem ipsum or generated descriptions
     - Metadata: JSON with camera info, location, etc.

8. **Create PhotoSeeder**
   - File: `database/seeders/PhotoSeeder.php`
   - Seed 300 photos using PhotoFactory
   - Command: `php artisan db:seed --class=PhotoSeeder`

9. **Run Migrations and Seed**
   ```bash
   php artisan migrate:fresh --seed
   ```

**Deliverables:**
- 3 database tables (photos, cache_stats, api_requests)
- 3 Eloquent models with proper relationships
- 300 photo records in database

---

## Phase 2: Photo Caching Endpoints

### Tasks

10. **Create CachingDemoController**
    - File: `app/Http/Controllers/CachingDemoController.php`

11. **Create GET /api/photos-uncached Endpoint**
    - Route: `GET /api/photos-uncached`
    - Implementation:
      - Add artificial delay: `usleep(1000000)` (1 second)
      - Query: `Photo::all()->take(10)`
      - Measure response time with `microtime(true)`
      - Log to cache_stats table with `cache_status = 'N/A'`
      - Use session ID: `session()->getId()`
    - Response:
      ```json
      {
        "status": "success",
        "cache_hit": false,
        "response_time_ms": 1250,
        "data": [...]
      }
      ```

12. **Create GET /api/photos-cached Endpoint**
    - Route: `GET /api/photos-cached`
    - Implementation:
      - Use `Cache::remember('photos-all', 600, function() { ... })`
      - Cache TTL: 600 seconds (10 minutes)
      - On cache hit: return cached data, set cache_hit = true
      - On cache miss: query DB with delay, cache result
      - Log to cache_stats with 'HIT' or 'MISS'
    - Response:
      ```json
      {
        "status": "success",
        "cache_hit": true,
        "response_time_ms": 45,
        "data": [...]
      }
      ```

**Expected Behavior:**
- First call (both): ~1200-1300ms
- Subsequent uncached: ~1200-1300ms (always slow)
- Subsequent cached: ~40-80ms (fast, from cache)

**Deliverables:**
- 2 API endpoints for photos
- Cache statistics logging

---

## Phase 3: Weather API Endpoints

### Tasks

13. **Configure OpenWeatherMap in config/services.php**
    ```php
    'openweathermap' => [
        'key' => env('OPENWEATHERMAP_API_KEY'),
    ],
    ```

14. **Create GET /api/weather-uncached Endpoint**
    - Route: `GET /api/weather-uncached?city={city}`
    - Implementation:
      - Get session ID from session
      - Call external API: `https://api.openweathermap.org/data/2.5/weather?q={city}&appid={API_KEY}&units=metric`
      - Increment request_count in api_requests table
      - Increment api_call_count in api_requests table
      - Measure response time
      - Return JSON with cache_status = 'N/A'
    - Response:
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

15. **Create GET /api/weather-cached Endpoint**
    - Route: `GET /api/weather-cached?city={city}`
    - Implementation:
      - Cache key: "weather-{city}"
      - Use `Cache::remember("weather-{$city}", 600, function() { ... })`
      - Cache TTL: 600 seconds
      - On cache hit:
        - Return cached data
        - Set external_api_calls = 0
        - Set cache_hit = true
      - On cache miss:
        - Call external API
        - Increment api_call_count to 1
        - Cache result for 10 minutes
        - Set cache_hit = false
      - Always increment request_count
    - Response:
      ```json
      {
        "status": "success",
        "cache_hit": true,
        "external_api_calls": 0,
        "user_total_requests": 5,
        "response_time_ms": 12,
        "data": {...}
      }
      ```

**Expected Behavior:**
- 5 user requests with uncached: 5 external API calls
- 5 user requests with cached: 1 external API call

**Deliverables:**
- 2 API endpoints for weather
- External API integration
- Request tracking per session

---

## Phase 4: Clear Cache Endpoint

### Tasks

16. **Create POST /api/clear-cache Endpoint**
    - Route: `POST /api/clear-cache`
    - Implementation:
      - Forget cache key 'photos-all'
      - Forget weather cache keys:
        - weather-London
        - weather-Paris
        - weather-Tokyo
        - weather-New York
        - weather-Sydney
      - Return success message
    - Response:
      ```json
      {
        "status": "success",
        "message": "Cache cleared"
      }
      ```

**Deliverables:**
- Clear cache endpoint to reset demo

---

## Phase 5: Frontend & Demo Page

### Tasks

17. **Create Blade Template demo.blade.php**
    - File: `resources/views/demo.blade.php`
    - Layout:
      ```
      ┌─────────────────────────────────────────────┐
      │           Caching Demo                     │
      ├──────────────────┬────────────────────────┤
      │  WITHOUT CACHING  │    WITH CACHING         │
      │  (Slow)           │    (Fast)               │
      ├──────────────────┼────────────────────────┤
      │  📊 Database     │  📊 Database           │
      │  Response: 1250ms│  Response: 45ms          │
      │  Cache: MISS     │  Cache: HIT             │
      │  [Load Photos]   │  [Load Photos]          │
      ├──────────────────┼────────────────────────┤
      │  📊 API          │  📊 API                │
      │  City: London    │  City: London          │
      │  Response: 850ms │  Response: 12ms        │
      │  API Calls: 5/5  │  API Calls: 5/1         │
      │  [Load Weather]  │  [Load Weather]        │
      └──────────────────┴────────────────────────┘
      ```

18. **Create Photo Demo Section**
    - Left Column (Uncached):
      - Button: "Load Photos"
      - Display: response_time_ms (large, red)
      - Cache status badge: "MISS" (red) or "N/A" (gray)
      - Grid of 5-10 photos
      - Request counter
    - Right Column (Cached):
      - Button: "Load Photos"
      - Display: response_time_ms (large, green)
      - Cache status badge: "HIT" (green) or "MISS" (yellow)
      - Grid of 5-10 photos
      - Request counter

19. **Create Weather Demo Section**
    - Left Column (Uncached):
      - Input: "Enter city name" (default: "London")
      - Button: "Load Weather"
      - Display: response_time_ms
      - Cache status badge: "N/A"
      - Weather card: city, temp, humidity, description
      - Key metric: "API Calls Made: X | External API Calls: Y"
    - Right Column (Cached):
      - Same input and button
      - Display: response_time_ms
      - Cache status badge: "HIT" or "MISS"
      - Weather card
      - Key metric: "API Calls Made: X | External API Calls: Y"

20. **Create Global Controls**
    - Clear Cache button (POST to /api/clear-cache)
    - Session reset option

21. **Create demo.js**
    - File: `resources/js/demo.js`
    - Functions:
      ```javascript
      // Utility: Measure request time
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
        const [uncached, cached] = await Promise.all([
          fetchWithTiming('/api/photos-uncached'),
          fetchWithTiming('/api/photos-cached')
        ]);
        updatePhotosDisplay(uncached, cached);
      }

      // Weather Demo
      async function loadWeather(city) {
        const [uncached, cached] = await Promise.all([
          fetchWithTiming(`/api/weather-uncached?city=${city}`),
          fetchWithTiming(`/api/weather-cached?city=${city}`)
        ]);
        updateWeatherDisplay(uncached, cached);
      }

      // Clear Cache
      async function clearCache() {
        await fetch('/api/clear-cache', { method: 'POST' });
      }
      ```

22. **Create demo.css**
    - File: `resources/css/demo.css`
    - Features:
      - Responsive grid (2 columns desktop, 1 column mobile)
      - Color coding:
        - Uncached: Red/Orange (#FF6B6B)
        - Cached: Green (#51CF66)
        - Hit badge: Green
        - Miss badge: Yellow (#FFD93D)
        - N/A badge: Gray
      - Large fonts for timing (18-24px)
      - Cards with shadow
      - Smooth transitions

**Deliverables:**
- Full demo page with both columns
- JavaScript interactivity
- Styled responsive UI

---

## Phase 6: Configuration & Testing

### Tasks

23. **Update .env Template**
    ```
    CACHE_DRIVER=file
    OPENWEATHERMAP_API_KEY=your_free_api_key_here
    ```

24. **Add API Routes**
    - File: `routes/api.php`
    ```php
    Route::get('/photos-uncached', [CachingDemoController::class, 'photosUncached']);
    Route::get('/photos-cached', [CachingDemoController::class, 'photosCached']);
    Route::get('/weather-uncached', [CachingDemoController::class, 'weatherUncached']);
    Route::get('/weather-cached', [CachingDemoController::class, 'weatherCached']);
    Route::post('/clear-cache', [CachingDemoController::class, 'clearCache']);
    ```

25. **Add Web Route**
    - File: `routes/web.php`
    ```php
    Route::get('/', [CachingDemoController::class, 'index'])->name('demo');
    ```

26. **Create TrackSession Middleware (Optional)**
    - File: `app/Http/Middleware/TrackSession.php`
    - Ensure session ID exists for all requests

27. **Configure Cache**
    - File: `config/cache.php`
    - Ensure cache driver is set (file or redis)

**Deliverables:**
- All routes configured
- Environment properly set up

---

## Phase 7: Testing & Verification

### Tasks

28. **Test Photos Uncached Endpoint**
    - Verify response time ~1200-1300ms (1s delay + network)
    - Verify cache_status = 'N/A' in response
    - Verify cache_stats table has new record

29. **Test Photos Cached Endpoint (First Call)**
    - Verify response time ~1200-1300ms (MISS)
    - Verify cache_hit = false in response
    - Verify cache_status = 'MISS' in logs

30. **Test Photos Cached Endpoint (Second Call)**
    - Verify response time ~40-80ms (HIT)
    - Verify cache_hit = true in response
    - Verify cache_status = 'HIT' in logs

31. **Test Weather Uncached Endpoint**
    - Call 5 times with same city
    - Verify external_api_calls increments each time
    - Verify user_total_requests = 5

32. **Test Weather Cached Endpoint**
    - Call 5 times with same city
    - First call: external_api_calls = 1
    - Calls 2-5: external_api_calls = 0
    - Verify request_count = 5, api_call_count = 1

33. **Test Clear Cache**
    - Call POST /api/clear-cache
    - Verify next call to cached returns MISS

34. **Test Database Seeding**
    - Run `php artisan migrate:fresh --seed`
    - Verify 300 photos in database

35. **Test Responsive Design**
    - Test on mobile (stacked layout)
    - Test on desktop (side-by-side layout)

**Testing Checklist (from spec):**
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
- [ ] Weather API call counter diverges after 3-5 clicks
- [ ] Responsive design works on mobile
- [ ] Session tracking works across multiple requests

**Deliverables:**
- All endpoints tested and working
- Demo page functional

---

## Phase 8: Stretch Goals (Optional)

### Tasks

36. **Add Real-Time Chart Visualization**
    - Integrate ApexCharts
    - Show response time trends over 10 requests
    - Visual comparison: uncached vs cached

37. **Add Redis Cache Backend Option**
    - Configure Redis in config/cache.php
    - Performance comparison: file vs redis

38. **Add Cache Invalidation Scenarios**
    - Demo: "user updates a photo, invalidate cache"
    - Button to manually invalidate specific cache keys

39. **Add Multi-User Simulation**
    - Show how caching reduces server load across users
    - Simulate multiple sessions

40. **Add Query Logging**
    - Display actual DB queries (uncached = many, cached = few)
    - Use Laravel Debugbar or custom logging

---

## Implementation Order

```
Phase 1: Database Setup & Seeding
    └── Tasks 1-9

Phase 2: Photo Caching Endpoints
    └── Tasks 10-12

Phase 3: Weather API Endpoints
    └── Tasks 13-15

Phase 4: Clear Cache Endpoint
    └── Task 16

Phase 5: Frontend & Demo Page
    └── Tasks 17-22

Phase 6: Configuration & Testing
    └── Tasks 23-27

Phase 7: Testing & Verification
    └── Tasks 28-35

Phase 8: Stretch Goals (Optional)
    └── Tasks 36-40
```

---

## Key Implementation Notes

1. **Always measure with microtime(true)**: Client-side timing may include network delay; server-side timing is more accurate.

2. **Session ID is critical**: Use `session()->getId()` to track individual users across requests.

3. **Cache TTL must be consistent**: Use 600 seconds (10 minutes) for all cache entries.

4. **Artificial delays are intentional**: The 1-second delay on database queries makes the difference obvious.

5. **External API errors**: Handle network failures gracefully; return 500 error with message.

6. **Logging**: Every request should log to either `cache_stats` or `api_requests` table.

7. **Responsive design**: Demo should work on desktop and mobile.

8. **Color coding**: Red=slow/bad, Green=fast/good, Yellow=uncertain (miss).

---

## File Structure Summary

```
laravel-caching-demo/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── CachingDemoController.php
│   │   └── Middleware/
│   │       └── TrackSession.php
│   └── Models/
│       ├── Photo.php
│       ├── CacheStat.php
│       └── ApiRequest.php
├── database/
│   ├── migrations/
│   │   ├── create_photos_table.php
│   │   ├── create_cache_stats_table.php
│   │   └── create_api_requests_table.php
│   ├── seeders/
│   │   └── PhotoSeeder.php
│   └── factories/
│       └── PhotoFactory.php
├── routes/
│   ├── api.php
│   └── web.php
├── resources/
│   ├── views/
│   │   └── demo.blade.php
│   ├── css/
│   │   └── demo.css
│   └── js/
│       └── demo.js
├── config/
│   ├── cache.php
│   └── services.php
└── .env
```

---

## Command Reference

```bash
# Run migrations
php artisan migrate

# Run migrations fresh with seeding
php artisan migrate:fresh --seed

# Seed only
php artisan db:seed --class=PhotoSeeder

# Clear cache
php artisan cache:clear

# Start development server
php artisan serve
```

---

**End of Implementation Plan**