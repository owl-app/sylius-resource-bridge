<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Controller\EventDispatcherInterface as SyliusEventDispatcherInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

final class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private SyliusEventDispatcherInterface $decorated,
        private SymfonyEventDispatcherInterface $eventDispatcher
    ) {
        $this->decorated = $decorated;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function dispatch(
        string $eventName,
        SyliusRequestConfiguration $requestConfiguration,
        ResourceInterface $resource
    ): ResourceControllerEvent {
        $eventName = $requestConfiguration->getEvent() ?: $eventName;
        $metadata = $requestConfiguration->getMetadata();
        $event = new ResourceControllerEvent($resource);

        $this->eventDispatcher->dispatch($event, sprintf('%s.%s.%s', $metadata->getApplicationName(), $metadata->getName(), $eventName));

        return $event;
    }

    public function dispatchMultiple(
        string $eventName,
        SyliusRequestConfiguration $requestConfiguration,
        $resources
    ): ResourceControllerEvent {
        return $this->decorated->dispatchMultiple($eventName, $requestConfiguration, $resources);
    }

    public function dispatchPreEvent(
        string $eventName,
        SyliusRequestConfiguration $requestConfiguration,
        ResourceInterface $resource
    ): ResourceControllerEvent {
        return $this->decorated->dispatchPreEvent($eventName, $requestConfiguration, $resource);
    }

    public function dispatchPostEvent(
        string $eventName,
        SyliusRequestConfiguration $requestConfiguration,
        ResourceInterface $resource
    ): ResourceControllerEvent {
        return $this->decorated->dispatchPostEvent($eventName, $requestConfiguration, $resource);
    }

    public function dispatchInitializeEvent(
        string $eventName,
        SyliusRequestConfiguration $requestConfiguration,
        ResourceInterface $resource
    ): ResourceControllerEvent {
        return $this->decorated->dispatchInitializeEvent($eventName, $requestConfiguration, $resource);
    }

    /**
     * @param RequestConfiguration $requestConfiguration
     */
    public function dispatchAjaxValidationEvent(
        string $eventName,
        SyliusRequestConfiguration $requestConfiguration,
        FormInterface $form
    ): ResourceControllerEvent {
        $eventName = $eventName;
        $addtionalName = $requestConfiguration->getAjaxValidationEventName();
        $metadata = $requestConfiguration->getMetadata();
        $event = new ResourceControllerEvent($form);

        $this->eventDispatcher->dispatch($event, sprintf('%s.%s%s', $metadata->getApplicationName(), $eventName, $addtionalName));

        return $event;
    }
}