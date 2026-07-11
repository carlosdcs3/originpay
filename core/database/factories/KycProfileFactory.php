<?php

namespace Database\Factories;

use App\Models\KycProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KycProfile>
 */
class KycProfileFactory extends Factory
{
    protected $model = KycProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'level' => 0,
            'status' => 'PENDING',
            'approved_at' => null,
            'rejection_reason' => null,
        ];
    }
}
