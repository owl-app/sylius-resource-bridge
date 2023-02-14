<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Common;

interface FilterInterface
{
    public function support(string $resourceClass, string $action): bool;
}
