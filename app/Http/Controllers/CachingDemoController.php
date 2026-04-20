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
        
        $photos = Photo::all()->take(10)->toArray();
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        $sessionId = session()->getId();
        CacheStat::create([
            'user_session_id' => $sessionId,
            'endpoint' => 'photos-uncached',
            'cache_status' => 'N/A',
            'response_time_ms' => $responseTime,
        ]);
        
        $globalRequests = CacheStat::where('endpoint', 'photos-uncached')->count();
        
        return response()->json([
            'status' => 'success',
            'cache_hit' => false,
            'global_total_requests' => $globalRequests,
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
            $photos = Photo::all()->take(10)->toArray();
            Cache::put($cacheKey, $photos, 600);
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        $sessionId = session()->getId();
        CacheStat::create([
            'user_session_id' => $sessionId,
            'endpoint' => 'photos-cached',
            'cache_status' => $cacheHit ? 'HIT' : 'MISS',
            'response_time_ms' => $responseTime,
        ]);
        
        $globalRequests = CacheStat::where('endpoint', 'photos-cached')->count();
        
        return response()->json([
            'status' => 'success',
            'cache_hit' => $cacheHit,
            'global_total_requests' => $globalRequests,
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
            $uvData = [
                'result' => [
                    'uv' => rand(1, 10),
                    'uv_max' => rand(5, 12),
                    'uv_time' => date('Y-m-d\TH:i:sP'),
                ]
            ];
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        ApiRequest::create([
            'user_session_id' => $sessionId,
            'endpoint' => 'weather-uncached',
            'request_count' => 1,
            'api_call_count' => 1,
        ]);

        $globalApiCalls = ApiRequest::where('endpoint', 'weather-uncached')->sum('api_call_count');
        $globalRequests = ApiRequest::where('endpoint', 'weather-uncached')->sum('request_count');
        
        return response()->json([
            'status' => 'success',
            'cache_hit' => false,
            'global_api_calls' => $globalApiCalls,
            'global_total_requests' => $globalRequests,
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
                $uvData = [
                    'result' => [
                        'uv' => rand(1, 10),
                        'uv_max' => rand(5, 12),
                        'uv_time' => date('Y-m-d\TH:i:sP'),
                    ]
                ];
                Cache::put($cacheKey, $uvData, 600);
                $apiCallsIncrement = 1;
            }
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        ApiRequest::create([
            'user_session_id' => $sessionId,
            'endpoint' => 'weather-cached',
            'request_count' => 1,
            'api_call_count' => $apiCallsIncrement,
        ]);

        $globalApiCalls = ApiRequest::where('endpoint', 'weather-cached')->sum('api_call_count');
        $globalRequests = ApiRequest::where('endpoint', 'weather-cached')->sum('request_count');
        
        return response()->json([
            'status' => 'success',
            'cache_hit' => $cacheHit,
            'external_api_calls' => $apiCallsIncrement,
            'global_api_calls' => $globalApiCalls,
            'global_total_requests' => $globalRequests,
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
        try {
            Cache::flush();
            CacheStat::truncate();
            ApiRequest::truncate();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resetSession(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerate();
        
        return response()->json(['status' => 'success', 'message' => 'Session reset']);
    }

    public function index()
    {
        return view('demo');
    }
}