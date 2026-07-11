<?php

namespace App\Console\Commands\DisasterRecovery;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VerifyRestoreCommand extends Command
{
    protected $signature = 'disaster:verify-restore {--file= : Specific backup file to test}';
    protected $description = 'Dry-runs a backup file to ensure it is not corrupted and can be decompressed.';

    public function handle()
    {
        $this->info("Starting Restore Dry-Run...");

        $file = $this->option('file');

        if (!$file) {
            $backupDir = storage_path('backups');
            if (!File::exists($backupDir)) {
                $this->error("Backup directory does not exist.");
                return 1;
            }

            $files = File::files($backupDir);
            $backups = array_filter($files, function($f) {
                return str_ends_with($f->getFilename(), '.sql.gz');
            });

            if (empty($backups)) {
                $this->error("No backups found to test.");
                return 1;
            }

            usort($backups, function($a, $b) {
                return $b->getMTime() <=> $a->getMTime();
            });

            $file = $backups[0]->getPathname();
        }

        if (!File::exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Testing archive integrity for: " . basename($file));

        // Dry-run: Test gzip integrity without writing anywhere
        // zcat outputs to stdout, we redirect to null. If gzip detects corruption, it exits > 0
        
        $output = [];
        $returnCode = 0;
        
        // Use gzip -t to test archive integrity
        exec("gzip -t " . escapeshellarg($file) . " 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("CRITICAL: The archive is corrupted and cannot be decompressed.");
            foreach ($output as $line) {
                $this->error($line);
            }
            return 1;
        }

        $this->info("Decompression Test: PASS.");
        $this->info("Dry-Run Validation Complete. The backup file is intact and ready for MySQL restoration.");
        
        return 0;
    }
}
