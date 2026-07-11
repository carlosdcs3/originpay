<?php

namespace App\Services\Analytics;

use App\Models\Charge;
use App\Models\User;
use App\Models\PaymentGateway;
use App\Models\WithdrawalRequest;
use App\Models\KycSubmission;
use App\Enums\ChargeStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class AdminDashboardMetricsService
{
    /**
     * Retorna os KPIs principais cacheados (C-Level Macro).
     */
    public function getExecutiveMetrics(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        return Cache::remember('admin.dashboard.kpis', 300, function () use ($today, $startOfMonth) {
            // Volume Diário e Mensal (Soma das charges pagas)
            $tpvDaily = Charge::where('status', ChargeStatus::PAID)->whereDate('updated_at', $today)->sum('amount');
            $tpvMonthly = Charge::where('status', ChargeStatus::PAID)->where('updated_at', '>=', $startOfMonth)->sum('amount');
            
            // Receita Líquida da Plataforma Mensal (Platform Fees)
            $netRevenue = Charge::where('status', ChargeStatus::PAID)->where('updated_at', '>=', $startOfMonth)->sum('platform_fee');

            // Métricas de Volume Transacional Diário
            $chargesCreatedToday = Charge::whereDate('created_at', $today)->count();
            $chargesPaidToday = Charge::where('status', ChargeStatus::PAID)->whereDate('updated_at', $today)->count();

            // Taxa de Aprovação (Approval Rate) - (Pagos / Criados)
            $approvalRate = $chargesCreatedToday > 0 
                ? round(($chargesPaidToday / $chargesCreatedToday) * 100, 2) 
                : 0;

            // Ticket Médio
            $avgTicket = $chargesPaidToday > 0 ? $tpvDaily / $chargesPaidToday : 0;

            // Base de Lojistas e Gateways
            $newMerchants = User::whereDate('created_at', $today)->count();
            $activeMerchants = User::where('status', 1)->count();
            $activeGateways = PaymentGateway::where('status', 1)->count();

            // Operacional (Saques / KYC) - Pode ser em real-time mas aceitável 5 mins
            $pendingWithdrawals = WithdrawalRequest::where('status', 2)->count(); // 2 is usually pending in old systems
            $pendingKyc = KycSubmission::where('status', 0)->count(); // Assuming 0 is pending

            return [
                'tpv_daily' => $tpvDaily,
                'tpv_monthly' => $tpvMonthly,
                'net_revenue_monthly' => $netRevenue,
                'charges_created_today' => $chargesCreatedToday,
                'charges_paid_today' => $chargesPaidToday,
                'approval_rate' => $approvalRate,
                'avg_ticket' => $avgTicket,
                'new_merchants_today' => $newMerchants,
                'active_merchants' => $activeMerchants,
                'active_gateways' => $activeGateways,
                'pending_withdrawals' => $pendingWithdrawals,
                'pending_kyc' => $pendingKyc,
            ];
        });
    }

    /**
     * Cache ultra-curto (1 minuto) para alertas críticos da plataforma.
     */
    public function getCriticalAlerts(): array
    {
        return Cache::remember('admin.dashboard.alerts', 60, function () {
            // Podemos varrer o Logs ou tabela de Incidentes
            $alerts = [];
            
            // Simulando alertas dinâmicos baseados no Health Score
            $gateways = PaymentGateway::where('status', 1)->get();
            foreach($gateways as $gw) {
                $score = \Illuminate\Support\Facades\Cache::get("gateway:health_score:{$gw->code}");
                if ($score !== null && (int)$score <= -50) {
                    $alerts[] = "URGENTE: Gateway {$gw->name} está com Circuit Breaker ABERTO (Score: {$score}).";
                }
            }

            // Withdrawals Paused?
            if (\Illuminate\Support\Facades\Cache::get('system_withdrawals_paused')) {
                $alerts[] = "AVISO: O Kill Switch de saques está ATIVADO. Todos os processamentos financeiros de saída estão suspensos.";
            }

            return $alerts;
        });
    }
}
