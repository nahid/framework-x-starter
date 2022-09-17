<?php

namespace App\ServiceResolvers;

use Noodlehaus\ConfigInterface;
use Supports\Config\ArrayConfig;
use Supports\DependencyResolver\BaseResolver;

class ConfigResolver extends BaseResolver
{

    public function resolve()
    {
        $this->container->set(ConfigInterface::class, function () {
            return new ArrayConfig(base_path('config'));
        });
    }

}