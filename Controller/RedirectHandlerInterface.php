<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\Controller;

use Sylius\Bundle\ResourceBundle\Controller\RedirectHandlerInterface as SyliusRedirectHandlerInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface RedirectHandlerInterface extends SyliusRedirectHandlerInterface
{
    public function getRedirectHeaders(RequestConfiguration $configuration, ?ResourceInterface $resource): array;
}
