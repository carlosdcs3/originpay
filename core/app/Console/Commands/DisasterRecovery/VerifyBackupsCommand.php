<?php

namespace App\Console\Commands\DisasterRecovery;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class VerifyBackupsCommand extends Command
{
    protected $signature = 'disaster:verify-backups';
    protected $description = 'Verifies the integrity, size, and freshness of the latest database backup.';

    public function handle()
    {
        $this->info("Starting Backup Verification...");

        $backupDir = storage_path('backups');
        if (!File::exists($backupDir)) {
            $this->error("Backup directory does not exist: {$backupDir}");
            return 1;
        }

        $files = File::files($backupDir);
        $backups = array_filter($files, function($file) {
            return str_ends_with($file->getFilename(), '.sql.gz');
        });

        if (empty($backups)) {
            $this->error("CRITICAL: No backup files found in {$backupDir}");
            return 1;
        }

        // Sort by modified time DESC
        usort($backups, function($a, $b) {
            return $b->getMTime() <=> $a->getMTime();
        });

        $latest = $backups[0];
        $this->info("Latest backup found: " . $latest->getFilename());

        // 1. Validate Date (Must be < 24h old)
        $ageHours = Carbon::createFromTimestamp($latest->getMTime())->diffInHours(now());
        if ($ageHours >= 24) {
            $this->error("CRITICAL: Latest backup is expired. Age: {$ageHours} hours.");
            return 1;
        }
        $this->info("Age check passed: {$ageHours} hours old.");

        // 2. Validate Size (Must be > 100KB for a minimum DB structure)
        $sizeKB = $latest->getSize() / 1024;
        if ($sizeKB < 100) {
            $this->error("CRITICAL: Backup size is suspiciously small: " . round($sizeKB, 2) . " KB.");
            return 1;
        }
        $this->info("Size check passed: " . round($sizeKB, 2) . " KB.");

        // 3. Validate Checksum
        $checksumFile = $latest->getPathname() . '.sha256';
        if (File::exists($checksumFile)) {
            $expectedChecksumRaw = File::get($checksumFile);
            $expectedHash = explode(' ', $expectedChecksumRaw)[0]; // sha256sum outputs "hash filename"
            
            $actualHash = hash_file('sha256', $latest->getPathname());
            
            if ($expectedHash !== $actualHash) {
                $this->error("CRITICAL: Checksum verification FAILED.");
                $this->error("Expected: {$expectedHash}");
                $this->error("Actual:   {$actualHash}");
                return 1;
            }
            $this->info("Checksum check passed.");
        } else {
            $this->warn("WARNING: No checksum file found for verification.");
        }

        $this->info("SUCCESS: Latest backup is healthy and verified.");
        return 0;
    }
}
