<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\File;

class EfiValidationReportService
{
    protected string $logDir;
    protected string $timestamp;
    
    public function __construct()
    {
        $this->logDir = storage_path('logs/efi-validation');
        if (!File::exists($this->logDir)) {
            File::makeDirectory($this->logDir, 0755, true);
        }
        $this->timestamp = date('Y-m-d-Hi');
    }

    public function generateReport(string $testName, array $results, bool $isApproved): string
    {
        $statusStr = $isApproved ? "APROVADO PARA CERTIFICAÇÃO OPERACIONAL" : "BLOQUEADO POR DIVERGÊNCIAS";
        
        $baseName = "validation-{$testName}-{$this->timestamp}";
        $csvPath = "{$this->logDir}/{$baseName}.csv";
        $jsonPath = "{$this->logDir}/{$baseName}.json";
        $mdPath = "{$this->logDir}/{$baseName}.md";

        // JSON
        File::put($jsonPath, json_encode([
            'test' => $testName,
            'status' => $statusStr,
            'results' => $results
        ], JSON_PRETTY_PRINT));

        // CSV
        if (count($results) > 0) {
            $fp = fopen($csvPath, 'w');
            $headers = array_keys(is_array($results[0]) ? $results[0] : (array)$results[0]);
            fputcsv($fp, $headers);
            foreach ($results as $row) {
                fputcsv($fp, is_array($row) ? $row : (array)$row);
            }
            fclose($fp);
        }

        // Markdown
        $mdContent = "# Relatório de Validação Efí: {$testName}\n\n";
        $mdContent .= "**Data:** " . date('Y-m-d H:i:s') . "\n";
        $mdContent .= "**Resultado Final:** {$statusStr}\n\n";
        $mdContent .= "## Execuções\n\n";

        foreach ($results as $index => $row) {
            $mdContent .= "### Execução " . ($index + 1) . "\n";
            foreach ($row as $key => $val) {
                $mdContent .= "- **{$key}:** {$val}\n";
            }
            $mdContent .= "\n";
        }

        File::put($mdPath, $mdContent);

        return $mdPath;
    }
}
