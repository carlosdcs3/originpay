<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $plain = 'sk_test_' . Str::random(32);

        return [
            'user_id' => User::factory(),
            'name' => 'Test API Key',
            'key_prefix' => substr($plain, 0, 12),
            'key_hash' => hash('sha256', $plain),
            'environment' => 'live',
            'permissions' => ['*'],
            'status' => true,
        ];
    }

    public function forPlainKey(string $plain): static
    {
        return $this->state(fn () => [
            'key_prefix' => substr($plain, 0, 12),
            'key_hash' => hash('sha256', $plain),
        ]);
    }
}
