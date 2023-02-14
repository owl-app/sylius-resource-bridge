<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\ExpressionLanguage;

use Psr\Cache\CacheItemPoolInterface;
use Sylius\Bundle\ResourceBundle\ExpressionLanguage\NotNullExpressionFunctionProvider;
use Symfony\Component\DependencyInjection\ExpressionLanguage as BaseExpressionLanguage;

final class ExpressionLanguage extends BaseExpressionLanguage
{
    public function __construct(CacheItemPoolInterface $cache = null, array $providers = [])
    {
        array_unshift($providers, new NotNullExpressionFunctionProvider());

        parent::__construct($cache, $providers);
    }
}
