<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Mview\View;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\Mview\Config;
use Magento\Framework\Mview\View\CollectionInterface;
use Magento\Framework\Mview\ViewInterface;

/**
 * Class responsible for handling subscription.
 */
class Subscription extends \Magento\Framework\Mview\View\Subscription
{
    /**
     * Main product entity table name.
     */
    private const PRODUCT_ENTITY_TABLE = 'catalog_product_entity';

    /**
     * @var string[]
     */
    private array $supportedViewIds;

    /**
     * @param ResourceConnection $resource
     * @param TriggerFactory $triggerFactory
     * @param CollectionInterface $viewCollection
     * @param ViewInterface $view
     * @param string $tableName
     * @param string $columnName
     * @param array $ignoredUpdateColumns
     * @param array $ignoredUpdateColumnsBySubscription
     * @param Config|null $mviewConfig
     * @param array $supportedViewIds
     */
    public function __construct(
        ResourceConnection $resource,
        TriggerFactory $triggerFactory,
        CollectionInterface $viewCollection,
        ViewInterface $view,
        $tableName,
        $columnName,
        $ignoredUpdateColumns = [],
        $ignoredUpdateColumnsBySubscription = [],
        Config $mviewConfig = null,
        array $supportedViewIds = []
    ) {
        parent::__construct(
            $resource,
            $triggerFactory,
            $viewCollection,
            $view,
            $tableName,
            $columnName,
            $ignoredUpdateColumns,
            $ignoredUpdateColumnsBySubscription,
            $mviewConfig
        );

        $this->supportedViewIds = $supportedViewIds;
    }

    /**
     * @inheritdoc
     */
    protected function buildStatement(string $event, ViewInterface $view): string
    {
        $statement = parent::buildStatement($event, $view);

        if (false === $this->isValidMaterializedView($view)) {
            return $statement;
        }

        switch ($event) {
            case Trigger::EVENT_INSERT:
            case Trigger::EVENT_UPDATE:
                $eventType = 'NEW';
                break;
            case Trigger::EVENT_DELETE:
                $eventType = 'OLD';
                break;
            default:
                return $statement;
        }

        return $this->buildEntityIdStatementByEventType($eventType) . $statement;
    }

    /**
     * @inheritdoc
     */
    public function getEntityColumn(string $prefix, ViewInterface $view): string
    {
        if (false === $this->isValidMaterializedView($view)) {
            return parent::getEntityColumn($prefix, $view);
        }

        return '@entity_id';
    }

    /**
     * Validate if materialized view is valid for custom subscription model.
     *
     * @param ViewInterface $view
     *
     * @return bool
     */
    private function isValidMaterializedView(ViewInterface $view): bool
    {
        return false !== \in_array($view->getId(), $this->supportedViewIds, true);
    }

    /**
     * Build part of trigger body.
     *
     * @param string $eventType
     *
     * @return string
     */
    private function buildEntityIdStatementByEventType(string $eventType): string
    {
        $select = $this->connection
            ->select()
            ->from($this->connection->getTableName(self::PRODUCT_ENTITY_TABLE), ['entity_id'])
            ->where(\sprintf('sku = %s.sku', $eventType))
            ->assemble();

        return \sprintf('SET @entity_id = (%s);', $select) . PHP_EOL;
    }
}
