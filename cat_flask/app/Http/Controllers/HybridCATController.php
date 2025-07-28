<?php

namespace App\Http\Controllers;

use App\Services\HybridCATService;
use App\Services\FlaskApiService;
use App\Services\PerformanceMonitorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Exception;

class HybridCATController extends Controller
{
    protected $hybridCatService;
    protected $flaskApiService;
    protected $performanceMonitor;

    public function __construct(
        HybridCATService $hybridCatService, 
        FlaskApiService $flaskApiService,
        PerformanceMonitorService $performanceMonitor
    ) {
        $this->hybridCatService = $hybridCatService;
        $this->flaskApiService = $flaskApiService;
        $this->performanceMonitor = $performanceMonitor;
    }

    /**
     * Start a new test session
     */
    public function startTest(): JsonResponse
    {
        try {
            $result = $this->hybridCatService->startSession();
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Submit response and get next item
     */
    public function submitResponse(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'session_id' => 'required|string',
                'item_id' => 'required|string',
                'answer' => 'required|integer|in:0,1'
            ]);

            $result = $this->hybridCatService->submitResponse(
                $request->session_id,
                $request->item_id,
                $request->answer
            );

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get session history
     */
    public function getSessionHistory(string $sessionId): JsonResponse
    {
        try {
            $result = $this->hybridCatService->getSessionHistory($sessionId);
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Flask API health status
     */
    public function getFlaskApiHealth(): JsonResponse
    {
        try {
            $result = $this->hybridCatService->getFlaskApiHealth();
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Force switch API source (untuk testing)
     */
    public function switchApiSource(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'use_flask' => 'required|boolean'
            ]);

            $this->hybridCatService->forceFlaskApi($request->use_flask);
            
            return response()->json([
                'message' => 'API source switched successfully',
                'current_source' => $this->hybridCatService->getApiSource()
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Direct Flask API calls (untuk testing/debugging)
     */
    
    /**
     * Test Flask API - Estimate Theta
     */
    public function testEstimateTheta(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'responses' => 'required|array',
                'responses.*.a' => 'required|numeric',
                'responses.*.b' => 'required|numeric',
                'responses.*.g' => 'required|numeric',
                'responses.*.answer' => 'required|integer|in:0,1'
            ]);

            $result = $this->flaskApiService->estimateTheta($request->responses);
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test Flask API - Next Item
     */
    public function testNextItem(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'theta' => 'required|numeric',
                'used_item_ids' => 'required|array',
                'responses' => 'array'
            ]);

            $result = $this->flaskApiService->selectNextItem(
                $request->theta,
                $request->used_item_ids,
                $request->responses ?? []
            );
            
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test Flask API - Calculate Score
     */
    public function testCalculateScore(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'theta' => 'required|numeric'
            ]);

            $result = $this->flaskApiService->calculateScore($request->theta);
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test Flask API - Stopping Criteria
     */
    public function testStoppingCriteria(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'responses' => 'required|array',
                'se_eap' => 'required|numeric',
                'used_item_ids' => 'required|array'
            ]);

            $result = $this->flaskApiService->checkStoppingCriteria(
                $request->responses,
                $request->se_eap,
                $request->used_item_ids
            );
            
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Debug stopping criteria with various test scenarios
     */
    public function debugStoppingCriteria(): JsonResponse
    {
        try {
            // Test the debug endpoint
            $debugResult = Http::timeout(30)
                ->post('http://127.0.0.1:5000/api/debug-stopping', [
                    'responses' => [],
                    'se_eap' => 0.3,
                    'used_item_ids' => []
                ]);

            if ($debugResult->failed()) {
                return response()->json(['error' => 'Flask debug endpoint failed: ' . $debugResult->body()], 500);
            }

            return response()->json($debugResult->json());
            
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get current API source info
     */
    public function getApiInfo(): JsonResponse
    {
        try {
            $flaskHealth = $this->hybridCatService->getFlaskApiHealth();
            
            return response()->json([
                'current_source' => $this->hybridCatService->getApiSource(),
                'flask_api_health' => $flaskHealth,
                'config' => [
                    'flask_api_url' => config('cat.flask_api_url'),
                    'flask_api_timeout' => config('cat.flask_api_timeout'),
                    'enable_fallback' => config('cat.enable_fallback'),
                    'max_items' => config('cat.max_items'),
                    'target_se' => config('cat.target_se')
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
