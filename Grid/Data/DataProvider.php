<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\Grid\Data;

use Owl\Bridge\SyliusResourceBridge\Controller\CollectionEventDispatcherInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Data\DataSourceProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Filtering\FiltersApplicatorInterface;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Sorting\SorterInterface;

final class DataProvider implements DataProviderInterface
{
    private DataSourceProviderInterface $dataSourceProvider;

    private FiltersApplicatorInterface $filtersApplicator;

    private SorterInterface $sorter;

    private CollectionEventDispatcherInterface $eventDispatcher;

    public function __construct(
        DataSourceProviderInterface $dataSourceProvider,
        FiltersApplicatorInterface $filtersApplicator,
        SorterInterface $sorter,
        CollectionEventDispatcherInterface $eventDispatcher
    ) {
        $this->dataSourceProvider = $dataSourceProvider;
        $this->filtersApplicator = $filtersApplicator;
        $this->sorter = $sorter;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getData(Grid $grid, Parameters $parameters)
    {
        $dataSource = $this->dataSourceProvider->getDataSource($grid, $parameters);
        $driverConfiguration = $grid->getDriverConfiguration();

        $event = $this->eventDispatcher->dispatch(
            $driverConfiguration['pre_load_event'] ?? null,
            $driverConfiguration['class'],
            $dataSource->getExpressionBuilder()
        );

        $this->injectExpression($event->getExpressions(), $dataSource);

        $this->filtersApplicator->apply($dataSource, $grid, $parameters);
        $this->sorter->sort($dataSource, $grid, $parameters);

        return $dataSource->getData($parameters);
    }

    private function injectExpression(array $expressions, DataSourceInterface $dataSource): void
    {
        if($expressions) {
            foreach($expressions as $expression) {
                $dataSource->restrict($expression);
            }
        }
    }
}
