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

const API_BASE = '/caching/public/api';

async function loadPhotos() {
    const [uncached, cached] = await Promise.all([
        fetchWithTiming(`${API_BASE}/photos-uncached`),
        fetchWithTiming(`${API_BASE}/photos-cached`)
    ]);
    
    updatePhotosDisplay(uncached, cached);
}

function updatePhotosDisplay(uncached, cached) {
    const uncachedTimeEl = document.getElementById('uncached-photos-time');
    const cachedTimeEl = document.getElementById('cached-photos-time');
    const uncachedCacheEl = document.getElementById('uncached-photos-cache');
    const cachedCacheEl = document.getElementById('cached-photos-cache');
    
    uncachedTimeEl.textContent = `Response: ${uncached.response_time_ms}ms`;
    cachedTimeEl.textContent = `Response: ${cached.response_time_ms}ms`;
    
    uncachedCacheEl.textContent = 'Cache: N/A';
    uncachedCacheEl.className = 'cache-status status-na';
    
    if (cached.cache_hit) {
        cachedCacheEl.textContent = 'Cache: HIT';
        cachedCacheEl.className = 'cache-status status-hit';
    } else {
        cachedCacheEl.textContent = 'Cache: MISS';
        cachedCacheEl.className = 'cache-status status-miss';
    }
    
    const uncachedGrid = document.getElementById('uncached-photos-grid');
    const cachedGrid = document.getElementById('cached-photos-grid');
    
    uncachedGrid.innerHTML = '';
    cachedGrid.innerHTML = '';
    
    const photos = cached.data || [];
    photos.slice(0, 8).forEach(photo => {
        const photoCard = createPhotoCard(photo);
        uncachedGrid.innerHTML += photoCard;
        cachedGrid.innerHTML += photoCard;
    });
}

function createPhotoCard(photo) {
    return `
        <div class="photo-card">
            <img src="${photo.url}" alt="${photo.title}" onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
            <div class="photo-title">${photo.title}</div>
            <div class="photo-description">${photo.description || ''}</div>
        </div>
    `;
}

async function loadUV() {
    const lat = document.getElementById('uncached-lat').value || '51.5';
    const lng = document.getElementById('uncached-lng').value || '-0.11';
    
    const [uncached, cached] = await Promise.all([
        fetchWithTiming(`${API_BASE}/weather-uncached?lat=${lat}&lng=${lng}`),
        fetchWithTiming(`${API_BASE}/weather-cached?lat=${lat}&lng=${lng}`)
    ]);
    
    updateUVDisplay(uncached, cached);
}

function updateUVDisplay(uncached, cached) {
    const uncachedTimeEl = document.getElementById('uncached-weather-time');
    const cachedTimeEl = document.getElementById('cached-weather-time');
    const cachedCacheEl = document.getElementById('cached-weather-cache');
    
    uncachedTimeEl.textContent = `Response: ${uncached.response_time_ms}ms`;
    cachedTimeEl.textContent = `Response: ${cached.response_time_ms}ms`;
    
    if (cached.cache_hit) {
        cachedCacheEl.textContent = 'Cache: HIT';
        cachedCacheEl.className = 'cache-status status-hit';
    } else {
        cachedCacheEl.textContent = 'Cache: MISS';
        cachedCacheEl.className = 'cache-status status-miss';
    }
    
    const uncachedCard = document.querySelector('#uncached-weather-card .weather-data');
    const cachedCard = document.querySelector('#cached-weather-card .weather-data');
    
    if (uncached.data) {
        uncachedCard.innerHTML = `
            <div class="uv-location">Lat: ${uncached.data.lat}, Lng: ${uncached.data.lng}</div>
            <div class="uv-index">UV Index: ${uncached.data.uv_index}</div>
            <div class="uv-max">Max UV: ${uncached.data.uv_max}</div>
            <div class="uv-time">Time: ${uncached.data.uv_time}</div>
        `;
    }
    
    if (cached.data) {
        cachedCard.innerHTML = `
            <div class="uv-location">Lat: ${cached.data.lat}, Lng: ${cached.data.lng}</div>
            <div class="uv-index">UV Index: ${cached.data.uv_index}</div>
            <div class="uv-max">Max UV: ${cached.data.uv_max}</div>
            <div class="uv-time">Time: ${cached.data.uv_time}</div>
        `;
    }
    
    document.getElementById('uncached-api-calls').textContent = uncached.user_total_requests || 0;
    document.getElementById('uncached-external-calls').textContent = uncached.external_api_calls || 0;
    document.getElementById('cached-api-calls').textContent = cached.user_total_requests || 0;
    document.getElementById('cached-external-calls').textContent = cached.external_api_calls || 0;
}

async function clearCache() {
    try {
        await fetch(`${API_BASE}/clear-cache`, { method: 'POST' });
        alert('Cache cleared. Reload the page.');
        location.reload();
    } catch (error) {
        console.error('Error clearing cache:', error);
    }
}

async function resetSession() {
    location.reload();
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Caching Demo loaded');
});