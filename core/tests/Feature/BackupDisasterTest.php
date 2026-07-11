<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FinancialAnomaly;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class BackupDisasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup clean mock environment for backups
        $this->backupDir = storage_path('backups_test');
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir);
        }
        
        // Temporarily bind the path if needed, or we just mock the file system if possible.
        // For simplicity in this test, we will just call the command and let it fail because there's no backup in the normal path,
        // which triggers backup_missing anomaly.
    }

    protected function tearDown(): void
    {
        if (File::exists($this->backupDir)) {
            File::deleteDirectory($this->backupDir);
        }
        parent::tearDown();
    }

    public function test_missing_backup_generates_anomaly()
    {
        // Limpar o diretório storage/backups se existir temporariamente para forçar o erro
        $realBackupDir = storage_path('backups');
        $moved = false;
        if (File::exists($realBackupDir)) {
            rename($realBackupDir, $realBackupDir . '_temp');
            $moved = true;
        }

        Artisan::call('anomalies:scan');

        $anomaly = FinancialAnomaly::where('type', 'backup_missing')->first();
        $this->assertNotNull($anomaly);
        $this->assertEquals('CRITICAL', $anomaly->severity);

        // Restaurar
        if ($moved) {
            rename($realBackupDir . '_temp', $realBackupDir);
        }
    }

    public function test_verify_restore_dry_run_command_fails_on_missing_file()
    {
        $exitCode = Artisan::call('disaster:verify-restore', ['--file' => 'non_existent_file.sql.gz']);
        $this->assertEquals(1, $exitCode);
    }
}
