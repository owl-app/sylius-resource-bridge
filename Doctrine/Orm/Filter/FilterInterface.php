<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Orm\Filter;

use Doctrine\ORM\QueryBuilder;
use Owl\Bridge\SyliusResource\Doctrine\Common\FilterInterface as BaseFilterInterface;

interface FilterInterface extends BaseFilterInterface
{
    public function apply(QueryBuilder $queryBuilder, string $model): void;
}
