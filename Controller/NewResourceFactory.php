<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Owl\Bridge\SyliusResource\Factory\Resource\ParentableFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

final class NewResourceFactory implements NewResourceFactoryInterface
{
    public function create(RequestConfiguration $requestConfiguration, FactoryInterface $factory, array $resourceParents = []): ResourceInterface
    {
        if ($factory instanceof ParentableFactoryInterface) {
            $factory->setResourceParents($resourceParents);
        }

        if (null === $method = $requestConfiguration->getFactoryMethod()) {
            /** @var ResourceInterface $resource */
            $resource = $factory->createNew();

            return $resource;
        }

        if (is_array($method) && 2 === count($method)) {
            $factory = $method[0];
            $method = $method[1];
        }

        $arguments = array_values($requestConfiguration->getFactoryArguments());

        return $factory->$method(...$arguments);
    }
}
