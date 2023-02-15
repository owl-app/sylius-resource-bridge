<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Common\Applicator;

use Doctrine\Orm\QueryBuilder as ORMQueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;

interface ResourceFilterApplicatorInterface
{
    public function apply(ORMQueryBuilder|DBALQueryBuilder $repository, string $resourceClass, string $nameAction): void;
}
