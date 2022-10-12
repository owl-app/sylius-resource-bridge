<?php
declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\Grid\Controller;

use Doctrine\ORM\QueryBuilder;
use Owl\Bridge\SyliusResourceBridge\Controller\CollectionEventDispatcherInterface;
use Owl\Bridge\SyliusResourceBridge\Event\CollectionPreLoadEvent;
use Sylius\Bundle\GridBundle\Doctrine\ORM\ExpressionBuilder;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\ResourcesResolverInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class ResourcesResolver implements ResourcesResolverInterface
{
    /** @var ResourcesResolverInterface */
    private $decoratedResolver;

    private CollectionEventDispatcherInterface $eventDispatcher;

    public function __construct(
        ResourcesResolverInterface $decoratedResolver,
        CollectionEventDispatcherInterface $eventDispatcher
    ) {
        $this->decoratedResolver = $decoratedResolver;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @psalm-suppress MissingReturnType
     */
    public function getResources(RequestConfiguration $requestConfiguration, RepositoryInterface $repository)
    {
        if ($requestConfiguration->hasGrid()) {
            return $this->decoratedResolver->getResources($requestConfiguration, $repository);
        }

        $method = $requestConfiguration->getRepositoryMethod();
        $metadata = $requestConfiguration->getMetadata();
        $customEventName = sprintf('%s.%s.%s', $metadata->getApplicationName(), $metadata->getName(), CollectionPreLoadEvent::EVENT_NAME);

        if (null !== $method) {
            if (is_array($method) && 2 === count($method)) {
                $repository = $method[0];
                $method = $method[1];
            }

            $arguments = array_values($requestConfiguration->getRepositoryArguments());
            $permissionQueryBuilder = $repository->createQueryBuilder('o');
            $expressionBuilder = new ExpressionBuilder($permissionQueryBuilder);

            $event = $this->eventDispatcher->dispatch(
                $customEventName,
                $requestConfiguration->getMetadata()->getClass('model'),
                $expressionBuilder
            );

            $this->injectExpression($event->getExpressions(), $permissionQueryBuilder, $arguments);

            return $repository->$method(...$arguments);
        }

        $criteria = [];
        if ($requestConfiguration->isFilterable()) {
            $criteria = $requestConfiguration->getCriteria();
        }

        $event = $this->eventDispatcher->dispatch(
            $customEventName,
            $requestConfiguration->getMetadata()->getClass('model')
        );

        $this->injectCriteria($event->getCriterias(), $criteria);

        $sorting = [];
        if ($requestConfiguration->isSortable()) {
            $sorting = $requestConfiguration->getSorting();
        }

        if ($requestConfiguration->isPaginated()) {
            return $repository->createPaginator($criteria, $sorting);
        }

        return $repository->findBy($criteria, $sorting, $requestConfiguration->getLimit());
    }

    private function injectExpression(array $expressions, QueryBuilder $queryBuilder, &$arguments): void
    {
        if($expressions) {
            foreach($expressions as $expression) {
                $queryBuilder->andWhere($expression);
            }

            array_push($arguments, $queryBuilder);
        }
    }

    private function injectCriteria(array $eventCriterias, &$criterias): void
    {
        if($eventCriterias) {
            foreach($eventCriterias as $criteria) {
                $criterias = array_merge($criterias, $criteria);
            }
        }
    }
}
