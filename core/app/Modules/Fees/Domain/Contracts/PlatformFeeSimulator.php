<?php

namespace App\Modules\Fees\Domain\Contracts;

use App\Services\Fees\PlatformFeeResult;

interface PlatformFeeSimulator
{
    public function simulate(array $data): PlatformFeeResult;
}
