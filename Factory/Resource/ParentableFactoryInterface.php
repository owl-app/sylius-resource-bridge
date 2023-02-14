<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Factory\Resource;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface ParentableFactoryInterface extends FactoryInterface
{
    public function getResourceParents(string $name): ResourceInterface;

    public function setResourceParents(array $resources): void;
}
