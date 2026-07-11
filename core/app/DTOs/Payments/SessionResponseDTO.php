<?php

namespace App\DTOs\Payments;

class SessionResponseDTO
{
    private string $sessionId;
    private string $status;
    private string $expiresAt;

    public function __construct(string $sessionId, string $status, string $expiresAt)
    {
        $this->sessionId = $sessionId;
        $this->status = $status;
        $this->expiresAt = $expiresAt;
    }

    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'status' => $this->status,
            'expires_at' => $this->expiresAt,
        ];
    }
}
