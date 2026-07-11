<?php

namespace App\Enums;

/**
 * Defines the routing strategy used to select a gateway from the available chain.
 *
 * Only `manual` is currently active. All other strategies are
 * architecturally prepared for future releases.
 */
enum RoutingStrategy: string
{
    /**
     * [ACTIVE] Admin manually defines gateway order (primary + fallbacks).
     * The first available, healthy gateway in the list will be used.
     */
    case MANUAL = 'manual';

    /**
     * [FUTURE] Select the gateway with the lowest configured transaction fee.
     * Requires fee data to be available per gateway.
     */
    case LOWEST_FEE = 'lowest_fee';

    /**
     * [FUTURE] Select the gateway with the lowest average latency.
     * Requires latency metrics from GatewayLog to be aggregated.
     */
    case LOWEST_LATENCY = 'lowest_latency';

    /**
     * [FUTURE] Select the gateway with the highest Health Score.
     * Integrates with GatewayHealthScoreService.
     */
    case HIGHEST_HEALTH_SCORE = 'highest_health_score';

    /**
     * [FUTURE] Distribute traffic by weight percentages configured per gateway.
     * Requires the `gateway_weights` JSON field to be populated.
     */
    case WEIGHTED = 'weighted';

    /**
     * [FUTURE] Distribute traffic evenly across all available gateways.
     * Maintains a counter in Redis to track next-in-turn.
     */
    case ROUND_ROBIN = 'round_robin';

    public function label(): string
    {
        return match($this) {
            self::MANUAL               => 'Manual',
            self::LOWEST_FEE           => 'Menor Taxa',
            self::LOWEST_LATENCY       => 'Menor Latência',
            self::HIGHEST_HEALTH_SCORE => 'Maior Health Score',
            self::WEIGHTED             => 'Distribuição por Peso',
            self::ROUND_ROBIN          => 'Round Robin',
        };
    }

    /**
     * Whether this strategy is currently implemented and usable.
     */
    public function isImplemented(): bool
    {
        return $this === self::MANUAL;
    }
}
