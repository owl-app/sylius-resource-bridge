<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Common\Applicator;

use Doctrine\Orm\QueryBuilder as ORMQueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Sylius\Component\Registry\ServiceRegistryInterface;

final class ResourceFilterApplicator implements ResourceFilterApplicatorInterface
{
    public function __construct(private ServiceRegistryInterface $registryFilter)
    {

    }

    public function apply(ORMQueryBuilder|DBALQueryBuilder $queryBuilder, string $resourceClass, string $action): void
    {
        foreach($this->registryFilter->all() as $filter) {
            if($filter->support($resourceClass, $action))
            {
                $filter->apply($queryBuilder, $resourceClass);
            }
        }
    }
}
