<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Flask API Client untuk mengakses service Python CAT
 * 
 * Menangani komunikasi dengan Flask API untuk:
 * - Estimasi theta dan SE
 * - Pemilihan item berikutnya
 * - Kalkulasi skor final
 * - Cek kriteria penghentian
 */
class FlaskApiService
{
    private $baseUrl;
    private $timeout;
    
    public function __construct()
    {
        $this->baseUrl = config('cat.flask_api_url', 'http://localhost:5000');
        $this->timeout = config('cat.flask_api_timeout', 30);
    }

    /**
     * Estimasi theta dan SE berdasarkan responses
     * 
     * @param array $responses Array responses dalam format:
     *   - API format: [{'a': 1.5, 'b': -1.0, 'g': 0.2, 'answer': 1}, ...]
     *   - GUI format: [{'item': {'a': 1.5, 'b': -1.0, 'g': 0.2}, 'answer': 1}, ...]
     * 
     * @return array ['theta' => float, 'se_eap' => float, 'num_responses' => int]
     * @throws Exception
     */
    public function estimateTheta(array $responses, float $thetaOld = 0.0): array
    {
        try {
            Log::info('FlaskApiService::estimateTheta POST', [
                'endpoint' => $this->baseUrl . '/api/estimate-theta',
                'theta_old' => $thetaOld,
                'responses_count' => count($responses),
                'responses' => $responses
            ]);
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/estimate-theta', [
                    'responses' => $responses,
                    'theta_old' => $thetaOld
                ]);

            if ($response->failed()) {
                throw new Exception('Flask API error: ' . $response->body());
            }

            $data = $response->json();
            // Validasi response
            if (!isset($data['theta']) || !isset($data['se'])) {
                throw new Exception('Invalid response from Flask API: missing theta or se');
            }

            return [
                'theta' => (float) $data['theta'],
                'se' => (float) $data['se'],  // MAP returns 'se', not 'se_eap'
                'se_eap' => (float) $data['se'],  // For compatibility
                'method' => (string) ($data['method'] ?? 'MAP'),
                'num_responses' => (int) ($data['n_responses'] ?? count($responses))
            ];
        } catch (Exception $e) {
            Log::error('FlaskApiService::estimateTheta failed', [
                'error' => $e->getMessage(),
                'responses_count' => count($responses)
            ]);
            throw $e;
        }
    }

    /**
     * Pilih item berikutnya berdasarkan EFI
     * 
     * @param float $theta Current theta estimate
     * @param array $usedItemIds Array of used item IDs
     * @param array $responses Optional: responses untuk better EFI calculation
     * 
     * @return array ['item' => array, 'probability' => float, 'fisher_information' => float, 'expected_fisher_information' => float]
     * @throws Exception
     */
    public function selectNextItem(float $theta, array $usedItemIds, array $responses = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/select-item', [
                    'theta' => $theta,
                    'used_item_ids' => $usedItemIds,
                    'responses' => $responses
                ]);

            if ($response->failed()) {
                throw new Exception('Flask API error: ' . $response->body());
            }

            $data = $response->json();
            
            // Validasi response
            if (!isset($data['item'])) {
                throw new Exception('Invalid response from Flask API: missing item');
            }

            return [
                'item' => $data['item'],
                'probability' => (float) ($data['probability'] ?? 0),
                'fisher_information' => (float) ($data['fisher_information'] ?? 0),
                'expected_fisher_information' => (float) ($data['expected_fisher_information'] ?? 0)
            ];
            
        } catch (Exception $e) {
            Log::error('FlaskApiService::selectNextItem failed', [
                'error' => $e->getMessage(),
                'theta' => $theta,
                'used_items_count' => count($usedItemIds)
            ]);
            throw $e;
        }
    }

    /**
     * Kalkulasi skor final dari theta
     * 
     * @param float $theta Theta estimate
     * @return array ['theta' => float, 'final_score' => float]
     * @throws Exception
     */
    public function calculateScore(float $theta): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/calculate-score', [
                    'theta' => $theta
                ]);

            if ($response->failed()) {
                throw new Exception('Flask API error: ' . $response->body());
            }

            $data = $response->json();
            
            // Validasi response
            if (!isset($data['score'])) {
                throw new Exception('Invalid response from Flask API: missing score');
            }

            return [
                'theta' => (float) ($data['theta'] ?? $theta),
                'final_score' => (float) $data['score']
            ];
            
        } catch (Exception $e) {
            Log::error('FlaskApiService::calculateScore failed', [
                'error' => $e->getMessage(),
                'theta' => $theta
            ]);
            throw $e;
        }
    }

    /**
     * Kalkulasi skor akhir menggunakan EAP dari semua responses
     * 
     * @param array $responses Array responses untuk EAP final scoring
     * @return array ['theta' => float, 'se_eap' => float, 'final_score' => float, 'method' => string]
     * @throws Exception
     */
    public function calculateFinalScore(array $responses): array
    {
        try {
            // Validate input
            if (empty($responses)) {
                throw new Exception('Cannot calculate final score: no responses provided');
            }

            Log::info('FlaskApiService::calculateFinalScore called', [
                'responses_count' => count($responses),
                'first_response' => $responses[0] ?? null
            ]);

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/final-score', [
                    'responses' => $responses
                ]);

            if ($response->failed()) {
                $errorBody = $response->body();
                Log::error('Flask API final-score endpoint failed', [
                    'status' => $response->status(),
                    'body' => $errorBody
                ]);
                throw new Exception('Flask API error: ' . $errorBody);
            }

            $data = $response->json();
            
            // Validasi response
            if (!isset($data['theta']) || !isset($data['se_eap']) || !isset($data['final_score'])) {
                $missingFields = [];
                if (!isset($data['theta'])) $missingFields[] = 'theta';
                if (!isset($data['se_eap'])) $missingFields[] = 'se_eap';
                if (!isset($data['final_score'])) $missingFields[] = 'final_score';
                
                Log::error('Invalid Flask API response', [
                    'missing_fields' => $missingFields,
                    'response_data' => $data
                ]);
                throw new Exception('Invalid response from Flask API: missing ' . implode(', ', $missingFields));
            }

            $result = [
                'theta' => (float) $data['theta'],
                'se_eap' => (float) $data['se_eap'],
                'final_score' => (float) $data['final_score'],
                'method' => (string) ($data['method'] ?? 'EAP')
            ];

            Log::info('FlaskApiService::calculateFinalScore success', $result);
            return $result;
            
        } catch (Exception $e) {
            Log::error('FlaskApiService::calculateFinalScore failed', [
                'error' => $e->getMessage(),
                'responses_count' => count($responses),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Cek kriteria penghentian tes
     * 
     * @param array $responses Array responses
     * @param float $seEap Current SE_EAP
     * @param array $usedItemIds Array of used item IDs
     * 
     * @return array ['should_stop' => bool, 'reason' => string, 'num_responses' => int, 'se_eap' => float]
     * @throws Exception
     */
    public function checkStoppingCriteria(array $responses, float $seEap, array $usedItemIds): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/stopping-criteria', [
                    'responses' => $responses,
                    'se_eap' => $seEap,
                    'used_item_ids' => $usedItemIds
                ]);

            if ($response->failed()) {
                throw new Exception('Flask API error: ' . $response->body());
            }

            $data = $response->json();
            
            // Validasi response
            if (!isset($data['should_stop'])) {
                throw new Exception('Invalid response from Flask API: missing should_stop');
            }

            return [
                'should_stop' => (bool) $data['should_stop'],
                'reason' => (string) ($data['reason'] ?? ''),
                'num_responses' => (int) ($data['items_administered'] ?? count($responses)),
                'se_eap' => (float) ($data['current_se'] ?? $seEap)
            ];
            
        } catch (Exception $e) {
            Log::error('FlaskApiService::checkStoppingCriteria failed', [
                'error' => $e->getMessage(),
                'responses_count' => count($responses),
                'se_eap' => $seEap
            ]);
            throw $e;
        }
    }

    /**
     * Health check Flask API
     * 
     * @return array ['status' => string, 'total_items' => int, 'api_version' => string]
     * @throws Exception
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . '/health');

            if ($response->failed()) {
                throw new Exception('Flask API health check failed: ' . $response->body());
            }

            return $response->json();
            
        } catch (Exception $e) {
            Log::error('FlaskApiService::healthCheck failed', [
                'error' => $e->getMessage(),
                'base_url' => $this->baseUrl
            ]);
            throw $e;
        }
    }

    /**
     * Konversi TestResponse ke format Flask API
     * 
     * @param \App\Models\TestResponse $response
     * @return array Format API: ['a' => float, 'b' => float, 'g' => float, 'answer' => int]
     */
    public function convertResponseToApiFormat($response): array
    {
        return [
            'a' => (float) $response->item->a,
            'b' => (float) $response->item->b,
            'g' => (float) $response->item->g,
            'answer' => (int) $response->answer
        ];
    }

    /**
     * Konversi multiple TestResponse ke format Flask API
     * 
     * @param \Illuminate\Database\Eloquent\Collection $responses
     * @return array
     */
    public function convertResponsesToApiFormat($responses): array
    {
        return $responses->map(function ($response) {
            return $this->convertResponseToApiFormat($response);
        })->toArray();
    }

    /**
     * Test koneksi ke Flask API
     * 
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $this->healthCheck();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * DEPRECATED: Single API call untuk semua perhitungan CAT
     * Method ini tidak digunakan karena endpoint /process-response tidak ada di Flask API
     */
    /*
    public function processResponse(array $responses, string $sessionId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/process-response', [
                    'responses' => $responses,
                    'session_id' => $sessionId
                ]);

            if ($response->failed()) {
                throw new Exception('Flask API error: ' . $response->body());
            }

            $data = $response->json();
            
            Log::info('Flask API processResponse', [
                'session_id' => $sessionId,
                'responses_count' => count($responses),
                'theta' => $data['theta'],
                'se_eap' => $data['se_eap'],
                'should_stop' => $data['should_stop'],
                'stop_reason' => $data['stop_reason'] ?? null
            ]);

            return $data;
            
        } catch (Exception $e) {
            Log::error('FlaskApiService::processResponse failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'responses_count' => count($responses)
            ]);
            throw $e;
        }
    }
    */
}
