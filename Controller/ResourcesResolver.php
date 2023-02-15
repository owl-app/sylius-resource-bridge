<?php
declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\ResourcesResolverInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Owl\Bridge\SyliusResource\Doctrine\Orm\CollectionProviderInterface;

final class ResourcesResolver implements ResourcesResolverInterface
{
    public function __construct(
        private ResourcesResolverInterface $decoratedResolver,
        private CollectionProviderInterface $collectionProvider
    ) {

    }

    /**
     * @psalm-suppress MissingReturnType
     */
    public function getResources(RequestConfiguration $requestConfiguration, RepositoryInterface $repository)
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

            return $this->collectionProvider->get($repository, null, $repositoryOptions);
        }

        $criteria = [];
        if ($requestConfiguration->isFilterable()) {
            $criteria = $requestConfiguration->getCriteria();
        }

        $sorting = [];
        if ($requestConfiguration->isSortable()) {
            $sorting = $requestConfiguration->getSorting();
        }

        return $this->collectionProvider->get($repository, $criteria, null, $sorting, $requestConfiguration->isPaginated());
    }
}
