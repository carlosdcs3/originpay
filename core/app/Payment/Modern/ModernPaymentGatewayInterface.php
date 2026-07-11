<?php

namespace App\Payment\Modern;

use Illuminate\Http\Request;
use App\Payment\Modern\DTO\DepositDTO;
use App\Payment\Modern\DTO\WebhookDTO;
use App\Payment\Modern\DTO\RefundDTO;
use App\Payment\Modern\DTO\WithdrawDTO;
use App\Payment\Modern\DTO\GatewayResponseDTO;
use App\Payment\Modern\DTO\GatewayTransactionDTO;

interface ModernPaymentGatewayInterface
{
    public function createDeposit(DepositDTO $dto): GatewayResponseDTO;
    public function createPix(DepositDTO $dto): GatewayResponseDTO;
    public function createCheckout(DepositDTO $dto): GatewayResponseDTO;
    
    public function verifyWebhook(Request $request): bool;
    public function parseWebhook(Request $request): WebhookDTO;
    
    public function refund(RefundDTO $dto): GatewayResponseDTO;
    public function withdraw(WithdrawDTO $dto): GatewayResponseDTO;
    
    public function getTransaction(string $providerTrxId): GatewayTransactionDTO;
    public function healthCheck(): string; // e.g. CONNECTED, DEGRADED, OFFLINE
}
