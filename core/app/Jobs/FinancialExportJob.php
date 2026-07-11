<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FinancialExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $adminId;
    protected $filters;

    /**
     * Create a new job instance.
     */
    public function __construct($adminId, $filters = [])
    {
        $this->adminId = $adminId;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $filename = "exports/ledger_export_" . time() . "_{$this->adminId}.csv";
        
        $query = WalletTransaction::with(['user', 'wallet']);

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        // Criar o CSV
        $file = fopen(storage_path('app/' . $filename), 'w');
        
        // Cabeçalhos
        fputcsv($file, ['ID', 'Type', 'Amount', 'Balance Before', 'Balance After', 'User', 'Description', 'Date', 'HMAC']);

        $query->chunk(500, function ($transactions) use ($file) {
            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->id,
                    $tx->type,
                    $tx->amount,
                    $tx->balance_before,
                    $tx->balance_after,
                    $tx->user->username ?? 'N/A',
                    $tx->description,
                    $tx->created_at,
                    $tx->integrity_hash
                ]);
            }
        });

        fclose($file);

        // Notificar Admin via sistema interno ou e-mail
        Log::channel('audit')->info("Exportação Financeira concluída", [
            'admin_id' => $this->adminId,
            'file' => $filename,
            'filters' => $this->filters
        ]);
        
        // Exemplo: Pode disparar um \App\Notifications\AdminExportReadyNotification...
    }
}
