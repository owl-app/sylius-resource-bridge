<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\Controller;

use Owl\Bridge\SyliusResourceBridge\Event\CollectionPreLoadEvent;
use Sylius\Bundle\ResourceBundle\Controller\EventDispatcherInterface as SyliusEventDispatcherInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Grid\Data\ExpressionBuilderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

final class CollectionEventDispatcher implements CollectionEventDispatcherInterface
{
    /** @var SymfonyEventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(SymfonyEventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function dispatch(
        ?string $eventName,
        string $dataClass,
        ExpressionBuilderInterface $expressionBuilder = null
    ): CollectionPreLoadEvent {
        $event = new CollectionPreLoadEvent(
            $dataClass, 
            $expressionBuilder
        );

        $this->eventDispatcher->dispatch($event, CollectionPreLoadEvent::EVENT_MAIN_NAME);

        if($eventName) {
            $this->eventDispatcher->dispatch($event, $eventName);
        }

        return $event;
    }
}