<?php

namespace App\Console\Commands\Subscriptions;

use App\Services\Subscriptions\CustomerSubscriptionRenewalService;
use Illuminate\Console\Command;

class RenewCustomerSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:renew {--limit= : Maximum number of due subscriptions to process}';

    protected $description = 'Process due customer subscriptions and generate the next invoice and charge.';

    public function handle(CustomerSubscriptionRenewalService $renewalService): int
    {
        $startedAt = microtime(true);
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $summary = $renewalService->processDue($limit);
        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);

        $this->info('Customer subscriptions renewal completed.');
        $this->line('Subscriptions processadas: ' . $summary['processed']);
        $this->line('Invoices criadas: ' . $summary['invoices_created']);
        $this->line('Charges criadas: ' . $summary['charges_created']);
        $this->line('Subscriptions canceladas: ' . $summary['canceled']);
        $this->line('Subscriptions ignoradas: ' . $summary['skipped']);
        $this->line('Falhas: ' . $summary['failed']);
        $this->line('Tempo total: ' . $elapsedMs . 'ms');

        return self::SUCCESS;
    }
}
