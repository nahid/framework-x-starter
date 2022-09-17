<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use Noodlehaus\ConfigInterface;
use React\Http\Message\Response;
use React\MySQL\ConnectionInterface;
use Throwable;
use function React\Async\await;

class UserController
{
    public function __construct(
        protected ConfigInterface $config,
        protected UserRepository $userRepository,
    ) {

    }

    /**
     * @return Response
     * @throws Throwable
     */
    public function __invoke(): Response
    {
        $user = $this->userRepository->findById(1);

        return Response::json(
            $user
        );
    }
}