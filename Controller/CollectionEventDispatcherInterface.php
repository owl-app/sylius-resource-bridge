<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\Controller;

use Owl\Bridge\SyliusResourceBridge\Event\CollectionPreLoadEvent;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Grid\Data\ExpressionBuilderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface CollectionEventDispatcherInterface
{
    public function dispatch(
        string $eventName,
        string $dataClass,
        ExpressionBuilderInterface $expressionBuilder = null
    ): CollectionPreLoadEvent;
}
