<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Common\Applicator;

use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;

interface ResourceFilterApplicatorInterface
{
    public function apply(ORMQueryBuilder|DBALQueryBuilder $queryBuilder, string $resourceClass, string $nameAction): void;
}
