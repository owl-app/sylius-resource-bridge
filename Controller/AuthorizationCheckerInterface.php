<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Controller\AuthorizationCheckerInterface as SyliusAuthorizationCheckerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;

interface AuthorizationCheckerInterface extends SyliusAuthorizationCheckerInterface
{
    public function isGranted(SyliusRequestConfiguration $configuration, $permission = null): bool;
}
