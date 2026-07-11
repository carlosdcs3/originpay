<?php

namespace App\Enums;

enum ProviderType: string
{
    case STRIPE = 'STRIPE';
    case PAYPAL = 'PAYPAL';
    case PIX_GENERIC = 'PIX_GENERIC';
    case MANUAL = 'MANUAL';
    // Adicionar novos providers aqui conforme forem integrados na arquitetura moderna
}
