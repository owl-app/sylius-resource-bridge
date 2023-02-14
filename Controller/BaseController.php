<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\View\View;
use Owl\Bridge\SyliusResource\Exception\InvalidResponseException;
use Sylius\Bundle\ResourceBundle\Controller\FlashHelperInterface;
use Sylius\Bundle\ResourceBundle\Controller\NewResourceFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Controller\ResourceDeleteHandlerInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceFormFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourcesCollectionProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceUpdateHandlerInterface;
use Sylius\Bundle\ResourceBundle\Controller\SingleResourceProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\StateMachineInterface;
use Sylius\Bundle\ResourceBundle\Controller\ViewHandlerInterface;
use Sylius\Component\Resource\Exception\DeleteHandlingException;
use Sylius\Component\Resource\Exception\UpdateHandlingException;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @property AuthorizationCheckerInterface $authorizationChecker
 * @property EventDispatcherInterface $eventDispatcher
 * @property RedirectHandlerInterface $redirectHandler
 */
class BaseController extends ResourceController
{
    private const AJAX_VALIDATION_EVENT = 'ajax_validation';

    public function __construct(
        MetadataInterface $metadata,
        RequestConfigurationFactoryInterface $requestConfigurationFactory,
        ?ViewHandlerInterface $viewHandler,
        RepositoryInterface $repository,
        FactoryInterface $factory,
        NewResourceFactoryInterface $newResourceFactory,
        ObjectManager $manager,
        SingleResourceProviderInterface $singleResourceProvider,
        ResourcesCollectionProviderInterface $resourcesFinder,
        ResourceFormFactoryInterface $resourceFormFactory,
        RedirectHandlerInterface $redirectHandler,
        FlashHelperInterface $flashHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        EventDispatcherInterface $eventDispatcher,
        ?StateMachineInterface $stateMachine,
        ResourceUpdateHandlerInterface $resourceUpdateHandler,
        ResourceDeleteHandlerInterface $resourceDeleteHandler,
    ) { 
        $this->metadata = $metadata;
        $this->requestConfigurationFactory = $requestConfigurationFactory;
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->factory = $factory;
        $this->newResourceFactory = $newResourceFactory;
        $this->manager = $manager;
        $this->singleResourceProvider = $singleResourceProvider;
        $this->resourcesCollectionProvider = $resourcesFinder;
        $this->resourceFormFactory = $resourceFormFactory;
        $this->redirectHandler = $redirectHandler;
        $this->flashHelper = $flashHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->eventDispatcher = $eventDispatcher;
        $this->stateMachine = $stateMachine;
        $this->resourceUpdateHandler = $resourceUpdateHandler;
        $this->resourceDeleteHandler = $resourceDeleteHandler;
    }

    public function showAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $resource = $this->findOr404($configuration);
        $resourceParents = $this->findParents($configuration);

        $this->isGrantedOr403($configuration, ResourceActions::SHOW, $resource);

        $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $resource);

        if ($configuration->isHtmlRequest()) {
            return $this->render($configuration->getTemplate(ResourceActions::SHOW . '.html'), [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'resource' => $resource,
                $this->metadata->getName() => $resource,
                'resourceParents' => $resourceParents
            ]);
        }

        return $this->createRestView($configuration, $resource);
    }

    public function createAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $resourceParents = $this->findParents($configuration);
        $newResource = $this->newResourceFactory->create($configuration, $this->factory, $resourceParents);

        $this->isGrantedOr403($configuration, ResourceActions::CREATE);

        $form = $this->resourceFormFactory->create($configuration, $newResource);
        $form->handleRequest($request);

        /** @var RequestConfiguration $configuration */
        if ($configuration->isAjaxRequest() && $form->isSubmitted() && !$form->isValid()) {
            try {
                return $this->eventAjaxValidation($configuration, $form);
            } catch (InvalidResponseException $e) {
                throw $e;
            }
        }

        if ($request->isMethod('POST') && $form->isSubmitted() && $form->isValid()) {
            $newResource = $form->getData();

            $event = $this->eventDispatcher->dispatchPreEvent(ResourceActions::CREATE, $configuration, $newResource);

            if ($event->isStopped() && !$configuration->isHtmlRequest()) {
                throw new HttpException($event->getErrorCode(), $event->getMessage());
            }
            if ($event->isStopped()) {
                $this->flashHelper->addFlashFromEvent($configuration, $event);

                $eventResponse = $event->getResponse();
                if (null !== $eventResponse) {
                    return $eventResponse;
                }

                return $this->redirectHandler->redirectToIndex($configuration, $newResource);
            }

            if ($configuration->hasStateMachine()) {
                $stateMachine = $this->getStateMachine();
                $stateMachine->apply($configuration, $newResource);
            }

            $this->repository->add($newResource);

            if ($configuration->isHtmlRequest()) {
                $this->flashHelper->addSuccessFlash($configuration, ResourceActions::CREATE, $newResource);
            }

            $postEvent = $this->eventDispatcher->dispatchPostEvent(ResourceActions::CREATE, $configuration, $newResource);

            /** @var RequestConfiguration $configuration */
            if($configuration->isAjaxRequest()) {
                return $this->createAjaxView($configuration, $newResource, Response::HTTP_OK);
            }

            if (!$configuration->isHtmlRequest()) {
                return $this->createRestView($configuration, $newResource, Response::HTTP_CREATED);
            }

            $postEventResponse = $postEvent->getResponse();
            if (null !== $postEventResponse) {
                return $postEventResponse;
            }

            return $this->redirectHandler->redirectToResource($configuration, $newResource);
        }

        if (!$configuration->isHtmlRequest()) {
            return $this->createRestView($configuration, $form, Response::HTTP_BAD_REQUEST);
        }

        $initializeEvent = $this->eventDispatcher->dispatchInitializeEvent(ResourceActions::CREATE, $configuration, $newResource);
        $initializeEventResponse = $initializeEvent->getResponse();
        if (null !== $initializeEventResponse) {
            return $initializeEventResponse;
        }

        return $this->render($configuration->getTemplate(ResourceActions::CREATE . '.html'), [
            'configuration' => $configuration,
            'metadata' => $this->metadata,
            'resource' => $newResource,
            $this->metadata->getName() => $newResource,
            'form' => $form->createView(),
            'resourceParents' => $resourceParents
        ]);
    }

    public function updateAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $resource = $this->findOr404($configuration);
        $resourceParents = $this->findParents($configuration);

        $this->isGrantedOr403($configuration, ResourceActions::UPDATE, $resource);

        $form = $this->resourceFormFactory->create($configuration, $resource);
        $form->handleRequest($request);

        /** @var RequestConfiguration $configuration */
        if ($configuration->isAjaxRequest() && $form->isSubmitted() && !$form->isValid()) {
            try {
                return $this->eventAjaxValidation($configuration, $form);
            } catch (InvalidResponseException $e) {
                throw $e;
            }
        }

        if (
            in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)
            && $form->isSubmitted()
            && $form->isValid()
        ) {
            $resource = $form->getData();

            $event = $this->eventDispatcher->dispatchPreEvent(ResourceActions::UPDATE, $configuration, $resource);

            if ($event->isStopped() && !$configuration->isHtmlRequest()) {
                throw new HttpException($event->getErrorCode(), $event->getMessage());
            }
            if ($event->isStopped()) {
                $this->flashHelper->addFlashFromEvent($configuration, $event);

                $eventResponse = $event->getResponse();
                if (null !== $eventResponse) {
                    return $eventResponse;
                }

                return $this->redirectHandler->redirectToResource($configuration, $resource);
            }

            try {
                $this->resourceUpdateHandler->handle($resource, $configuration, $this->manager);
            } catch (UpdateHandlingException $exception) {
                if (!$configuration->isHtmlRequest()) {
                    return $this->createRestView($configuration, $form, $exception->getApiResponseCode());
                }

                $this->flashHelper->addErrorFlash($configuration, $exception->getFlash());

                return $this->redirectHandler->redirectToReferer($configuration);
            }

            if ($configuration->isHtmlRequest()) {
                $this->flashHelper->addSuccessFlash($configuration, ResourceActions::UPDATE, $resource);
            }

            $postEvent = $this->eventDispatcher->dispatchPostEvent(ResourceActions::UPDATE, $configuration, $resource);

            /** @var RequestConfiguration $configuration */
            if($configuration->isAjaxRequest()) {
                return $this->createAjaxView($configuration, $resource, Response::HTTP_OK);
            }

            if (!$configuration->isHtmlRequest()) {
                if ($configuration->getParameters()->get('return_content', false)) {
                    return $this->createRestView($configuration, $resource, Response::HTTP_OK);
                }

                return $this->createRestView($configuration, null, Response::HTTP_NO_CONTENT);
            }

            $postEventResponse = $postEvent->getResponse();
            if (null !== $postEventResponse) {
                return $postEventResponse;
            }

            return $this->redirectHandler->redirectToResource($configuration, $resource);
        }

        if (!$configuration->isHtmlRequest()) {
            return $this->createRestView($configuration, $form, Response::HTTP_BAD_REQUEST);
        }

        $initializeEvent = $this->eventDispatcher->dispatchInitializeEvent(ResourceActions::UPDATE, $configuration, $resource);
        $initializeEventResponse = $initializeEvent->getResponse();
        if (null !== $initializeEventResponse) {
            return $initializeEventResponse;
        }

        return $this->render($configuration->getTemplate(ResourceActions::UPDATE . '.html'), [
            'configuration' => $configuration,
            'metadata' => $this->metadata,
            'resource' => $resource,
            $this->metadata->getName() => $resource,
            'form' => $form->createView(),
            'resourceParents' => $resourceParents
        ]);
    }

    public function deleteAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $resource = $this->findOr404($configuration);
        $this->isGrantedOr403($configuration, ResourceActions::DELETE, $resource);

        if ($configuration->isCsrfProtectionEnabled() && !$this->isCsrfTokenValid((string) $resource->getId(), $request->request->get('_csrf_token'))) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Invalid csrf token.');
        }

        $event = $this->eventDispatcher->dispatchPreEvent(ResourceActions::DELETE, $configuration, $resource);

        if ($event->isStopped() && !$configuration->isHtmlRequest()) {
            throw new HttpException($event->getErrorCode(), $event->getMessage());
        }
        if ($event->isStopped()) {
            $this->flashHelper->addFlashFromEvent($configuration, $event);

            $eventResponse = $event->getResponse();
            if (null !== $eventResponse) {
                return $eventResponse;
            }

            return $this->redirectHandler->redirectToIndex($configuration, $resource);
        }

        try {
            $this->resourceDeleteHandler->handle($resource, $this->repository);
        } catch (DeleteHandlingException $exception) {
            if (!$configuration->isHtmlRequest()) {
                return $this->createRestView($configuration, null, $exception->getApiResponseCode());
            }

            $this->flashHelper->addErrorFlash($configuration, $exception->getFlash());

            return $this->redirectHandler->redirectToReferer($configuration);
        }

        if ($configuration->isHtmlRequest()) {
            $this->flashHelper->addSuccessFlash($configuration, ResourceActions::DELETE, $resource);
        }

        $postEvent = $this->eventDispatcher->dispatchPostEvent(ResourceActions::DELETE, $configuration, $resource);

        if (!$configuration->isHtmlRequest()) {
            return $this->createRestView($configuration, null, Response::HTTP_NO_CONTENT);
        }

        $postEventResponse = $postEvent->getResponse();
        if (null !== $postEventResponse) {
            return $postEventResponse;
        }

        return $this->redirectHandler->redirectToIndex($configuration, $resource);
    }

    protected function eventAjaxValidation(RequestConfiguration $configuration, FormInterface $form): Response
    {
        $event = $this->eventDispatcher->dispatchAjaxValidationEvent(self::AJAX_VALIDATION_EVENT, $configuration, $form);

        if ($event->isStopped()) {

            $eventResponse = $event->getResponse();
            if (null !== $eventResponse && $eventResponse instanceof Response) {
                return $eventResponse;
            }

            throw new InvalidResponseException('Event ajax validation must return class instance of Response');
        }

        throw new InvalidResponseException('Event must be stopped');
    }

    protected function createAjaxView(RequestConfiguration $configuration, $data = null, int $statusCode = null): Response
    {
        if (null === $this->viewHandler) {
            throw new \LogicException('You can not use the "non-html" request if FriendsOfSymfony Rest Bundle is not available. Try running "composer require friendsofsymfony/rest-bundle".');
        }

        $view = new View;
        $view->setData($data ?? []);
        $view->setStatusCode($statusCode);
        $view->setFormat('json');
        $view->setHeaders($this->redirectHandler->getRedirectHeaders($configuration, $data));

        return $this->viewHandler->handle($configuration, $view);
    }

    protected function getErrorMessages(FormInterface $form) 
    {
        $errors = array();

        foreach ($form->getErrors() as $key => $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    protected function isGrantedOr403(SyliusRequestConfiguration $configuration, string $name, $resource = null): void
    {
        if (!$configuration->hasPermission()) {
            return;
        }

        if (!$this->authorizationChecker->isGranted($configuration, $resource)) {
            throw new AccessDeniedException();
        }
    }

    protected function findParents(RequestConfiguration $configuration): array
    {
        if (!$this->container->has('owl.resource_controller.parent_single_resource_provider')) {
            throw new \LogicException(sprintf(
                'The %s is not registered in your application.',
                'owl.resource_controller.parent_single_resource_provider'
            ));
        }

        return $this->container->get('owl.resource_controller.parent_single_resource_provider')->get($configuration);
    }
}