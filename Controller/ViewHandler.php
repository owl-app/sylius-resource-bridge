<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Controller;

use FOS\RestBundle\View\ConfigurableViewHandlerInterface;
use FOS\RestBundle\View\View;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\ViewHandlerInterface;
use Symfony\Component\HttpFoundation\Response;

final class ViewHandler implements ViewHandlerInterface
{
    public function __construct(private ViewHandlerInterface $decorated, private ConfigurableViewHandlerInterface $restViewHandler)
    {
        $this->decorated = $decorated;
        $this->restViewHandler = $restViewHandler;
    }

    /**
     * @param RequestConfiguration $requestConfiguration
     */
    public function handle(SyliusRequestConfiguration $requestConfiguration, View $view): Response
    {
        if ($requestConfiguration->isAjaxRequest()) {
            $this->restViewHandler->setExclusionStrategyGroups($requestConfiguration->getAjaxSerializationGroups());

            if ($version = $requestConfiguration->getSerializationVersion()) {
                $this->restViewHandler->setExclusionStrategyVersion($version);
            }

            $view->getContext()->enableMaxDepth();

            return $this->restViewHandler->handle($view);
        }

        return $this->decorated->handle($requestConfiguration, $view);
    }
}
