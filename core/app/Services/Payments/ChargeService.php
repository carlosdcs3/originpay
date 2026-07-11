<?php

namespace App\Services\Payments;

use App\Contracts\Payments\ChargeRepositoryInterface;
use App\Domain\Payments\Charge;
use App\Enums\ChargeStatus;
use Illuminate\Support\Str;
use App\Events\ChargeStatusChanged;
use App\Domain\Auth\MerchantContext;
use App\Models\Charge as EloquentCharge;
use App\Models\ChargeTransition;
use App\Domain\Payments\GatewayResult;
use App\Domain\Payments\GatewayAuthorizeRequest;
use App\Services\Gateways\GatewayManager;

class ChargeService
{
    public function __construct(
        private readonly ChargeRepositoryInterface $repository,
        private readonly GatewayManager $gatewayManager
    ) {}

    public function createCharge(array $data, MerchantContext $merchant): Charge
    {
        // For Sprint 9: Tolerate nullable session/payment_method
        $sessionId = $data['session_id'] ?? null;
        $paymentMethodId = $data['payment_method_id'] ?? null;
        $amount = (int) $data['amount'];
        $merchantMetadata = $data['metadata'] ?? [];
        
        // Generate internal number
        $chargeNumber = 'Charge #' . rand(10000, 99999);

        // 1. Create Pending Charge
        $charge = new Charge(
            id: 'ch_' . Str::ulid(),
            merchantId: $merchant->merchantId,
            sessionId: $sessionId,
            paymentMethodId: $paymentMethodId,
            amount: $amount,
            currency: $data['currency'] ?? 'BRL',
            status: ChargeStatus::PENDING,
            merchantMetadata: $merchantMetadata,
            internalMetadata: [],
            environment: $merchant->environment,
            chargeNumber: $chargeNumber
        );

        $this->repository->save($charge);
        $this->recordTransition($charge->id, null, ChargeStatus::PENDING, 'Charge created');
        
        event(new ChargeStatusChanged($charge, 'charge.created'));

        // 2. Process Charge (Mock Gateway)
        return $this->processCharge($charge);
    }

    private function processCharge(Charge $charge): Charge
    {
        $gatewayRequest = new GatewayAuthorizeRequest(
            chargeId: $charge->id,
            merchantId: $charge->merchantId,
            amount: $charge->amount,
            currency: $charge->currency,
            paymentMethodId: $charge->paymentMethodId,
            merchantMetadata: $charge->merchantMetadata,
            environment: $charge->environment
        );

        $gatewayResult = $this->gatewayManager->authorize($gatewayRequest);

        $newStatus = $gatewayResult->success ? ChargeStatus::SUCCEEDED : ChargeStatus::FAILED;
        
        // Update internal metadata with gateway result
        $internalMetadata = $charge->internalMetadata;
        $internalMetadata['gateway_reference'] = $gatewayResult->gatewayReference;
        $internalMetadata['authorization_code'] = $gatewayResult->authorizationCode;
        $internalMetadata['raw_response'] = $gatewayResult->rawResponse;

        $updatedCharge = new Charge(
            id: $charge->id,
            merchantId: $charge->merchantId,
            sessionId: $charge->sessionId,
            paymentMethodId: $charge->paymentMethodId,
            amount: $charge->amount,
            currency: $charge->currency,
            status: $newStatus,
            merchantMetadata: $charge->merchantMetadata,
            internalMetadata: $internalMetadata,
            environment: $charge->environment,
            chargeNumber: $charge->chargeNumber,
            failureCode: $gatewayResult->failureCode,
            failureMessage: $gatewayResult->failureMessage,
            createdAt: $charge->createdAt
        );

        $this->repository->save($updatedCharge);
        
        $reason = $gatewayResult->success ? 'Payment successful' : $gatewayResult->failureMessage;
        $this->recordTransition($charge->id, $charge->status, $newStatus, $reason);

        $eventType = $updatedCharge->isSucceeded() ? 'charge.succeeded' : 'charge.failed';
        event(new ChargeStatusChanged($updatedCharge, $eventType));

        return $updatedCharge;
    }

    private function recordTransition(string $chargeId, ?ChargeStatus $from, ChargeStatus $to, string $reason): void
    {
        $model = EloquentCharge::where('charge_id', $chargeId)->first();
        if ($model) {
            ChargeTransition::create([
                'charge_id' => $model->id,
                'from_status' => $from?->value,
                'to_status' => $to->value,
                'reason' => $reason
            ]);
        }
    }

    public function updateChargeStatus(Charge $charge, ChargeStatus $newStatus, string $reason = 'Status updated'): Charge
    {
        if ($charge->status === $newStatus) {
            return $charge;
        }

        $updatedCharge = new Charge(
            id: $charge->id,
            merchantId: $charge->merchantId,
            sessionId: $charge->sessionId,
            paymentMethodId: $charge->paymentMethodId,
            amount: $charge->amount,
            currency: $charge->currency,
            status: $newStatus,
            merchantMetadata: $charge->merchantMetadata,
            internalMetadata: $charge->internalMetadata,
            environment: $charge->environment,
            chargeNumber: $charge->chargeNumber,
            failureCode: $charge->failureCode,
            failureMessage: $charge->failureMessage,
            createdAt: $charge->createdAt
        );

        $this->repository->save($updatedCharge);
        $this->recordTransition($charge->id, $charge->status, $newStatus, $reason);

        if ($updatedCharge->isSucceeded()) {
            event(new ChargeStatusChanged($updatedCharge, 'charge.succeeded'));
        } elseif ($newStatus === ChargeStatus::FAILED) {
            event(new ChargeStatusChanged($updatedCharge, 'charge.failed'));
        }

        return $updatedCharge;
    }

    public function getCharge(string $chargeId, MerchantContext $merchant): ?Charge
    {
        return $this->repository->findByIdAndMerchant($chargeId, $merchant->merchantId);
    }
}
