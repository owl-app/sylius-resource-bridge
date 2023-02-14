<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\SingleResourceProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Owl\Bridge\SyliusResource\Doctrine\Orm\ItemProviderInterface;

final class SingleResourceProvider implements SingleResourceProviderInterface
{
    public function __construct(
        private ItemProviderInterface $itemProvider
    ) {
    }

    public function get(RequestConfiguration $requestConfiguration, RepositoryInterface $repository): ?ResourceInterface
    {
        $method = $requestConfiguration->getRepositoryMethod();

        if (null !== $method) {
            if (is_array($method) && 2 === count($method)) {
                $repository = $method[0];
                $method = $method[1];
            }

            $repositoryOptions = [
                'method' => $method,
                'arguments' => array_values($requestConfiguration->getRepositoryArguments())
            ];

            return $this->itemProvider->get($repository, null, $repositoryOptions);
        }

        $criteria = [];
        $request = $requestConfiguration->getRequest();

        if ($request->attributes->has('id')) {
            $criteria = ['identifier' => $request->attributes->get('id')];

            return $this->itemProvider->get($repository, $criteria);
        }

        if ($request->attributes->has('slug')) {
            $criteria = ['slug' => $request->attributes->get('slug')];
        }

        $criteria = array_merge($criteria, $requestConfiguration->getCriteria());

        return $this->itemProvider->get($repository, $criteria);
    }
}
