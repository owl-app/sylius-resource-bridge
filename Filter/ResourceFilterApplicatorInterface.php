<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Filter;

use Doctrine\Orm\QueryBuilder;

interface ResourceFilterApplicatorInterface
{
    public function apply(QueryBuilder $repository, string $resourceClass, string $nameAction): void;
}
