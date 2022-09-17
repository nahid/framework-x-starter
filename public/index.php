<?php

use React\Http\Message\Response;
use Supports\DependencyResolver\Resolver;

require __DIR__ . '/../vendor/autoload.php';

(\Dotenv\Dotenv::createImmutable(base_path('/')))->load();

$container = new \DI\Container();

(new Resolver($container))->make();

$app = new FrameworkX\App(new \FrameworkX\Container($container));

$app->get('/', \App\Controllers\UserController::class);

$app->get('/users/{name}', function (Psr\Http\Message\ServerRequestInterface $request) {
    return Response::json([
        'name' => $request->getAttribute('name'),
        'status' => Response::STATUS_OK,
    ]);
});

$app->run();