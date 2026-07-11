<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class EmailTemplateService
{
    /**
     * Envia o E-mail de Boas-vindas
     */
    public function sendWelcome(string $email, string $name): void
    {
        $this->send($email, 'Bem-vindo à OriginPay!', 'welcome', [
            'name' => $name,
            'ctaUrl' => route('user.dashboard'),
        ]);
    }

    /**
     * Envia confirmação de KYC enviado
     */
    public function sendKycSubmitted(string $email, string $name): void
    {
        $this->send($email, 'Seus documentos foram recebidos - OriginPay', 'kyc-submitted', [
            'name' => $name,
        ]);
    }

    /**
     * Envia e-mail de Cobrança Paga (Notifica lojista)
     */
    public function sendChargePaid(string $email, string $name, string $chargeId, float $amount): void
    {
        $this->send($email, 'Você recebeu um novo pagamento! - OriginPay', 'charge-paid', [
            'name' => $name,
            'chargeId' => $chargeId,
            'amount' => number_format($amount, 2, ',', '.'),
        ]);
    }
    
    /**
     * Notificação de Alerta Crítico do Sistema
     */
    public function sendSystemAlert(string $email, string $alertTitle, string $alertDetails): void
    {
        $this->send($email, 'ALERTA CRÍTICO: ' . $alertTitle, 'system-alert', [
            'alertTitle' => $alertTitle,
            'alertDetails' => $alertDetails,
            'ctaUrl' => route('admin.dashboard'),
        ]);
    }

    /**
     * Método genérico de envio utilizando o layout padronizado do Design System.
     */
    protected function send(string $to, string $subject, string $templateKey, array $data): void
    {
        // Fallback or actual logic for mailer
        try {
            Mail::send("emails.{$templateKey}", $data, function (Message $message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send email to {$to}: " . $e->getMessage());
        }
    }
}
