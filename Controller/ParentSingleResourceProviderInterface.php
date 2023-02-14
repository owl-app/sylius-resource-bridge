<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;

interface ParentSingleResourceProviderInterface
{
    public function get(RequestConfiguration $configuration): array;
}
