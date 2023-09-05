<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Orm\Filter;

use Owl\Bridge\SyliusResource\Doctrine\Common\FilterInterface as BaseFilterInterface;
use Doctrine\ORM\QueryBuilder;

interface FilterInterface extends BaseFilterInterface
{
    public function apply(QueryBuilder $queryBuilder, string $model): void;
}
