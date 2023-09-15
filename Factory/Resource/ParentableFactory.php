<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Factory\Resource;

use Owl\Bridge\SyliusResource\Exception\ParetResourceNotFound;
use Sylius\Component\Resource\Model\ResourceInterface;

abstract class ParentableFactory implements ParentableFactoryInterface
{
    private string $className;

    private array $resourceParents;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function createNew()
    {
        return new $this->className();
    }

    public function getResourceParents(string $name): ResourceInterface
    {
        if (!isset($this->resourceParents[$name])) {
            throw new ParetResourceNotFound(sprintf('Resource %s not found', $name));
        }

        return $this->resourceParents[$name];
    }

    public function setResourceParents(array $resources): void
    {
        $this->resourceParents = $resources;
    }

    abstract public function createForParent(string $parentName);
}
