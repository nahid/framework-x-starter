<?php

namespace App\DTO;

use App\DTO\Casters\CarbonCast;
use Carbon\Carbon;
use DateTimeImmutable;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Attributes\DefaultCast;
use Spatie\DataTransferObject\DataTransferObject;

class User extends DataTransferObject
{
    public int $id;
    public string $name;
    public string $email;
    public ?string $remember_token;
    #[
        CastWith(CarbonCast::class),
    ]
    public Carbon $created_at;
    public string $updated_at;
    public ?string $email_verified_at;
}