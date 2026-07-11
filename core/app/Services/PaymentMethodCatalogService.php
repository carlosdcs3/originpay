<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentOperation;
use App\Models\PaymentMethodRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentMethodCatalogService
{
    /**
     * Methods enabled by Admin for merchant charge/payment-link creation.
     *
     * @return Collection<int, array{code: string, label: string, description: string, icon_class: string}>
     */
    public function activeChargeMethods(): Collection
    {
        if (! Schema::hasTable('payment_method_routes')) {
            return collect();
        }

        return PaymentMethodRoute::query()
            ->where('enabled', true)
            ->get()
            ->filter(fn (PaymentMethodRoute $route) => $this->isChargeRoute($route))
            ->map(fn (PaymentMethodRoute $route) => $this->methodCode($route))
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $code) => $this->present($code));
    }

    /**
     * @return array<int, string>
     */
    public function activeChargeMethodCodes(): array
    {
        return $this->activeChargeMethods()
            ->pluck('code')
            ->all();
    }

    public function isActiveChargeMethod(string $code): bool
    {
        return in_array($code, $this->activeChargeMethodCodes(), true);
    }

    private function isChargeRoute(PaymentMethodRoute $route): bool
    {
        if (! $route->payment_operation) {
            return true;
        }

        try {
            return PaymentOperation::from($route->payment_operation) !== PaymentOperation::PIX_WITHDRAW;
        } catch (\ValueError) {
            return true;
        }
    }

    private function methodCode(PaymentMethodRoute $route): ?string
    {
        if ($route->payment_operation) {
            try {
                return PaymentOperation::from($route->payment_operation)->paymentMethod()->value;
            } catch (\ValueError) {
                // Fall through to legacy payment_method for forward-compatible custom rows.
            }
        }

        return $route->payment_method ? Str::slug($route->payment_method, '_') : null;
    }

    /**
     * @return array{code: string, label: string, description: string, icon_class: string}
     */
    private function present(string $code): array
    {
        $enum = PaymentMethod::tryFrom($code);
        $label = $enum?->label() ?? Str::headline(str_replace(['_', '-'], ' ', $code));

        return [
            'code' => $code,
            'label' => $label,
            'description' => __('Disponivel conforme configuracao do administrador.'),
            'icon_class' => 'fas fa-wallet',
        ];
    }
}
