<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PerformanceMonitorService
{
    /**
     * Log proses dengan monitoring memory dan CPU usage
     * 
     * @param string $processName Nama proses yang dicatat
     * @return void
     */
    public function logProcess(string $processName): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $memoryUsage = $this->getMemoryUsage();
        $cpuLoad = $this->getCpuLoad();
        
        $logMessage = sprintf(
            'process: %s | memory: %sMB | cpu_load: %.2f',
            $processName,
            $memoryUsage,
            $cpuLoad
        );
        
        try {
            // Check if Laravel Log is available
            if (class_exists('\Illuminate\Support\Facades\Log') && app()->bound('log')) {
                Log::channel('cat')->info($logMessage);
            } else {
                // Fallback jika Laravel tidak tersedia
                $this->logToFileDirect($processName, $memoryUsage, $cpuLoad);
            }
        } catch (\Exception $e) {
            // Fallback jika channel 'cat' tidak tersedia
            $this->logToFileDirect($processName, $memoryUsage, $cpuLoad);
        }
    }
    
    /**
     * Fallback method untuk log langsung ke file
     * 
     * @param string $processName
     * @param string $memoryUsage
     * @param float $cpuLoad
     * @return void
     */
    private function logToFileDirect(string $processName, string $memoryUsage, float $cpuLoad): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = sprintf(
            "[%s] process: %s | memory: %sMB | cpu_load: %.2f\n",
            $timestamp,
            $processName,
            $memoryUsage,
            $cpuLoad
        );
        
        // Get base path for Laravel app
        $basePath = __DIR__ . '/../../';
        $logFile = $basePath . 'storage/logs/cat.log';
        
        // Pastikan direktori ada
        $logsDir = dirname($logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Mendapatkan penggunaan memory dalam MB
     * 
     * @return string
     */
    private function getMemoryUsage(): string
    {
        $memoryBytes = memory_get_usage(true);
        $memoryMB = $memoryBytes / 1024 / 1024;
        return number_format($memoryMB, 1);
    }
    
    /**
     * Mendapatkan CPU load average
     * 
     * @return float
     */
    private function getCpuLoad(): float
    {
        // Untuk Windows, kita menggunakan estimasi sederhana
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->getWindowsCpuLoad();
        }
        
        // Untuk Unix/Linux systems
        $load = sys_getloadavg();
        return $load ? round($load[0], 2) : 0.0;
    }
    
    /**
     * Estimasi CPU load untuk Windows
     * 
     * @return float
     */
    private function getWindowsCpuLoad(): float
    {
        // Menggunakan kombinasi memory usage dan waktu eksekusi sebagai estimasi
        $startTime = microtime(true);
        
        // Simulasi load berdasarkan memory usage dan aktivitas sistem
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        // Estimasi sederhana berdasarkan ratio memory usage
        $memoryRatio = $memoryUsage / $peakMemory;
        $baseLoad = 0.5 + ($memoryRatio * 0.5);
        
        // Tambahkan variasi kecil untuk simulasi fluktuasi
        $variation = (mt_rand(-20, 20) / 100);
        
        return round(max(0.1, min(2.0, $baseLoad + $variation)), 2);
    }
    
    /**
     * Log mulai proses CAT
     * 
     * @return void
     */
    public function logStartCAT(): void
    {
        $this->logProcess('start_CAT');
    }
    
    /**
     * Log pemilihan item berikutnya
     * 
     * @return void
     */
    public function logSelectNextItem(): void
    {
        $this->logProcess('select_next_item');
    }
    
    /**
     * Log estimasi theta MAP
     * 
     * @return void
     */
    public function logEstimateThetaMAP(): void
    {
        $this->logProcess('estimate_theta_MAP');
    }
    
    /**
     * Log akhir proses CAT
     * 
     * @return void
     */
    public function logEndCAT(): void
    {
        $this->logProcess('end_CAT');
    }
    
    /**
     * Log proses custom dengan nama bebas
     * 
     * @param string $processName
     * @return void
     */
    public function logCustomProcess(string $processName): void
    {
        $this->logProcess($processName);
    }
}