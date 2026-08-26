<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Facades\Log;

class ImportInnovatorsToFirestore extends Command
{
    protected $signature = 'import:innovators {file? : Path to CSV file}';
    protected $description = 'Import innovators data from CSV to Firestore';

    public function handle()
    {
        $filePath = $this->argument('file') ?? storage_path('app/innovators.csv');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Starting import from: {$filePath}");

        try {
            $firestore = Firebase::firestore();
            $database = $firestore->database();
            $collection = $database->collection('innovators');

            $rows = SimpleExcelReader::create($filePath)->getRows();
            $batch = $database->batch();
            $batchSize = 0;
            $total = 0;
            $errors = [];

            foreach ($rows as $index => $row) {

                if ($index === 0 && isset($row['name']) && $row['name'] === 'name') {
                    continue;
                }
                $documentData = $this->mapRowToDocument($row);

                $docId = $this->generateSlug($documentData['name']);

                $docRef = $collection->document($docId);

                // Check if document exists
                $snapshot = $docRef->snapshot();
                if ($snapshot->exists()) {
                    $this->warn("Document {$docId} already exists, updating...");
                }

                $batch->set($docRef, $documentData);
                $batchSize++;
                $total++;

                if ($batchSize >= 500) {
                    $this->commitBatch($batch, $database);
                    $batch = $database->batch();
                    $batchSize = 0;
                    $this->info("Processed {$total} records...");
                }
            }

            if ($batchSize > 0) {
                $this->commitBatch($batch, $database);
            }

            $this->info("Successfully imported {$total} innovators!");
            $this->info("Total errors: " . count($errors));

            return 0;

        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            Log::error("Firestore import failed", ['error' => $e->getMessage()]);
            return 1;
        }
    }

    private function mapRowToDocument(array $row): array
    {
        $bio = $row['bio'] ?? '';
        $bio = str_replace("\n", ' ', $bio);
        $bio = preg_replace('/\s+/', ' ', $bio);

        return [
            'name' => trim($row['name'] ?? ''),
            'occupation' => trim($row['occupation'] ?? ''),
            'bio' => trim($bio),
            'achievements' => trim($row['achievements'] ?? ''),
            'image' => trim($row['image'] ?? ''),
            'slug' => $this->generateSlug($row['name'] ?? ''),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }

    private function commitBatch($batch, $database)
    {
        try {
            $batch->commit();
        } catch (\Exception $e) {
            Log::error("Batch commit failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
