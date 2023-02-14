<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Filter;

use Doctrine\Orm\QueryBuilder;
use Sylius\Component\Registry\ServiceRegistryInterface;

final class ResourceFilterApplicator implements ResourceFilterApplicatorInterface
{
    public function __construct(private ServiceRegistryInterface $registryFilter)
    {

    }

    public function apply(QueryBuilder $queryBuilder, string $resourceClass, string $action): void
    {
        foreach($this->registryFilter->all() as $filter) {
            if($filter->support($resourceClass, $action)) {
                $filter->apply($queryBuilder, $resourceClass);
            }
        }
    }
}
