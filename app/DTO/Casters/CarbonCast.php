<?php

namespace App\DTO\Casters;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class CarbonCast implements \Spatie\DataTransferObject\Caster
{

    public function cast(mixed $value): CarbonInterface
    {
        return new Carbon($value);
    }
}