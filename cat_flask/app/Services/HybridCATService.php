<?php

namespace App\Services;

use App\Models\ItemParameter;
use App\Models\TestSession;
use App\Models\TestResponse;
use App\Models\UsedItem;
use App\Services\FlaskApiService;
use App\Services\CATService as OriginalCATService;
use App\Services\PerformanceMonitorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Hybrid CAT Service yang menggunakan Flask API untuk perhitungan
 * 
 * Arsitektur:
 * - Laravel: Database management, session handling, UI
 * - Flask API: IRT calculations (theta, SE, EFI, item selection)
 * 
 * Fallback:
 * - Jika Flask API tidak tersedia, gunakan CATService original
 */
class HybridCATService
{
    private $flaskApi;
    private $originalCatService;
    private $performanceMonitor;
    private $useFlaskApi;

    public function __construct(
        FlaskApiService $flaskApi, 
        OriginalCATService $originalCatService,
        PerformanceMonitorService $performanceMonitor
    ) {
        $this->flaskApi = $flaskApi;
        $this->originalCatService = $originalCatService;
        $this->performanceMonitor = $performanceMonitor;
        
        // Test Flask API connection
        $this->useFlaskApi = $this->flaskApi->testConnection();
        
        if (!$this->useFlaskApi) {
            Log::warning('Flask API tidak tersedia, menggunakan fallback Laravel service');
        }
    }

    /**
     * Start a new test session
     */
    public function startSession(): array
    {
        $this->performanceMonitor->logStartCAT();
        
        DB::beginTransaction();
        
        try {
            // Generate session ID
            $sessionId = 'CAT_' . time() . '_' . rand(1000, 9999);
            
            // Create new session
            $session = TestSession::create([
                'session_id' => $sessionId,
                'theta' => 0.0,
                'standard_error' => 1.0,
                'test_completed' => false
            ]);

            $this->performanceMonitor->logSelectNextItem();
            
            // Get first item
            if ($this->useFlaskApi) {
                $itemData = $this->flaskApi->selectNextItem(0.0, [], []);
                $firstItem = ItemParameter::find($itemData['item']['id']);
                
                if (!$firstItem) {
                    throw new Exception('Item tidak ditemukan: ' . $itemData['item']['id']);
                }
                
                $probability = $itemData['probability'];
                $information = $itemData['fisher_information'];
                $expectedFisherInfo = $itemData['expected_fisher_information'];
            } else {
                // Fallback ke service original
                $firstItem = $this->originalCatService->selectNextItem(0.0, $sessionId);
                $probability = $this->originalCatService->probability(0.0, $firstItem->a, $firstItem->b, $firstItem->g, $firstItem->u);
                $information = $this->originalCatService->itemInformation(0.0, $firstItem->a, $firstItem->b, $firstItem->g, $firstItem->u);
                $expectedFisherInfo = $this->originalCatService->expectedFisherInformation($firstItem->a, $firstItem->b, $firstItem->g, $sessionId, $firstItem->u);
            }

            if (!$firstItem) {
                throw new Exception('No items available');
            }

            // Mark item as used
            UsedItem::create([
                'session_id' => $sessionId,
                'item_id' => $firstItem->id
            ]);

            DB::commit();

            return [
                'session_id' => $sessionId,
                'item' => $firstItem,
                'theta' => 0.0,
                'se' => 1.0,
                'item_number' => 1,
                'probability' => $probability,
                'information' => $information,
                'expected_fisher_information' => $expectedFisherInfo,
                'api_source' => $this->useFlaskApi ? 'flask' : 'laravel'
            ];
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('HybridCATService::startSession failed', [
                'error' => $e->getMessage(),
                'api_source' => $this->useFlaskApi ? 'flask' : 'laravel'
            ]);
            throw $e;
        }
    }

    /**
     * Submit response and get next item
     */
    public function submitResponse(string $sessionId, string $itemId, int $answer): array
    {
        $this->performanceMonitor->logCustomProcess('submit_response_start');
        
        DB::beginTransaction();
        
        try {
            $session = TestSession::where('session_id', $sessionId)->first();
            if (!$session) {
                throw new Exception('Session not found');
            }

            if ($session->test_completed) {
                throw new Exception('Test already completed');
            }

            $item = ItemParameter::find($itemId);
            if (!$item) {
                throw new Exception('Item not found');
            }

            // Get current response count
            $responseCount = TestResponse::where('session_id', $sessionId)->count();
            
            // Calculate metrics before response (untuk logging)
            $u = $item->u ?? 1.0;
            $probabilityBefore = $this->useFlaskApi ? 
                $this->calculateProbability($session->theta, $item) :
                $this->originalCatService->probability($session->theta, $item->a, $item->b, $item->g, $u);

            $informationBefore = $this->useFlaskApi ?
                $this->calculateInformation($session->theta, $item) :
                $this->originalCatService->itemInformation($session->theta, $item->a, $item->b, $item->g, $u);

            $this->performanceMonitor->logCustomProcess('store_response');
            
            // Store response
            TestResponse::create([
                'session_id' => $sessionId,
                'item_id' => $itemId,
                'answer' => $answer,
                'theta_before' => $session->theta,
                'theta_after' => $session->theta, // Will be updated below
                'se_after' => $session->standard_error, // Will be updated below
                'item_order' => $responseCount + 1,
                'probability' => $probabilityBefore,
                'information' => $informationBefore,
                'expected_fisher_information' => 0 // Will be calculated for next item
            ]);

            // Get all responses for theta estimation
            $responses = TestResponse::where('session_id', $sessionId)
                ->with('item')
                ->orderBy('item_order')
                ->get();

            $this->performanceMonitor->logEstimateThetaMAP();
            
            // Estimate new theta and SE using MAP for real-time estimation
            if ($this->useFlaskApi) {
                $flaskResponses = $this->flaskApi->convertResponsesToApiFormat($responses);
                // Kirim theta_old (theta terakhir sebelum estimasi) - Flask menggunakan MAP
                $thetaData = $this->flaskApi->estimateTheta($flaskResponses, $session->theta);
                $newTheta = $thetaData['theta'];
                $newSE = $thetaData['se'];  // MAP returns 'se'
                Log::info('MAP estimation (real-time)', [
                    'theta_map' => $newTheta,
                    'se_map' => $newSE,
                    'method' => $thetaData['method']
                ]);
            } else {
                [$newTheta, $newSE] = $this->originalCatService->estimateThetaAndSE($sessionId, $session->theta);
            }

            $this->performanceMonitor->logCustomProcess('update_session_data');
            
            // Update response with new theta and SE
            TestResponse::where('session_id', $sessionId)
                ->where('item_id', $itemId)
                ->update([
                    'theta_after' => $newTheta,
                    'se_after' => $newSE
                ]);

            // Update session
            $session->update([
                'theta' => $newTheta,
                'standard_error' => $newSE
            ]);

            // Get used items
            $usedItems = UsedItem::where('session_id', $sessionId)->pluck('item_id')->toArray();
            
            $this->performanceMonitor->logCustomProcess('check_stopping_criteria');
            
            // Check if test should stop
            if ($this->useFlaskApi) {
                try {
                    $flaskResponses = $this->flaskApi->convertResponsesToApiFormat($responses);
                    
                    // Use MAP SE for stopping criteria (since final-score endpoint may not be available)
                    $stopData = $this->flaskApi->checkStoppingCriteria($flaskResponses, $newSE, $usedItems);
                    $shouldStop = $stopData['should_stop'];
                    $stopReason = $stopData['reason'];
                    
                    Log::info('Stopping criteria check', [
                        'map_se' => $newSE,
                        'should_stop' => $shouldStop,
                        'reason' => $stopReason
                    ]);
                } catch (Exception $e) {
                    Log::error('Flask API stopping criteria failed, falling back to Laravel', [
                        'error' => $e->getMessage()
                    ]);
                    // Fallback to Laravel original service
                    [$shouldStop, $stopReason] = $this->originalCatService->shouldStopTest($sessionId, $newTheta, $newSE, $usedItems);
                }
            } else {
                [$shouldStop, $stopReason] = $this->originalCatService->shouldStopTest($sessionId, $newTheta, $newSE, $usedItems);
            }

            if ($shouldStop) {
                $this->performanceMonitor->logCustomProcess('calculate_final_score');
                
                if ($this->useFlaskApi) {
                    try {
                        // Try to use EAP for final scoring
                        $flaskResponses = $this->flaskApi->convertResponsesToApiFormat($responses);
                        $finalScoreData = $this->flaskApi->calculateFinalScore($flaskResponses);
                        $finalTheta = $finalScoreData['theta'];
                        $finalSE = $finalScoreData['se_eap'];
                        $finalScore = $finalScoreData['final_score'];
                        
                        $this->performanceMonitor->logCustomProcess('estimate_theta_EAP');
                        
                        Log::info('EAP final scoring', [
                            'theta_eap' => $finalTheta,
                            'se_eap' => $finalSE,
                            'final_score' => $finalScore,
                            'method' => $finalScoreData['method']
                        ]);
                    } catch (Exception $e) {
                        Log::warning('Flask API final scoring failed, using MAP values', [
                            'error' => $e->getMessage(),
                            'fallback_theta' => $newTheta,
                            'fallback_se' => $newSE
                        ]);
                        // Fallback to MAP values with Laravel scoring
                        $finalScore = $this->originalCatService->calculateScore($newTheta);
                        $finalTheta = $newTheta;
                        $finalSE = $newSE;
                    }
                } else {
                    $finalScore = $this->originalCatService->calculateScore($newTheta);
                    $finalTheta = $newTheta;
                    $finalSE = $newSE;
                }
                
                $session->update([
                    'test_completed' => true,
                    'stop_reason' => $stopReason,
                    'theta' => $finalTheta,  // Store final EAP theta
                    'standard_error' => $finalSE,  // Store final EAP SE
                    'final_score' => $finalScore
                ]);

                DB::commit();

                return [
                    'test_completed' => true,
                    'theta' => $finalTheta,
                    'se' => $finalSE,
                    'final_score' => $finalScore,
                    'stop_reason' => $stopReason,
                    'total_items' => $responseCount + 1,
                    'api_source' => $this->useFlaskApi ? 'flask' : 'laravel'
                ];
            }

            // Get next item
            $this->performanceMonitor->logSelectNextItem();
            
            if ($this->useFlaskApi) {
                $flaskResponses = $this->flaskApi->convertResponsesToApiFormat($responses);
                $itemData = $this->flaskApi->selectNextItem($newTheta, $usedItems, $flaskResponses);
                $nextItem = ItemParameter::find($itemData['item']['id']);
                
                if (!$nextItem) {
                    throw new Exception('Next item tidak ditemukan: ' . $itemData['item']['id']);
                }
                
                $probability = $itemData['probability'];
                $information = $itemData['fisher_information'];
                $expectedFisherInfo = $itemData['expected_fisher_information'];
            } else {
                $nextItem = $this->originalCatService->selectNextItem($newTheta, $sessionId);
                
                if (!$nextItem) {
                    // No more items available - calculate final score using EAP
                    $this->performanceMonitor->logCustomProcess('no_more_items_final_score');
                    
                    if ($this->useFlaskApi) {
                        try {
                            $flaskResponses = $this->flaskApi->convertResponsesToApiFormat($responses);
                            $finalScoreData = $this->flaskApi->calculateFinalScore($flaskResponses);
                            $finalTheta = $finalScoreData['theta'];
                            $finalSE = $finalScoreData['se_eap'];
                            $finalScore = $finalScoreData['final_score'];
                            
                            Log::info('EAP final scoring (no more items)', [
                                'theta_eap' => $finalTheta,
                                'se_eap' => $finalSE,
                                'final_score' => $finalScore,
                                'method' => $finalScoreData['method']
                            ]);
                        } catch (Exception $e) {
                            Log::warning('Flask API final scoring failed (no more items), using MAP values', [
                                'error' => $e->getMessage(),
                                'fallback_theta' => $newTheta,
                                'fallback_se' => $newSE
                            ]);
                            // Fallback to MAP values with Laravel scoring
                            $finalScore = $this->originalCatService->calculateScore($newTheta);
                            $finalTheta = $newTheta;
                            $finalSE = $newSE;
                        }
                    } else {
                        $finalScore = $this->originalCatService->calculateScore($newTheta);
                        $finalTheta = $newTheta;
                        $finalSE = $newSE;
                    }
                    
                    $session->update([
                        'test_completed' => true,
                        'stop_reason' => 'No more items available',
                        'theta' => $finalTheta,  // Store final EAP theta
                        'standard_error' => $finalSE,  // Store final EAP SE
                        'final_score' => $finalScore
                    ]);
                    
                    DB::commit();
                    
                    return [
                        'test_completed' => true,
                        'theta' => $finalTheta,
                        'se' => $finalSE,
                        'final_score' => $finalScore,
                        'stop_reason' => 'No more items available',
                        'total_items' => $responseCount + 1,
                        'api_source' => $this->useFlaskApi ? 'flask' : 'laravel'
                    ];
                }
                
                $probability = $this->originalCatService->probability($newTheta, $nextItem->a, $nextItem->b, $nextItem->g, $nextItem->u);
                $information = $this->originalCatService->itemInformation($newTheta, $nextItem->a, $nextItem->b, $nextItem->g, $nextItem->u);
                $expectedFisherInfo = $this->originalCatService->expectedFisherInformation($nextItem->a, $nextItem->b, $nextItem->g, $sessionId, $nextItem->u);
            }

            // Mark next item as used
            UsedItem::create([
                'session_id' => $sessionId,
                'item_id' => $nextItem->id
            ]);

            $this->performanceMonitor->logCustomProcess('submit_response_complete');

            DB::commit();

            return [
                'test_completed' => false,
                'item' => $nextItem,
                'theta' => $newTheta,
                'se' => $newSE,
                'item_number' => $responseCount + 2,
                'probability' => $probability,
                'information' => $information,
                'expected_fisher_information' => $expectedFisherInfo,
                'api_source' => $this->useFlaskApi ? 'flask' : 'laravel'
            ];
            
        } catch (Exception $e) {
            DB::rollback();
            $this->performanceMonitor->logCustomProcess('submit_response_error');
            Log::error('HybridCATService::submitResponse failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'item_id' => $itemId,
                'answer' => $answer,
                'api_source' => $this->useFlaskApi ? 'flask' : 'laravel'
            ]);
            throw $e;
        }
    }

    /**
     * Get session history
     */
    public function getSessionHistory(string $sessionId): array
    {
        $this->performanceMonitor->logCustomProcess('get_session_history');
        // History tidak memerlukan Flask API, langsung dari database
        return $this->originalCatService->getSessionHistory($sessionId);
    }

    /**
     * Helper method untuk menghitung probability (fallback jika Flask API tidak tersedia)
     */
    private function calculateProbability(float $theta, ItemParameter $item): float
    {
        // Implementasi 3PL sederhana untuk backup
        $g = $item->g;
        $u = $item->u ?? 1.0;
        $a = $item->a;
        $b = $item->b;
        
        return $g + ($u - $g) / (1 + exp(-$a * ($theta - $b)));
    }

    /**
     * Helper method untuk menghitung information (fallback jika Flask API tidak tersedia)
     */
    private function calculateInformation(float $theta, ItemParameter $item): float
    {
        $p = $this->calculateProbability($theta, $item);
        $q = 1 - $p;
        $g = $item->g;
        $u = $item->u ?? 1.0;
        $a = $item->a;
        
        if ($p <= $g || $p >= $u || ($u - $g) == 0) {
            return 0;
        }
        
        $numerator = pow($a, 2) * pow($p - $g, 2) * $q;
        $denominator = $p * pow($u - $g, 2);
        
        return $numerator / $denominator;
    }

    /**
     * Get current API source
     */
    public function getApiSource(): string
    {
        return $this->useFlaskApi ? 'flask' : 'laravel';
    }

    /**
     * Force switch to Flask API (untuk testing)
     */
    public function forceFlaskApi(bool $force = true): void
    {
        if ($force && $this->flaskApi->testConnection()) {
            $this->useFlaskApi = true;
        } else {
            $this->useFlaskApi = false;
        }
    }

    /**
     * Get Flask API health status
     */
    public function getFlaskApiHealth(): array
    {
        try {
            return $this->flaskApi->healthCheck();
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'total_items' => 0,
                'api_version' => 'unknown'
            ];
        }
    }
}
