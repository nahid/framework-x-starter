<?php

declare(strict_types=1);

namespace Supports\DependencyResolver;

use Psr\Container\ContainerInterface;

class Resolver
{

    protected array $resolvers = [];
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $resolvers = require base_path('config/resolvers.php');

        $this->instantiate($resolvers);
    }

    /**
     * @param BaseResolver[] $resolvers
     * @return void
     */
    protected function instantiate(array $resolvers):void
    {
        foreach ($resolvers as $resolver) {
            $this->addResolver(new $resolver($this->container));
        }
    }

    protected function addResolver(BaseResolver $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    /**
     * @return BaseResolver[]
     */
    protected function getResolvers(): array
    {
        return $this->resolvers;
    }

    public function make(): void
    {
        foreach ($this->getResolvers() as $resolver) {
            $resolver->boot();
            $resolver->resolve();
        }
    }
}