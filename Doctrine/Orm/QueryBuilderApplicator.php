<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResource\Doctrine\Orm;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration as SyliusRequestConfiguration;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\ClassMetadata;

final class QueryBuilderApplicator implements QueryBuilderApplicatorInterface
{
    public function applyFilters(QueryBuilder $queryBuilder, string $resourceClass, array $criteria): void
    {
        $metadata = $this->getClassMetadata($queryBuilder, $resourceClass);

        foreach ($criteria as $property => $value) {
            if (!in_array($property, array_merge($metadata->getAssociationNames(), $metadata->getFieldNames()), true)) {
                continue;
            }

            $name = $this->getPropertyName($property);

            if (null === $value) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNull($name));
            } elseif (is_array($value)) {
                $queryBuilder->andWhere($queryBuilder->expr()->in($name, $value));
            } elseif ('' !== $value) {
                $parameter = str_replace('.', '_', $property);
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->eq($name, ':' . $parameter))
                    ->setParameter($parameter, $value)
                ;
            }
        }
    }

    public function applySort(QueryBuilder $queryBuilder, string $resourceClass, array $sorting): void
    {
        $metadata = $this->getClassMetadata($queryBuilder, $resourceClass);

        foreach ($sorting as $property => $order) {
            if (!in_array($property, array_merge($metadata->getAssociationNames(), $metadata->getFieldNames()), true)) {
                continue;
            }

            if (!empty($order)) {
                $queryBuilder->addOrderBy($this->getPropertyName($property), $order);
            }
        }
    }

    /**
     * @psalm-return \Doctrine\ORM\Mapping\ClassMetadata<object>
     */
    private function getClassMetadata(QueryBuilder $queryBuilder, string $resourceClass): \Doctrine\ORM\Mapping\ClassMetadata
    {
        return $queryBuilder->getEntityManager()->getClassMetadata($resourceClass);
    }

    private function getPropertyName(string $name): string
    {
        if (false === strpos($name, '.')) {
            return 'o' . '.' . $name;
        }

        return $name;
    }
}
