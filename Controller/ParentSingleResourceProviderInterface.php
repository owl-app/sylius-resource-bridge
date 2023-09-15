<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

interface ParentSingleResourceProviderInterface
{
    public function get(RequestConfiguration $requestConfiguration): array;
}
