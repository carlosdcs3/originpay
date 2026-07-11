<?php

namespace App\Gateway;

interface GatewayCapabilities
{
    public function supportsPix(): bool;
    public function supportsCard(): bool;
    public function supportsBoleto(): bool;
    public function supportsSplit(): bool;
    public function supportsWithdraw(): bool;
    public function supportsRefund(): bool;
    public function supportsWebhookValidation(): bool;
}
