# 🔧 API Documentation - CAT Flask API

## Base URL
```
http://127.0.0.1:5000
```

## Authentication
No authentication required for development. API menggunakan CORS untuk keamanan.

## Response Format

### Success Response
```json
{
    "data": {},
    "status": "success"
}
```

### Error Response
```json
{
    "error": "Error message",
    "status": "error"
}
```

## Endpoints

### 1. Health Check

**GET** `/health`

Check server status and basic information.

**Response:**
```json
{
    "status": "healthy",
    "version": "1.0.0",
    "timestamp": "2025-07-28T10:30:00.000Z",
    "service": "CAT Flask API"
}
```

---

### 2. Estimate Theta

**POST** `/api/estimate-theta`

Estimate theta (ability) menggunakan EAP method berdasarkan responses.

**Request Body:**
```json
{
    "responses": [
        {
            "a": 1.5,        // Discrimination parameter
            "b": -0.5,       // Difficulty parameter  
            "g": 0.2,        // Guessing parameter
            "u": 1.0,        // Upper asymptote (optional, default=1.0)
            "answer": 1      // Response: 1=correct, 0=incorrect
        }
    ],
    "theta_old": 0.0         // Previous theta estimate (optional, default=0.0)
}
```

**Response:**
```json
{
    "theta": 0.25,           // New theta estimate
    "se": 0.45,             // Standard error of theta
    "method": "EAP",         // Estimation method used
    "n_responses": 1,        // Number of responses processed
    "theta_old": 0.0        // Previous theta value
}
```

---

### 3. Select Item

**POST** `/api/select-item`

Select next item menggunakan Expected Fisher Information (EFI) method.

**Request Body:**
```json
{
    "theta": 0.25,           // Current theta estimate
    "used_item_ids": ["1", "2"],  // Array of used item IDs
    "responses": [           // Previous responses (optional, for EFI calculation)
        {
            "a": 1.5,
            "b": -0.5,
            "g": 0.2,
            "answer": 1
        }
    ]
}
```

**Response:**
```json
{
    "item": {
        "id": "3",
        "a": 1.2,
        "b": 0.3,
        "g": 0.15,
        "u": 1.0
    },
    "probability": 0.67,     // P(correct) for this theta
    "information": 0.45,     // Fisher Information
    "expected_fisher_information": 0.52,  // EFI value
    "method": "EFI",         // Selection method
    "available_items": 174   // Remaining items
}
```

---

### 4. Calculate Score

**POST** `/api/calculate-score`

Convert theta estimate to final score using IQ scale.

**Request Body:**
```json
{
    "theta": 0.5             // Theta estimate
}
```

**Response:**
```json
{
    "score": 107.5,          // Final score (IQ scale)
    "theta": 0.5,            // Input theta
    "scale": "IQ-based (100 + 15*theta)"  // Scoring formula
}
```

**Score Formula:**
```
Score = 100 + (15 × theta)
```

| Theta | Score | Interpretation |
|-------|-------|----------------|
| -2.0  | 70    | Well below average |
| -1.0  | 85    | Below average |
| 0.0   | 100   | Average |
| 1.0   | 115   | Above average |
| 2.0   | 130   | Well above average |

---

### 5. Stopping Criteria

**POST** `/api/stopping-criteria`

Check if test should stop based on predefined criteria.

**Request Body:**
```json
{
    "responses": [...],      // Array of all responses
    "se_eap": 0.3,          // Current Standard Error
    "used_item_ids": ["1", "2", "3"],  // Used item IDs
    "max_items": 30,        // Maximum items (optional, default=30)
    "se_threshold": 0.25    // SE threshold (optional, default=0.25)
}
```

**Response:**
```json
{
    "should_stop": false,    // Whether test should stop
    "reason": "Continuing",  // Reason for stop/continue
    "items_administered": 3, // Items given so far
    "max_items": 30,        // Maximum allowed
    "current_se": 0.3,      // Current SE
    "se_threshold": 0.25    // SE threshold
}
```

**Stopping Reasons:**
- `"SE_EAP mencapai 0.25 dengan minimal 10 soal"`
- `"Mencapai maksimal 30 soal"`
- `"Semua item telah digunakan"`
- `"Peserta sudah mendapat soal dengan b maksimum (paling sulit)"`
- `"Peserta sudah mendapat soal dengan b minimum (paling mudah)"`

---

### 6. Item Bank Info

**GET** `/api/item-bank`

Get information about the loaded item bank.

**Response:**
```json
{
    "items": [
        {
            "id": "1",
            "a": 1.5,
            "b": -0.5,
            "g": 0.2,
            "u": 1.0
        }
    ],
    "count": 176,            // Total items in bank
    "parameters": ["a", "b", "g", "u"],  // Available parameters
    "model": "3PL",          // IRT model used
    "source": "Parameter_Item_IST.csv"   // Data source
}
```

---

### 7. Test Calculation

**POST** `/api/test-calculation`

Test endpoint untuk debugging dan verification calculations.

**Request Body:**
```json
{}
```

**Response:**
```json
{
    "test_data": {
        "responses": [...],      // Sample test responses
        "theta_eap": 0.25,      // Calculated theta
        "se_eap": 0.45,         // Standard error
        "next_item": {...},     // Selected next item
        "probability": 0.67,    // Response probability
        "information": 0.45,    // Fisher information
        "expected_fisher_information": 0.52,  // EFI
        "score": 103.75,        // Final score
        "should_stop": false,   // Stop recommendation
        "stop_reason": "Continuing"  // Stop reason
    },
    "csv_status": "✓ Loaded 176 items from CSV",
    "status": "Test calculation completed successfully"
}
```

## Error Codes

| Code | Description | Possible Causes |
|------|-------------|-----------------|
| 400  | Bad Request | Invalid JSON, missing required fields |
| 404  | Not Found | No items available for selection |
| 500  | Internal Server Error | Calculation error, server issue |

## Example Usage (JavaScript)

### Estimate Theta
```javascript
const response = await fetch('http://127.0.0.1:5000/api/estimate-theta', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        responses: [
            {a: 1.5, b: -0.5, g: 0.2, answer: 1}
        ],
        theta_old: 0.0
    })
});

const data = await response.json();
console.log('New theta:', data.theta);
```

### Select Next Item
```javascript
const response = await fetch('http://127.0.0.1:5000/api/select-item', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        theta: 0.25,
        used_item_ids: ['1', '2'],
        responses: [...]
    })
});

const data = await response.json();
console.log('Next item:', data.item);
```

## Example Usage (PHP/Laravel)

### Estimate Theta
```php
$client = new \GuzzleHttp\Client();

$response = $client->post('http://127.0.0.1:5000/api/estimate-theta', [
    'json' => [
        'responses' => [
            ['a' => 1.5, 'b' => -0.5, 'g' => 0.2, 'answer' => 1]
        ],
        'theta_old' => 0.0
    ]
]);

$data = json_decode($response->getBody(), true);
$newTheta = $data['theta'];
```

## Performance Notes

### Grid Points Optimization
Current implementation uses 1001 quadrature points for high accuracy but slower performance.

For 5x speed improvement:
```python
# Change in cat_api.py:
# From: theta_range = np.linspace(-6, 6, 1001)
# To:   theta_range = np.linspace(-6, 6, 201)
```

### Rate Limiting
No rate limiting implemented. For production, consider adding rate limiting.

### CORS Configuration
API configured to accept requests from:
- `http://localhost:8000` (Laravel dev)
- `http://127.0.0.1:8000`
- Production domains (update as needed)

## Development Tips

1. **Health Check First**: Always check `/health` to ensure API is running
2. **Test Endpoint**: Use `/api/test-calculation` for quick verification
3. **Logging**: Check `cat_api.log` for detailed request/response logs
4. **Error Handling**: Always handle HTTP errors and JSON parsing errors
5. **Theta Range**: Keep theta estimates within reasonable range (-6 to 6)

## Integration with Laravel

The Laravel frontend already includes service classes to integrate with this API:

```php
// In Laravel Controller
$catService = app(\App\Services\HybridCATService::class);
$result = $catService->estimateTheta($responses, $thetaOld);
```

**Ready to integrate! Use this documentation for seamless API integration! 🚀**
