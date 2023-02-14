<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\NewResourceFactoryInterface as SyliusNewResourceFactoryInterface;

interface NewResourceFactoryInterface extends SyliusNewResourceFactoryInterface
{
    public function create(RequestConfiguration $requestConfiguration, FactoryInterface $factory, array $resourceParents = []): ResourceInterface;
}
