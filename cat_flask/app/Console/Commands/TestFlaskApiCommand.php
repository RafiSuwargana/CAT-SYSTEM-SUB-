<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FlaskApiService;
use App\Services\HybridCATService;
use App\Models\TestResponse;
use App\Models\ItemParameter;
use Exception;

class TestFlaskApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cat:test-flask-api 
                           {--endpoint=all : Endpoint to test (all, health, estimate-theta, next-item, calculate-score, stopping-criteria)}
                           {--session= : Session ID untuk testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Flask API connection and endpoints';

    protected $flaskApiService;
    protected $hybridCatService;

    public function __construct(FlaskApiService $flaskApiService, HybridCATService $hybridCatService)
    {
        parent::__construct();
        $this->flaskApiService = $flaskApiService;
        $this->hybridCatService = $hybridCatService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $endpoint = $this->option('endpoint');
        $sessionId = $this->option('session');
        
        $this->info("Testing Flask API...");
        $this->info("Base URL: " . config('cat.flask_api_url'));
        $this->info("Timeout: " . config('cat.flask_api_timeout') . " seconds");
        $this->newLine();
        
        if ($endpoint === 'all') {
            $this->testAllEndpoints($sessionId);
        } else {
            $this->testSingleEndpoint($endpoint, $sessionId);
        }
    }

    private function testAllEndpoints($sessionId)
    {
        $this->info("=== Testing All Flask API Endpoints ===");
        $this->newLine();
        
        $tests = [
            'health' => 'Health Check',
            'estimate-theta' => 'Estimate Theta',
            'next-item' => 'Next Item Selection',
            'calculate-score' => 'Calculate Score',
            'stopping-criteria' => 'Stopping Criteria'
        ];
        
        foreach ($tests as $endpoint => $description) {
            $this->info("Testing: {$description}");
            $this->testSingleEndpoint($endpoint, $sessionId);
            $this->newLine();
        }
        
        $this->info("=== Integration Test ===");
        $this->testIntegration();
    }

    private function testSingleEndpoint($endpoint, $sessionId)
    {
        try {
            switch ($endpoint) {
                case 'health':
                    $this->testHealthEndpoint();
                    break;
                    
                case 'estimate-theta':
                    $this->testEstimateThetaEndpoint($sessionId);
                    break;
                    
                case 'next-item':
                    $this->testNextItemEndpoint($sessionId);
                    break;
                    
                case 'calculate-score':
                    $this->testCalculateScoreEndpoint();
                    break;
                    
                case 'stopping-criteria':
                    $this->testStoppingCriteriaEndpoint($sessionId);
                    break;
                    
                default:
                    $this->error("Unknown endpoint: {$endpoint}");
                    break;
            }
        } catch (Exception $e) {
            $this->error("Test failed: " . $e->getMessage());
        }
    }

    private function testHealthEndpoint()
    {
        try {
            $result = $this->flaskApiService->healthCheck();
            $this->info("✅ Health Check: " . $result['status']);
            $this->info("   Total Items: " . $result['total_items']);
            $this->info("   API Version: " . $result['api_version']);
        } catch (Exception $e) {
            $this->error("❌ Health Check failed: " . $e->getMessage());
        }
    }

    private function testEstimateThetaEndpoint($sessionId)
    {
        try {
            // Sample responses
            $responses = [
                ['a' => 1.5, 'b' => -1.0, 'g' => 0.2, 'answer' => 1],
                ['a' => 2.0, 'b' => 0.5, 'g' => 0.25, 'answer' => 0],
                ['a' => 1.2, 'b' => 1.5, 'g' => 0.15, 'answer' => 1]
            ];
            
            // If session ID provided, use real data
            if ($sessionId) {
                $realResponses = TestResponse::where('session_id', $sessionId)
                    ->with('item')
                    ->get();
                    
                if ($realResponses->isNotEmpty()) {
                    $responses = $this->flaskApiService->convertResponsesToApiFormat($realResponses);
                    $this->info("   Using real responses from session: {$sessionId}");
                    $this->info("   Response count: " . count($responses));
                }
            }
            
            $result = $this->flaskApiService->estimateTheta($responses);
            $this->info("✅ Estimate Theta:");
            $this->info("   Theta: " . number_format($result['theta'], 4));
            $this->info("   SE_EAP: " . number_format($result['se_eap'], 4));
            $this->info("   Num Responses: " . $result['num_responses']);
        } catch (Exception $e) {
            $this->error("❌ Estimate Theta failed: " . $e->getMessage());
        }
    }

    private function testNextItemEndpoint($sessionId)
    {
        try {
            $theta = 0.5;
            $usedIds = [1, 2, 3];
            $responses = [];
            
            // If session ID provided, use real data
            if ($sessionId) {
                $realResponses = TestResponse::where('session_id', $sessionId)
                    ->with('item')
                    ->get();
                    
                if ($realResponses->isNotEmpty()) {
                    $responses = $this->flaskApiService->convertResponsesToApiFormat($realResponses);
                    $usedIds = $realResponses->pluck('item_id')->toArray();
                    $this->info("   Using real data from session: {$sessionId}");
                }
            }
            
            $result = $this->flaskApiService->selectNextItem($theta, $usedIds, $responses);
            $this->info("✅ Next Item Selection:");
            $this->info("   Item ID: " . $result['item']['id']);
            $this->info("   Item Parameters: a=" . $result['item']['a'] . ", b=" . $result['item']['b'] . ", g=" . $result['item']['g']);
            $this->info("   Probability: " . number_format($result['probability'], 4));
            $this->info("   Fisher Info: " . number_format($result['fisher_information'], 4));
            $this->info("   Expected Fisher Info: " . number_format($result['expected_fisher_information'], 4));
        } catch (Exception $e) {
            $this->error("❌ Next Item Selection failed: " . $e->getMessage());
        }
    }

    private function testCalculateScoreEndpoint()
    {
        try {
            $theta = 0.5;
            $result = $this->flaskApiService->calculateScore($theta);
            $this->info("✅ Calculate Score:");
            $this->info("   Theta: " . number_format($result['theta'], 4));
            $this->info("   Final Score: " . number_format($result['final_score'], 2));
        } catch (Exception $e) {
            $this->error("❌ Calculate Score failed: " . $e->getMessage());
        }
    }

    private function testStoppingCriteriaEndpoint($sessionId)
    {
        try {
            $responses = [
                ['a' => 1.5, 'b' => -1.0, 'g' => 0.2, 'answer' => 1],
                ['a' => 2.0, 'b' => 0.5, 'g' => 0.25, 'answer' => 0]
            ];
            $seEap = 0.8;
            $usedIds = [1, 2];
            
            // If session ID provided, use real data
            if ($sessionId) {
                $realResponses = TestResponse::where('session_id', $sessionId)
                    ->with('item')
                    ->get();
                    
                if ($realResponses->isNotEmpty()) {
                    $responses = $this->flaskApiService->convertResponsesToApiFormat($realResponses);
                    $usedIds = $realResponses->pluck('item_id')->toArray();
                    $seEap = $realResponses->last()->se_after ?? 0.8;
                    $this->info("   Using real data from session: {$sessionId}");
                }
            }
            
            $result = $this->flaskApiService->checkStoppingCriteria($responses, $seEap, $usedIds);
            $this->info("✅ Stopping Criteria:");
            $this->info("   Should Stop: " . ($result['should_stop'] ? 'Yes' : 'No'));
            $this->info("   Reason: " . ($result['reason'] ?: 'Continue testing'));
            $this->info("   Num Responses: " . $result['num_responses']);
            $this->info("   SE_EAP: " . number_format($result['se_eap'], 4));
        } catch (Exception $e) {
            $this->error("❌ Stopping Criteria failed: " . $e->getMessage());
        }
    }

    private function testIntegration()
    {
        try {
            $this->info("Testing HybridCATService integration...");
            
            // Test API source detection
            $apiSource = $this->hybridCatService->getApiSource();
            $this->info("✅ Current API Source: " . $apiSource);
            
            // Test Flask API health from hybrid service
            $health = $this->hybridCatService->getFlaskApiHealth();
            if ($health['status'] === 'healthy') {
                $this->info("✅ Flask API Health: " . $health['status']);
            } else {
                $this->error("❌ Flask API Health: " . $health['status']);
                if (isset($health['error'])) {
                    $this->error("   Error: " . $health['error']);
                }
            }
            
            // Test connection
            $connection = $this->flaskApiService->testConnection();
            $this->info("✅ Flask API Connection Test: " . ($connection ? 'Success' : 'Failed'));
            
        } catch (Exception $e) {
            $this->error("❌ Integration test failed: " . $e->getMessage());
        }
    }
}
