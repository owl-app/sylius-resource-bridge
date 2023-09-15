<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Grid\Data;

use Owl\Bridge\SyliusResource\Doctrine\Common\Applicator\ResourceFilterApplicatorInterface;
use Sylius\Bundle\GridBundle\Doctrine\DataSourceInterface;
use Sylius\Bundle\GridBundle\Doctrine\DataSourceInterface as DoctrineDataSourceIntrface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Data\DataSourceProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Filtering\FiltersApplicatorInterface;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Sorting\SorterInterface;

final class DataProvider implements DataProviderInterface
{
    public function __construct(
        private DataSourceProviderInterface $dataSourceProvider,
        private FiltersApplicatorInterface $filtersApplicator,
        private SorterInterface $sorter,
        private ResourceFilterApplicatorInterface $resourceFilterApplicator,
    ) {
    }

    public function getData(Grid $grid, Parameters $parameters)
    {
        /** @var DataSourceInterface|DoctrineDataSourceIntrface $dataSource */
        $dataSource = $this->dataSourceProvider->getDataSource($grid, $parameters);
        $driverConfiguration = $grid->getDriverConfiguration();

        $this->resourceFilterApplicator->apply(
            $dataSource->getQueryBuilder(),
            $driverConfiguration['class'],
            $driverConfiguration['pre_load_event'],
        );

        $this->filtersApplicator->apply($dataSource, $grid, $parameters);
        $this->sorter->sort($dataSource, $grid, $parameters);

        return $dataSource->getData($parameters);
    }
}
