<?php
namespace App\Services\Connect;

use App\Models\Connect\UsageLimit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConnectUsageLimitService
{
    public function canUse($merchantId, $channel, $amount = 1)
    {
        // First check locally in context to avoid writing unless needed
        $context = ConnectAccessContext::getInstance($merchantId);
        $usage = $context->usage()->get($channel);

        // If it's expired, we know it needs a reset and we might have quota again.
        // We will just do the reset right now.
        if ($usage && $usage->resets_at && Carbon::now()->isAfter($usage->resets_at)) {
            $this->resetIfNeeded($merchantId, $channel);
            // Refresh context usage
            $limit = UsageLimit::where('merchant_id', $merchantId)->where('channel', $channel)->first();
        } else {
            $limit = $usage;
        }

        if (!$limit || $limit->monthly_limit == 0) return true;
        
        return ($limit->current_usage + $amount) <= $limit->monthly_limit;
    }

    public function incrementUsage($merchantId, $channel, $amount = 1)
    {
        DB::transaction(function () use ($merchantId, $channel, $amount) {
            $limit = UsageLimit::where('merchant_id', $merchantId)->where('channel', $channel)->lockForUpdate()->first();
            
            if (!$limit) {
                UsageLimit::create([
                    'merchant_id' => $merchantId,
                    'channel' => $channel,
                    'current_usage' => $amount,
                    'resets_at' => Carbon::now()->addMonth()->startOfMonth()
                ]);
                return;
            }

            if ($limit->resets_at && Carbon::now()->isAfter($limit->resets_at)) {
                $limit->current_usage = 0;
                $limit->resets_at = Carbon::now()->addMonth()->startOfMonth();
            }

            $limit->current_usage += $amount;
            $limit->save();
        });
    }

    public function resetIfNeeded($merchantId, $channel)
    {
        DB::transaction(function () use ($merchantId, $channel) {
            $limit = UsageLimit::where('merchant_id', $merchantId)->where('channel', $channel)->lockForUpdate()->first();
            
            if ($limit && $limit->resets_at && Carbon::now()->isAfter($limit->resets_at)) {
                $limit->update([
                    'current_usage' => 0,
                    'resets_at' => Carbon::now()->addMonth()->startOfMonth()
                ]);
            }
        });
    }
}
