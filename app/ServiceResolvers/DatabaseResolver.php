<?php

namespace App\ServiceResolvers;

use Noodlehaus\ConfigInterface;
use React\MySQL\ConnectionInterface;
use Supports\DependencyResolver\BaseResolver;
use React\MySQL\Factory;

class DatabaseResolver extends BaseResolver
{

    public function resolve()
    {
        $this->container->set(ConnectionInterface::class , function () {
            $config = $this->container->get(ConfigInterface::class);

            $host = $config->get('db.host');
            $dbName = $config->get('db.name');
            $user = $config->get('db.username');
            $pass = $config->get('db.password', '');
            $port = $config->get('db.port');

            $credentials = $user . ':' . $pass . '@' . $host . ':' . $port . '/' . $dbName . '?idle=0.001';
            return (new Factory())->createLazyConnection($credentials);
        });
    }

}