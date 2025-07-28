<?php

namespace App\Services;

use App\Models\ItemParameter;
use App\Models\TestSession;
use App\Models\TestResponse;
use App\Models\UsedItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * CAT Service - Fallback service untuk ketika Flask API tidak tersedia
 * Implementasi minimal untuk IRT calculations
 */
class CATService {
    // ...existing code...
    /**
     * Calculate Expected Fisher Information (EFI) for one item
     */
    public function expectedFisherInformation(float $a, float $b, float $g, string $sessionId, float $u = 1.0): float
    {
        // Get responses for session
        $responses = TestResponse::where('session_id', $sessionId)
            ->join('item_parameters', 'test_responses.item_id', '=', 'item_parameters.id')
            ->select('test_responses.*', 'item_parameters.a', 'item_parameters.b', 'item_parameters.g', 'item_parameters.u')
            ->get();

        // Grid theta
        $thetaRange = range(-4, 4, 0.1);
        $prior = [];
        foreach ($thetaRange as $theta) {
            $prior[] = exp(-0.5 * $theta * $theta) / sqrt(2 * M_PI); // N(0,1)
        }
        $sumPrior = array_sum($prior);
        foreach ($prior as $k => $v) {
            $prior[$k] = $v / $sumPrior;
        }

        // Likelihood
        $likelihood = array_fill(0, count($thetaRange), 1.0);
        foreach ($responses as $response) {
            foreach ($thetaRange as $i => $theta) {
                $prob = $this->probability($theta, $response->a, $response->b, $response->g, $response->u ?? 1.0);
                $likelihood[$i] *= $response->answer ? $prob : (1 - $prob);
            }
        }

        // Posterior
        $posterior = [];
        $sumPosterior = 0.0;
        foreach ($thetaRange as $i => $theta) {
            $posterior[$i] = $likelihood[$i] * $prior[$i];
            $sumPosterior += $posterior[$i];
        }
        if ($sumPosterior > 0) {
            foreach ($posterior as $k => $v) {
                $posterior[$k] = $v / $sumPosterior;
            }
        } else {
            $posterior = $prior;
        }

        // EFI
        $efi = 0.0;
        foreach ($thetaRange as $i => $theta) {
            $info = $this->itemInformation($theta, $a, $b, $g, $u);
            $efi += $info * $posterior[$i];
        }
        return $efi;
    }
    /**
     * Start a new test session
     */
    public function startSession(): array
    {
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

            // Select first item (highest information at theta=0)
            $firstItem = $this->selectNextItem(0.0, $sessionId);
            
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
                'api_source' => 'laravel'
            ];
            
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Select next item using Fisher Information
     */
    public function selectNextItem(float $theta, string $sessionId): ?ItemParameter
    {
        $usedItems = UsedItem::where('session_id', $sessionId)->pluck('item_id')->toArray();
        
        $items = ItemParameter::whereNotIn('id', $usedItems)
            ->get()
            ->map(function ($item) use ($theta) {
                $item->information = $this->itemInformation($theta, $item->a, $item->b, $item->g, $item->u ?? 1.0);
                return $item;
            })
            ->sortByDesc('information')
            ->first();

        return $items;
    }

    /**
     * Estimate theta using simple EAP
     */
    public function estimateThetaAndSE(string $sessionId, float $currentTheta = 0.0): array
    {
        $responses = TestResponse::where('session_id', $sessionId)
            ->join('item_parameters', 'test_responses.item_id', '=', 'item_parameters.id')
            ->select('test_responses.*', 'item_parameters.a', 'item_parameters.b', 'item_parameters.g', 'item_parameters.u')
            ->get();

        if ($responses->isEmpty()) {
            return [0.0, 1.0];
        }

        // Simple EAP estimation
        $thetaRange = range(-4, 4, 0.1);
        $posteriorValues = [];
        
        foreach ($thetaRange as $theta) {
            $likelihood = 1.0;
            foreach ($responses as $response) {
                $prob = $this->probability($theta, $response->a, $response->b, $response->g, $response->u ?? 1.0);
                $likelihood *= $response->answer ? $prob : (1 - $prob);
            }
            $posteriorValues[] = $likelihood * $this->priorDensity($theta);
        }
        
        // Find MAP estimate
        $maxIndex = array_search(max($posteriorValues), $posteriorValues);
        $thetaEstimate = $thetaRange[$maxIndex];
        
        // Calculate SE (simplified)
        $se = 1.0 / sqrt(max(0.1, $this->testInformation($thetaEstimate, $responses)));
        
        return [$thetaEstimate, $se];
    }

    /**
     * Calculate probability using 3PL model
     */
    public function probability(float $theta, float $a, float $b, float $g, float $u = 1.0): float
    {
        return $g + ($u - $g) / (1 + exp(-$a * ($theta - $b)));
    }

    /**
     * Calculate item information
     */
    public function itemInformation(float $theta, float $a, float $b, float $g, float $u = 1.0): float
    {
        $prob = $this->probability($theta, $a, $b, $g, $u);
        $q = 1 - $prob;
        
        if ($prob <= 0 || $prob >= 1 || $q <= 0) {
            return 0.0;
        }
        
        $dProb = $a * ($u - $g) * exp(-$a * ($theta - $b)) / pow(1 + exp(-$a * ($theta - $b)), 2);
        
        return ($dProb * $dProb) / ($prob * $q);
    }

    /**
     * Calculate test information
     */
    public function testInformation(float $theta, $responses): float
    {
        $totalInfo = 0.0;
        foreach ($responses as $response) {
            $totalInfo += $this->itemInformation($theta, $response->a, $response->b, $response->g, $response->u ?? 1.0);
        }
        return $totalInfo;
    }

    /**
     * Check stopping criteria
     */
    public function shouldStopTest(string $sessionId, float $theta, float $se, array $usedItems): array
    {
        $itemCount = count($usedItems);
        
        // Stop if SE is low enough
        if ($se < 0.3) {
            return [true, 'SE threshold reached'];
        }
        
        // Stop if maximum items reached
        if ($itemCount >= 30) {
            return [true, 'Maximum items reached'];
        }
        
        // Stop if no more items available
        $availableItems = ItemParameter::whereNotIn('id', $usedItems)->count();
        if ($availableItems == 0) {
            return [true, 'No more items available'];
        }
        
        return [false, null];
    }

    /**
     * Calculate final score
     */
    public function calculateScore(float $theta): float
    {
        // Simple linear transformation: theta to 0-100 scale
        return max(0, min(100, 50 + ($theta * 15)));
    }

    /**
     * Get session history
     */
    public function getSessionHistory(string $sessionId): array
    {
        $session = TestSession::where('session_id', $sessionId)->first();
        if (!$session) {
            throw new Exception('Session not found');
        }

        $responses = TestResponse::where('session_id', $sessionId)
            ->join('item_parameters', 'test_responses.item_id', '=', 'item_parameters.id')
            ->select('test_responses.*', 'item_parameters.a', 'item_parameters.b', 'item_parameters.g')
            ->orderBy('item_order')
            ->get();

        return [
            'session' => $session,
            'responses' => $responses,
            'total_items' => $responses->count(),
            'api_source' => 'laravel'
        ];
    }

    /**
     * Prior density (standard normal)
     */
    private function priorDensity(float $theta): float
    {
        return exp(-0.5 * $theta * $theta) / sqrt(2 * M_PI);
    }
}
