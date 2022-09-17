<?php

namespace Supports\DependencyResolver;

use Psr\Container\ContainerInterface;

abstract class BaseResolver
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    abstract public function resolve();

    public function boot(): void
    {

    }
}