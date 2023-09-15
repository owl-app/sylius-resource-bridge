<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Orm;

use Doctrine\ORM\EntityRepository;
use Pagerfanta\Pagerfanta;

interface CollectionProviderInterface
{
    public const TYPE = 'collection';

    public function get(EntityRepository $repository, ?array $criteria = [], ?array $repositoryOptions = [], array $sorting = [], bool $isPaginated = false): array|Pagerfanta;
}
