<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ItemParametersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('item_parameters')->delete();
        
        // Path to CSV file
        $csvFile = base_path('Parameter_Item_IST.csv');
        
        // Check if CSV file exists
        if (!File::exists($csvFile)) {
            $this->command->error('CSV file not found: ' . $csvFile);
            $this->command->info('Please place Parameter_Item_IST.csv in the root directory');
            return;
        }
        
        // Read CSV file
        $csvData = [];
        if (($handle = fopen($csvFile, 'r')) !== FALSE) {
            // Read header row
            $header = fgetcsv($handle);
            $this->command->info('CSV Header: ' . implode(', ', $header));
            
            // Read data rows
            $count = 0;
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 5) {
                    ItemParameter::create([
                        'id' => $data[0],           // ID
                        'a' => (float) $data[1],    // a (discrimination)
                        'b' => (float) $data[2],    // b (difficulty)
                        'g' => (float) $data[3],    // g (guessing)
                        'u' => (float) $data[4],    // u (upper asymptote)
                    ]);
                    $count++;
                } else {
                    $this->command->warn('Skipping invalid row: ' . implode(', ', $data));
                }
            }
            fclose($handle);
            
            $this->command->info("Successfully imported {$count} item parameters from CSV");
            
            // Show statistics
            $bValues = ItemParameter::pluck('b')->toArray();
            $bMax = max($bValues);
            $bMin = min($bValues);
            $bMean = array_sum($bValues) / count($bValues);
            
            $this->command->info("Item Statistics:");
            $this->command->info("- Total items: {$count}");
            $this->command->info("- b parameter range: {$bMin} to {$bMax}");
            $this->command->info("- b parameter mean: " . round($bMean, 3));
            
        } else {
            $this->command->error('Could not open CSV file');
        }
    }
}
