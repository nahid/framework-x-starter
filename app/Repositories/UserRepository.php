<?php

namespace App\Repositories;

use App\DTO\User;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UserRepository extends Repository
{

    public function getTableName(): string
    {
        return 'users';
    }

    /**
     * @throws UnknownProperties
     */
    public function findById(int $id): User
    {
        $result = $this->find($id);

        return new User($result);
    }
}