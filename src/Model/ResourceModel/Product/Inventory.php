<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Model\ResourceModel\Product;

use Divante\VsbridgeIndexerMsi\Api\GetStockIdBySalesChannelCodeInterface;
use Divante\VsbridgeIndexerMsi\Model\GetStockIndexTableByStore;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Inventory
 */
class Inventory
{

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var GetStockIndexTableByStore
     */
    private $getStockIndexTableByStore;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetStockIdBySalesChannelCodeInterface
     */
    private $getStockIdBySalesChannelCode;

    /**
     * Inventory constructor.
     *
     * @param GetStockIndexTableByStore $getStockIndexTableByStore
     * @param GetStockIdBySalesChannelCodeInterface $getStockIdBySalesChannelCode
     * @param ResourceConnection $resourceModel
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GetStockIndexTableByStore $getStockIndexTableByStore,
        GetStockIdBySalesChannelCodeInterface $getStockIdBySalesChannelCode,
        ResourceConnection $resourceModel,
        StoreManagerInterface $storeManager
    ) {
        $this->getStockIndexTableByStore = $getStockIndexTableByStore;
        $this->resource = $resourceModel;
        $this->storeManager = $storeManager;
        $this->getStockIdBySalesChannelCode = $getStockIdBySalesChannelCode;
    }

    /**
     * @param int $storeId
     * @param array $skuList
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loadInventory(int $storeId, array $skuList): array
    {
        return $this->getInventoryData($storeId, $skuList);
    }

    /**
     * @param int $storeId
     * @param array $skuList
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getInventoryData(int $storeId, array $skuList): array
    {
        $connection = $this->resource->getConnection();
        $select = $this->getInventoryDataQuery($storeId, $skuList);

        return $connection->fetchAssoc($select);
    }

    /**
     * Get inventory query that includes reservations
     *
     * @param int $storeId
     * @param array $skuList
     * @return Select
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getInventoryDataQuery(int $storeId, array $skuList): Select
    {
        $connection = $this->resource->getConnection();
        $salableQtyState = \sprintf('(inv.%s + SUM(res.%s))', IndexStructure::QUANTITY, ReservationInterface::QUANTITY);
        $salableQtyCase = $connection->getCaseSql('', [$salableQtyState . ' > 0' => $salableQtyState], 0);

        return $connection->select()
            ->from(['inv' => $this->getStockIndexTableByStore->execute($storeId)], [])
            ->joinLeft(
                ['res' => $this->resource->getTableName('inventory_reservation')],
                \implode(
                    ' AND ',
                    [
                        \sprintf('res.%s = inv.%s', IndexStructure::SKU, ReservationInterface::SKU),
                        $connection->quoteInto(
                            \sprintf('res.%s = ?', ReservationInterface::STOCK_ID),
                            $this->getWebsiteStockId($storeId)
                        ),
                    ]
                ),
                []
            )
            ->where(\sprintf('inv.%s IN (?)', IndexStructure::SKU), $skuList)
            ->group('inv.' . IndexStructure::SKU)
            ->columns(
                [
                    'sku' => 'inv.' . IndexStructure::SKU,
                    'qty' => 'inv.' . IndexStructure::QUANTITY,
                    'salable_qty' => $connection->getCheckSql(
                        \sprintf('res.%s IS NULL', ReservationInterface::QUANTITY),
                        'inv.' . IndexStructure::QUANTITY,
                        $salableQtyCase
                    ),
                    'is_salable' => 'inv.' . IndexStructure::IS_SALABLE,
                ]
            );
    }

    /**
     * Retrieve website stock id.
     *
     * @param int $storeId
     *
     * @return int
     *
     * @throws NoSuchEntityException
     */
    private function getWebsiteStockId(int $storeId): int
    {
        $websiteCode = $this->storeManager->getStore($storeId)->getWebsite()->getCode();

        return $this->getStockIdBySalesChannelCode->execute($websiteCode);
    }
}
