<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\Grid\View;

use Owl\Bridge\SyliusResourceBridge\Event\CollectionPreLoadEvent;
use Sylius\Bundle\ResourceBundle\Controller\ParametersParserInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Grid\View\ResourceGridView;
use Sylius\Bundle\ResourceBundle\Grid\View\ResourceGridViewFactoryInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Resource\Metadata\MetadataInterface;

final class ResourceGridViewFactory implements ResourceGridViewFactoryInterface
{
    /** @var DataProviderInterface */
    private $dataProvider;

    /** @var ParametersParserInterface */
    private $parametersParser;

    public function __construct(DataProviderInterface $dataProvider, ParametersParserInterface $parametersParser)
    {
        $this->dataProvider = $dataProvider;
        $this->parametersParser = $parametersParser;
    }

    public function create(
        Grid $grid,
        Parameters $parameters,
        MetadataInterface $metadata,
        RequestConfiguration $requestConfiguration
    ): ResourceGridView {
        $driverConfiguration = $grid->getDriverConfiguration();
        $request = $requestConfiguration->getRequest();

        if(!isset($driverConfiguration['pre_load_event'])) {
            $driverConfiguration['pre_load_event'] = sprintf('%s.%s.%s', $metadata->getApplicationName(), $metadata->getName(), CollectionPreLoadEvent::EVENT_NAME);
        }

        $grid->setDriverConfiguration($this->parametersParser->parseRequestValues($driverConfiguration, $request));

        return new ResourceGridView($this->dataProvider->getData($grid, $parameters), $grid, $parameters, $metadata, $requestConfiguration);
    }
}
