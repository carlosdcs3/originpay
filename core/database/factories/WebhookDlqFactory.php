<?php

namespace Database\Factories;

use App\Models\WebhookDlq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDlq>
 */
class WebhookDlqFactory extends Factory
{
    protected $model = WebhookDlq::class;

    public function definition(): array
    {
        return [
            'provider' => 'MANUAL',
            'event_id' => 'evt_' . $this->faker->unique()->uuid(),
            'external_reference' => null,
            'payload' => json_encode(['id' => $this->faker->uuid()]),
            'headers' => json_encode([]),
            'error_message' => 'Test DLQ error',
            'error_class' => \Exception::class,
            'attempts' => 3,
            'resolved_at' => null,
        ];
    }
}
