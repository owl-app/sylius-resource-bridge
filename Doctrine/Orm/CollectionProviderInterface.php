<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Orm;

use Pagerfanta\Pagerfanta;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface CollectionProviderInterface
{
    public const TYPE = 'collection';

    public function get(RepositoryInterface $repository, ?array $criteria = [], ?array $repositoryOptions = [], array $sorting = [], bool $isPaginated = false): array|Pagerfanta;
}
