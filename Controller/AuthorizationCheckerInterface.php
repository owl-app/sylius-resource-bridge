<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\Controller;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\AuthorizationCheckerInterface as SyliusAuthorizationCheckerInterface;

interface AuthorizationCheckerInterface extends SyliusAuthorizationCheckerInterface
{
    public function isGranted(SyliusRequestConfiguration $configuration, $resource = null): bool;
}
