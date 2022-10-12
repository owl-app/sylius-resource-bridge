<?php

declare(strict_types=1);

namespace Owl\Bridge\SyliusResourceBridge\Event;

use Sylius\Component\Grid\Data\ExpressionBuilderInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class CollectionPreLoadEvent extends Event
{
    public const EVENT_MAIN_NAME = 'owl.grid.pre_load_collection';

    public const EVENT_NAME = 'pre_load_collection';

    private string $dataClass;

    private ?ExpressionBuilderInterface $expressionBuilder;

    private array $expressions = [];

    private array $criterias = [];

    public function __construct(string $dataClass, ExpressionBuilderInterface $expressionBuilder = null)
    {
        $this->dataClass = $dataClass;
        $this->expressionBuilder = $expressionBuilder;
    }

    public function getDataClass(): string
    {
        return $this->dataClass;
    }

    public function getExpressionBuilder(): ?ExpressionBuilderInterface
    {
        return $this->expressionBuilder;
    }

    public function getExpressions()
    {
        return $this->expressions;
    }

    public function addExpression($expression): void
    {
        $this->expressions[] = $expression;
    }

    public function getCriterias(): ?array
    {
        return $this->criterias;
    }

    public function addCriteria(array $criteria): void
    {
        $this->criterias[] = $criteria;
    }
}
