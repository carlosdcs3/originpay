<?php

namespace App\Gateway\Providers;

class GatewayDefinition
{
    public string $name;
    public string $code;
    public string $adapter;
    public string $logo;
    public string $description;
    public string $provider_version = '1.0.0';
    public string $metadata_version = '1.0.0';

    // Legacy booleans mappings (maintained for compatibility)
    public bool $supports_pix = false;
    public bool $supports_boleto = false;
    public bool $supports_card = false;
    public bool $supports_crypto = false;
    public bool $is_withdraw = false;

    // The primary source of truth for operations
    public array $operations = []; // e.g. ['PIX_CHARGE', 'PIX_WITHDRAW', 'BOLETO']

    // The new Enterprise metadata schema for credentials
    public array $credentials = [];
    public array $withdraw_fields = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        // Map operations to legacy booleans for backwards compatibility
        if (!empty($this->operations)) {
            $this->supports_pix = in_array('PIX_CHARGE', $this->operations);
            $this->is_withdraw = in_array('PIX_WITHDRAW', $this->operations) || in_array('CRYPTO_WITHDRAW', $this->operations);
            $this->supports_boleto = in_array('BOLETO', $this->operations);
            $this->supports_card = in_array('CARD_CREDIT', $this->operations) || in_array('CARD_DEBIT', $this->operations);
            $this->supports_crypto = in_array('CRYPTO_CHARGE', $this->operations);
        }
    }
}
