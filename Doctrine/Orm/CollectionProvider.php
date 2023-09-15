<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use Owl\Bridge\SyliusResource\Doctrine\Common\Applicator\ResourceFilterApplicatorInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class CollectionProvider implements CollectionProviderInterface
{
    public function __construct(
        private ResourceFilterApplicatorInterface $resourceFilterApplicator,
        private QueryBuilderApplicatorInterface $queryBuilderApplicator,
    ) {
    }

    public function get(RepositoryInterface $repository, ?array $criteria = [], ?array $repositoryOptions = [], array $sorting = [], bool $isPaginated = false): array|Pagerfanta
    {
        $queryBuilder = $this->getQueryBuilder($repository, $repositoryOptions);

        if ($criteria) {
            $this->queryBuilderApplicator->applyFilters($queryBuilder, $repository->getClassName(), $criteria);
        }

        if ($sorting) {
            $this->queryBuilderApplicator->applySort($queryBuilder, $repository->getClassName(), $sorting);
        }

        $this->resourceFilterApplicator->apply($queryBuilder, $repository->getClassName(), self::TYPE);

        if ($isPaginated) {
            return new Pagerfanta(new QueryAdapter($queryBuilder, false, false));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    private function getQueryBuilder(RepositoryInterface $repository, ?array $repositoryOptions = []): QueryBuilder
    {
        if (isset($repositoryOptions['method'])) {
            $method = $repositoryOptions['method'];
            $arguments = $repositoryOptions['arguments'] ?? [];

            return $repository->$method(...$arguments);
        }

        return $repository->createQueryBuilder('o');
    }
}
