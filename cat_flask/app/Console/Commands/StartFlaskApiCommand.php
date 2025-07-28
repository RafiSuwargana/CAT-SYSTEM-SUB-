<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class StartFlaskApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cat:start-flask-api 
                           {--host=localhost : Host untuk Flask API}
                           {--port=5000 : Port untuk Flask API}
                           {--python=python : Python executable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Flask API server untuk perhitungan CAT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $python = $this->option('python');
        
        // Path ke file Flask API
        $flaskApiPath = base_path('cat_api.py');
        
        if (!file_exists($flaskApiPath)) {
            $this->error("Flask API file not found: {$flaskApiPath}");
            $this->info("Please ensure cat_api.py is in the project root directory");
            return 1;
        }
        
        // Check if Parameter_Item_IST.csv exists
        $csvPath = base_path('Parameter_Item_IST.csv');
        if (!file_exists($csvPath)) {
            $this->error("Parameter_Item_IST.csv not found: {$csvPath}");
            $this->info("Please ensure Parameter_Item_IST.csv is in the project root directory");
            return 1;
        }
        
        $this->info("Starting Flask API server...");
        $this->info("Host: {$host}");
        $this->info("Port: {$port}");
        $this->info("Python: {$python}");
        $this->info("Flask API: {$flaskApiPath}");
        $this->info("CSV Data: {$csvPath}");
        $this->newLine();
        
        // Test Python availability
        $this->info("Testing Python availability...");
        $pythonTest = new SymfonyProcess([$python, '--version']);
        $pythonTest->run();
        
        if (!$pythonTest->isSuccessful()) {
            $this->error("Python tidak tersedia atau error:");
            $this->error($pythonTest->getErrorOutput());
            return 1;
        }
        
        $this->info("Python version: " . trim($pythonTest->getOutput()));
        
        // Test Python dependencies
        $this->info("Testing Python dependencies...");
        $depsTest = new SymfonyProcess([$python, '-c', 'import flask, pandas, numpy, scipy; print("Dependencies OK")']);
        $depsTest->run();
        
        if (!$depsTest->isSuccessful()) {
            $this->error("Python dependencies tidak tersedia:");
            $this->error($depsTest->getErrorOutput());
            $this->info("Install dependencies with: pip install flask pandas numpy scipy");
            return 1;
        }
        
        $this->info("Dependencies: " . trim($depsTest->getOutput()));
        $this->newLine();
        
        // Set environment variables
        $env = [
            'FLASK_APP' => $flaskApiPath,
            'FLASK_ENV' => 'development',
            'FLASK_DEBUG' => '1'
        ];
        
        // Start Flask API
        $this->info("Starting Flask API server...");
        $this->info("Press Ctrl+C to stop the server");
        $this->newLine();
        
        $command = [$python, $flaskApiPath];
        
        // Run Flask API
        $process = new SymfonyProcess($command);
        $process->setEnv($env);
        $process->setTimeout(null); // No timeout
        $process->setWorkingDirectory(base_path());
        
        $process->run(function ($type, $buffer) {
            if (SymfonyProcess::ERR === $type) {
                $this->error($buffer);
            } else {
                $this->line($buffer);
            }
        });
        
        return $process->getExitCode();
    }
}
