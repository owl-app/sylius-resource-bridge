<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Orm;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Owl\Bridge\SyliusResource\Doctrine\Common\Applicator\ResourceFilterApplicatorInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

final class ItemProvider implements ItemProviderInterface
{
    public function __construct(
        private ResourceFilterApplicatorInterface $resourceFilterApplicator,
        private QueryBuilderApplicatorInterface $queryBuilderApplicator,
    ) {
    }

    public function get(EntityRepository $repository, ?array $criteria = [], ?array $repositoryOptions = []): ?ResourceInterface
    {
        $method = $this->getMethod($repositoryOptions);
        $criteria = $this->getCriteria($repository, $method, $repositoryOptions['arguments'] ?? [], $criteria);
        $queryBuilder = $this->getQueryBuilder($repository, $method, $repositoryOptions['arguments'] ?? []);

        if ($criteria) {
            $this->queryBuilderApplicator->applyFilters($queryBuilder, $repository->getClassName(), $criteria);
        }

        $this->resourceFilterApplicator->apply($queryBuilder, $repository->getClassName(), self::TYPE);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function getMethod(?array $repositoryOptions = []): ?string
    {
        if (isset($repositoryOptions['method'])) {
            if ($repositoryOptions['method'] === 'find') {
                return 'findOneBy';
            }

            return $repositoryOptions['method'];
        }

        return null;
    }

    private function getCriteria(EntityRepository $repository, ?string $method, array $arguments, ?array $criteria): ? array
    {
        if (null !== $method && ($method === 'findOneBy' || (!method_exists($repository, $method) && str_starts_with($method, 'findOneBy')))) {
            $identifier = $this->getIdentifierFieldName($repository);

            return [$identifier => end($arguments)];
        }

        if (isset($criteria['identifier'])) {
            $criteria = array_merge($criteria, [$this->getIdentifierFieldName($repository) => $criteria['identifier']]);

            unset($criteria['identifier']);
        }

        return $criteria;
    }

    private function getQueryBuilder(EntityRepository $repository, ?string $method, array $arguments): QueryBuilder
    {
        if (null !== $method && $method !== 'findOneBy') {
            return $repository->$method($arguments);
        }

        return $repository->createQueryBuilder('o');
    }

    private function getIdentifierFieldName(EntityRepository $repository): string
    {
        $entityManager = $repository->createQueryBuilder('o')->getEntityManager();
        $meta = $entityManager->getClassMetadata($repository->getClassName());

        return $meta->getSingleIdentifierFieldName();
    }
}
