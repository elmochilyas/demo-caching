function getUVLevel(uvIndex) {
    if (uvIndex <= 2) return 'Low';
    if (uvIndex <= 5) return 'Moderate';
    if (uvIndex <= 7) return 'High';
    if (uvIndex <= 10) return 'Very High';
    return 'Extreme';
}

function getUVColor(uvIndex) {
    if (uvIndex <= 2) return '#22c55e';
    if (uvIndex <= 5) return '#eab308';
    if (uvIndex <= 7) return '#f97316';
    if (uvIndex <= 10) return '#ef4444';
    return '#7c3aed';
}

async function fetchWithTiming(url) {
    console.log('Fetching URL:', url);
    const startTime = performance.now();
    const response = await fetch(url);
    console.log('Response:', response.status, response.statusText);
    const endTime = performance.now();
    if (!response.ok) {
        const text = await response.text();
        console.log('Error response:', text);
        throw new Error('HTTP ' + response.status + ' - ' + text);
    }
    const data = await response.json();
    return {
        ...data,
        client_response_time_ms: Math.round(endTime - startTime)
    };
}

async function runDbCompare() {
    try {
        const [uncached, cached] = await Promise.all([
            fetchWithTiming('/photos-uncached'),
            fetchWithTiming('/photos-cached')
        ]);
        
        document.getElementById('uncached-photos-time').textContent = uncached.response_time_ms + 'ms';
        document.getElementById('cached-photos-time').textContent = cached.response_time_ms + 'ms';
        
        if (cached.cache_hit) {
            document.getElementById('cached-photos-cache').textContent = 'HIT';
            document.getElementById('cached-photos-cache').className = 'stat-badge hit';
        } else {
            document.getElementById('cached-photos-cache').textContent = 'MISS';
            document.getElementById('cached-photos-cache').className = 'stat-badge miss';
        }
        
        const uncachedGrid = document.getElementById('uncached-photos-grid');
        const cachedGrid = document.getElementById('cached-photos-grid');
        
        uncachedGrid.innerHTML = '';
        cachedGrid.innerHTML = '';
        
        const photos = cached.data || [];
        photos.slice(0, 6).forEach(photo => {
            const card = `
                <div class="photo-card">
                    <img src="${photo.url}" alt="${photo.title}" onerror="this.src='https://via.placeholder.com/200x100?text=No+Image'">
                    <div class="photo-title">${photo.title}</div>
                </div>
            `;
            uncachedGrid.innerHTML += card;
            cachedGrid.innerHTML += card;
        });
        
        document.getElementById('uncached-photos-requests').textContent = uncached.global_total_requests || 0;
        document.getElementById('cached-photos-requests').textContent = cached.global_total_requests || 0;
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading data');
    }
}

async function runApiCompare() {
    try {
        const lat = document.getElementById('api-lat').value || '51.5';
        const lng = document.getElementById('api-lng').value || '-0.11';
        
        const [uncached, cached] = await Promise.all([
            fetchWithTiming(`/weather-uncached?lat=${lat}&lng=${lng}`),
            fetchWithTiming(`/weather-cached?lat=${lat}&lng=${lng}`)
        ]);
        
        document.getElementById('uncached-weather-time').textContent = uncached.response_time_ms + 'ms';
        document.getElementById('cached-weather-time').textContent = cached.response_time_ms + 'ms';
        
        if (cached.cache_hit) {
            document.getElementById('cached-weather-cache').textContent = 'HIT';
            document.getElementById('cached-weather-cache').className = 'stat-badge hit';
        } else {
            document.getElementById('cached-weather-cache').textContent = 'MISS';
            document.getElementById('cached-weather-cache').className = 'stat-badge miss';
        }
        
        const uncachedCard = document.getElementById('uncached-weather-card');
        const cachedCard = document.getElementById('cached-weather-card');
        
        if (uncached.data && uncached.data.uv_index !== null) {
            const uvLevel = getUVLevel(uncached.data.uv_index);
            const uvColor = getUVColor(uncached.data.uv_index);
            uncachedCard.innerHTML = `
                <div class="uv-value" style="color: ${uvColor}">${uncached.data.uv_index}</div>
                <div class="uv-level">${uvLevel}</div>
                <div class="uv-details">Max: ${uncached.data.uv_max}</div>
            `;
        } else {
            uncachedCard.innerHTML = '<div class="placeholder">API Error</div>';
        }
        
        if (cached.data && cached.data.uv_index !== null) {
            const uvLevel = getUVLevel(cached.data.uv_index);
            const uvColor = getUVColor(cached.data.uv_index);
            cachedCard.innerHTML = `
                <div class="uv-value" style="color: ${uvColor}">${cached.data.uv_index}</div>
                <div class="uv-level">${uvLevel}</div>
                <div class="uv-details">Max: ${cached.data.uv_max}</div>
            `;
        } else {
            cachedCard.innerHTML = '<div class="placeholder">API Error</div>';
        }
        
        document.getElementById('uncached-api-calls').textContent = uncached.global_total_requests || 0;
        document.getElementById('uncached-external-calls').textContent = uncached.global_api_calls || 0;
        document.getElementById('cached-api-calls').textContent = cached.global_total_requests || 0;
        document.getElementById('cached-external-calls').textContent = cached.global_api_calls || 0;
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading data');
    }
}

function clearCache() {
    fetch('/clear')
        .then(response => response.json())
        .then(() => {
            document.getElementById('uncached-photos-time').textContent = '--';
            document.getElementById('cached-photos-time').textContent = '--';
            document.getElementById('cached-photos-cache').textContent = '--';
            document.getElementById('uncached-photos-requests').textContent = '0';
            document.getElementById('cached-photos-requests').textContent = '0';
            document.getElementById('uncached-weather-time').textContent = '--';
            document.getElementById('cached-weather-time').textContent = '--';
            document.getElementById('cached-weather-cache').textContent = '--';
            document.getElementById('uncached-api-calls').textContent = '0';
            document.getElementById('uncached-external-calls').textContent = '0';
            document.getElementById('cached-api-calls').textContent = '0';
            document.getElementById('cached-external-calls').textContent = '0';
            document.getElementById('uncached-photos-grid').innerHTML = '<div class="placeholder"><i class="fas fa-images"></i></div>';
            document.getElementById('cached-photos-grid').innerHTML = '<div class="placeholder"><i class="fas fa-images"></i></div>';
            document.getElementById('uncached-weather-card').innerHTML = '<div class="placeholder">Run test to see results</div>';
            document.getElementById('cached-weather-card').innerHTML = '<div class="placeholder">Run test to see results</div>';
            alert('Cache cleared!');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error clearing cache');
        });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Caching Demo loaded');
});