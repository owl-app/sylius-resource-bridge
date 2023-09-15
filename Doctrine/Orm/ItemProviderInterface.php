<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Orm;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ItemProviderInterface
{
    public const TYPE = 'item';

    public function get(RepositoryInterface $repository, ?array $criteria = [], ?array $repositoryOptions = []): ?ResourceInterface;
}
