<?php

namespace App\Repositories\Payments;

use App\Contracts\Payments\ChargeRepositoryInterface;
use App\Domain\Payments\Charge as DomainCharge;
use App\Models\Charge as EloquentCharge;

class EloquentChargeRepository implements ChargeRepositoryInterface
{
    public function save(DomainCharge $charge): void
    {
        EloquentCharge::updateOrCreate(
            ['charge_id' => $charge->id],
            [
                'charge_number' => $charge->chargeNumber,
                'merchant_id' => $charge->merchantId,
                'session_id' => $charge->sessionId,
                'payment_method_id' => $charge->paymentMethodId,
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'status' => $charge->status,
                'failure_code' => $charge->failureCode,
                'failure_message' => $charge->failureMessage,
                'merchant_metadata' => $charge->merchantMetadata,
                'internal_metadata' => $charge->internalMetadata,
                'environment' => $charge->environment,
            ]
        );
    }

    public function findByIdAndMerchant(string $chargeId, string $merchantId): ?DomainCharge
    {
        $model = EloquentCharge::where('charge_id', $chargeId)
            ->where('merchant_id', $merchantId)
            ->first();

        if (!$model) {
            return null;
        }

        return new DomainCharge(
            id: $model->charge_id,
            merchantId: (string) $model->merchant_id,
            sessionId: $model->session_id ? (string) $model->session_id : null,
            paymentMethodId: $model->payment_method_id ? (string) $model->payment_method_id : null,
            amount: (int) round((float) $model->amount),
            currency: $model->currency,
            status: $model->status,
            merchantMetadata: $model->merchant_metadata ?? [],
            internalMetadata: $model->internal_metadata ?? [],
            environment: $model->environment,
            chargeNumber: $model->charge_number,
            failureCode: $model->failure_code,
            failureMessage: $model->failure_message,
            createdAt: $model->created_at?->toIso8601String()
        );
    }
}
