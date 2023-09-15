<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use Sylius\Bundle\ResourceBundle\Controller\NewResourceFactoryInterface as SyliusNewResourceFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface NewResourceFactoryInterface extends SyliusNewResourceFactoryInterface
{
    public function create(RequestConfiguration $requestConfiguration, FactoryInterface $factory, array $resourceParents = []): ResourceInterface;
}
