<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Common\Applicator;

use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use Sylius\Component\Registry\ServiceRegistryInterface;

final class ResourceFilterApplicator implements ResourceFilterApplicatorInterface
{
    public function __construct(private ServiceRegistryInterface $registryFilter)
    {
    }

    public function apply(ORMQueryBuilder|DBALQueryBuilder $queryBuilder, string $resourceClass, string $nameAction): void
    {
        foreach ($this->registryFilter->all() as $filter) {
            if ($filter->support($resourceClass, $nameAction)) {
                $filter->apply($queryBuilder, $resourceClass);
            }
        }
    }
}
