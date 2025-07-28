<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class TestController extends Controller
{
    public function simpleTest(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Laravel is working',
            'timestamp' => now(),
            'database' => config('database.connections.mysql.database')
        ]);
    }
    
    public function testDatabase(): JsonResponse
    {
        try {
            $connection = DB::connection();
            $dbName = $connection->getDatabaseName();
            
            return response()->json([
                'status' => 'success',
                'database' => $dbName,
                'connection' => 'OK'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
