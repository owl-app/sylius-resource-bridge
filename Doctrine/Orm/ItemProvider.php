<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Orm;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Owl\Bridge\SyliusResource\Filter\ResourceFilterApplicatorInterface;
use Doctrine\ORM\QueryBuilder;
use Sylius\Component\Resource\Model\ResourceInterface;

final class ItemProvider implements ItemProviderInterface
{
    public function __construct(
        private ResourceFilterApplicatorInterface $resourceFilterApplicator,
        private QueryBuilderApplicatorInterface $queryBuilderApplicator
    ) {

    }

    public function get(RepositoryInterface $repository, ?array $criteria = [], ?array $repositoryOptions = []): ?ResourceInterface
    {
        $method = $this->getMethod($repositoryOptions);
        $criteria = $this->getCriteria($repository, $method, $repositoryOptions['arguments'] ?? [], $criteria );
        $queryBuilder = $this->getQueryBuilder($repository, $method, $repositoryOptions['arguments'] ?? []);

        if($criteria) {
            $this->queryBuilderApplicator->applyFilters($queryBuilder, $repository->getClassName(), $criteria);
        }

        $this->resourceFilterApplicator->apply($queryBuilder, $repository->getClassName(), self::TYPE);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function getMethod(?array $repositoryOptions = []): ?string
    {
        if(isset($repositoryOptions['method'])) {
            if ($repositoryOptions['method'] === 'find') {
                return 'findOneBy';
            }

            return $repositoryOptions['method'];
        }

        return null;
    }

    private function getCriteria(RepositoryInterface $repository, ?string $method, array $arguments, ?array $criteria): array
    {
        if (!is_null($method) && ($method === 'findOneBy' || (!method_exists($repository, $method) && str_starts_with($method, 'findOneBy')))) {
            $identifier = $this->getIdentifierFieldName($repository);

            return [$identifier => end($arguments)];
        }

        if(isset($criteria['identifier'])) {
            $criteria = array_merge($criteria, [$this->getIdentifierFieldName($repository) => $criteria['identifier']]);

            unset($criteria['identifier']);
        }

        return $criteria;
    }

    private function getQueryBuilder(RepositoryInterface $repository, ?string $method, array $arguments): QueryBuilder
    {
        if(!is_null($method) && $method !== 'findOneBy') {
            return $repository->$method($arguments);
        }

        return $repository->createQueryBuilder('o');
    }

    private function getIdentifierFieldName(RepositoryInterface $repository): string
    {
        $entityManager = $repository->createQueryBuilder('o')->getEntityManager();
        $meta = $entityManager->getClassMetadata($repository->getClassName());
        
        return $meta->getSingleIdentifierFieldName();
    }
}
