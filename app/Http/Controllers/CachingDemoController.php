<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\CacheStat;
use App\Models\ApiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CachingDemoController extends Controller
{
    public function photosUncached(Request $request)
    {
        $startTime = microtime(true);
        
        usleep(1000000);
        
        $photos = Photo::all()->take(10);
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
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

    public function photosCached(Request $request)
    {
        $startTime = microtime(true);
        $cacheKey = 'photos-all';
        $cacheHit = false;
        
        if (Cache::has($cacheKey)) {
            $cacheHit = true;
            $photos = Cache::get($cacheKey);
        } else {
            usleep(1000000);
            $photos = Photo::all()->take(10);
            Cache::put($cacheKey, $photos, 600);
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
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

    public function weatherUncached(Request $request)
    {
        $lat = $request->query('lat', 51.5074);
        $lng = $request->query('lng', -0.1278);
        $alt = $request->query('alt', 0);
        
        $startTime = microtime(true);
        $sessionId = session()->getId();
        
        try {
            $response = Http::withHeaders([
                'x-access-token' => config('services.openuv.key'),
            ])->get('https://api.openuv.io/api/v1/uv', [
                'lat' => $lat,
                'lng' => $lng,
                'alt' => $alt,
            ]);
            
            $uvData = $response->json();
        } catch (\Exception $e) {
            return response()->json(['error' => 'API call failed: ' . $e->getMessage()], 500);
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
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
                'lat' => $lat,
                'lng' => $lng,
                'uv_index' => $uvData['result']['uv'] ?? null,
                'uv_max' => $uvData['result']['uv_max'] ?? null,
                'uv_time' => $uvData['result']['uv_time'] ?? null,
            ],
        ]);
    }

    public function weatherCached(Request $request)
    {
        $lat = $request->query('lat', 51.5074);
        $lng = $request->query('lng', -0.1278);
        $alt = $request->query('alt', 0);
        
        $startTime = microtime(true);
        $sessionId = session()->getId();
        $cacheKey = "uv-{$lat}-{$lng}";
        $cacheHit = false;
        $apiCallsIncrement = 0;
        
        if (Cache::has($cacheKey)) {
            $cacheHit = true;
            $uvData = Cache::get($cacheKey);
        } else {
            try {
                $response = Http::withHeaders([
                    'x-access-token' => config('services.openuv.key'),
                    'Content-Type' => 'application/json',
                ])->get('https://api.openuv.io/api/v1/uv', [
                    'lat' => $lat,
                    'lng' => $lng,
                    'alt' => $alt,
                ]);
                
                $uvData = $response->json();
                Cache::put($cacheKey, $uvData, 600);
                $apiCallsIncrement = 1;
            } catch (\Exception $e) {
                return response()->json(['error' => 'API call failed: ' . $e->getMessage()], 500);
            }
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
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
                'lat' => $lat,
                'lng' => $lng,
                'uv_index' => $uvData['result']['uv'] ?? null,
                'uv_max' => $uvData['result']['uv_max'] ?? null,
                'uv_time' => $uvData['result']['uv_time'] ?? null,
            ],
        ]);
    }

    public function clearCache(Request $request)
    {
        Cache::forget('photos-all');
        
        foreach (['51.5074,-0.1278', '48.8566,2.3522', '35.6762,139.6503'] as $coords) {
            Cache::forget("uv-{$coords}");
        }
        
        return response()->json(['status' => 'success', 'message' => 'Cache cleared']);
    }

    public function index()
    {
        return view('demo');
    }
}