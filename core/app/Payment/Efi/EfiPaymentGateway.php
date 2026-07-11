<?php

namespace App\Payment\Efi;

use App\Payment\PaymentGateway as PaymentGatewayInterface;
use App\Payment\Modern\Providers\EfiGateway;
use App\Payment\Modern\DTO\DepositDTO;
use App\Payment\Modern\DTO\WithdrawDTO;
use Illuminate\Http\Request;
use Exception;
use Log;

class EfiPaymentGateway implements PaymentGatewayInterface
{
    private EfiGateway $efiGateway;

    public function __construct()
    {
        $this->efiGateway = new EfiGateway();
    }

    public function deposit($amount, $currency, $trxId)
    {
        try {
            $dto = new DepositDTO();
            $dto->amount = $amount;
            $dto->currency = $currency;
            $dto->userId = auth()->id() ?? 'GUEST';

            $response = $this->efiGateway->createPix($dto);

            if ($response->success) {
                // Efi gateway returns QR Code string or payload in qrCode
                // In Digikash, gateways often return a view or redirect.
                // We'll return the view with the QR code.
                return view('frontend.user.deposit.partials._efi_pix_payment', [
                    'qrCode' => $response->qrCode,
                    'transactionId' => $trxId,
                    'amount' => $amount
                ])->render();
            }

            throw new Exception("Efí Error: " . $response->errorMessage);
        } catch (Exception $e) {
            Log::error('Efi Deposit Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function withdraw($amount, $currency, $trxId, $credential)
    {
        // $credential should be the PIX Key
        try {
            $dto = new WithdrawDTO();
            $dto->amount = $amount;
            $dto->currency = $currency;
            $dto->destinationKey = $credential;
            // EfiGateway is currently missing implementation for withdraw()
            // We will implement it directly in EfiGateway or here.
            $response = $this->efiGateway->withdraw($dto);

            if (!$response->success) {
                throw new Exception("Efí Withdraw Error: " . $response->errorMessage);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Efi Withdraw Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function handleIPN(Request $request)
    {
        // Handled by ModernWebhookController
        return response()->json(['status' => 'handled_by_modern_webhook']);
    }
}
