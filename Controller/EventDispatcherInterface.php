<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Bundle\ResourceBundle\Controller\EventDispatcherInterface as SyliusEventDispatcherInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;
use Symfony\Component\Form\FormInterface;

interface EventDispatcherInterface extends SyliusEventDispatcherInterface
{
    public function dispatchAjaxValidationEvent(
        string $eventName,
        SyliusRequestConfiguration $requestConfiguration,
        FormInterface $form
    ): ResourceControllerEvent;
}
