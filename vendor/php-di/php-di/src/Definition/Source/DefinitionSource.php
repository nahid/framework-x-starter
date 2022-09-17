<?php

declare(strict_types=1);

namespace DI\Definition\Source;

use DI\Definition\Definition;
use DI\Definition\Exception\InvalidDefinition;

/**
 * Source of definitions for entries of the container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface DefinitionSource
{
    /**
     * Returns the DI definition for the entry name.
     *
     * @return Definition|null
     * @throws InvalidDefinition An invalid definition was found.
     */
    public function getDefinition(string $name);

    /**
     * @return Definition[] Definitions indexed by their name.
     */
    public function getDefinitions() : array;
}