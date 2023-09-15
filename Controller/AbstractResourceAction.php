<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Doctrine\Persistence\ObjectManager;
use Owl\Bridge\SyliusResource\Exception\InvalidResponseException;
use Sylius\Bundle\ResourceBundle\Controller\ControllerTrait;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class AbstractResourceAction implements AbstractResourceActionInterface
{
    use ControllerTrait;
    use ContainerAwareTrait;

    private const AJAX_VALIDATION_EVENT = 'ajax_validation';

    /** @var MetadataInterface */
    protected $metadata;

    /** @var RepositoryInterface */
    protected $repository;

    /** @var FactoryInterface */
    protected $factory;

    /** @var ObjectManager */
    protected $manager;

    protected AuthorizationCheckerInterface $authorizationChecker;

    protected EventDispatcherInterface $eventDispatcher;

    public function setMetadata(MetadataInterface $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function setRepository(RepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }

    public function setFactory(FactoryInterface $factory): void
    {
        $this->factory = $factory;
    }

    public function setManager(ObjectManager $manager): void
    {
        $this->manager = $manager;
    }

    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): void
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function isGrantedOr403(RequestConfiguration $configuration, \Sylius\Component\Resource\Model\ResourceInterface|null $resource = null): void
    {
        if (!$configuration->hasPermission()) {
            return;
        }

        if (!$this->authorizationChecker->isGranted($configuration, $resource)) {
            throw new AccessDeniedException();
        }
    }

    protected function eventAjaxValidation(RequestConfiguration $configuration, FormInterface $form): Response
    {
        $event = $this->eventDispatcher->dispatchAjaxValidationEvent(self::AJAX_VALIDATION_EVENT, $configuration, $form);

        if ($event->isStopped()) {
            $eventResponse = $event->getResponse();
            if (null !== $eventResponse) {
                return $eventResponse;
            }

            throw new InvalidResponseException('Event ajax validation must return class instance of Response');
        }

        throw new InvalidResponseException('Event must be stopped');
    }
}
