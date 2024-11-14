<?php
/**
 * @package  Divante\VsbridgeIndexerMsi
 * @author Agata Firlejczyk <afirlejczyk@divante.pl>
 * @copyright 2019 Divante Sp. z o.o.
 * @license See LICENSE_DIVANTE.txt for license details.
 */

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Model;

use Divante\VsbridgeIndexerCatalog\Api\LoadInventoryInterface;
use Divante\VsbridgeIndexerMsi\Model\ResourceModel\Product\Inventory as InventoryResource;

/**
 * Class LoadInventory
 */
class LoadInventory implements LoadInventoryInterface
{
    /**
     * @var InventoryResource
     */
    private $resource;

    /**
     * LoadChildrenInventory constructor.
     *
     * @param InvetoryResource $resource
     */
    public function __construct(InventoryResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $indexData, int $storeId): array
    {
        $productIdBySku = $this->getIdBySku($indexData);
        $rawInventory = $this->resource->loadInventory($storeId, array_keys($productIdBySku));
        $rawInventoryByProductId = [];

        foreach ($rawInventory as $sku => $row) {
            //TODO: Test is_in_stock & stock_status when using backorders/not-managed-stock
            $row['is_in_stock'] = $row['stock_status'] = (bool)($row['salable_qty'] > 0);
            $row['product_id'] = $productIdBySku[$sku];
            unset($row['sku']);
            $rawInventoryByProductId[$row['product_id']] = $row;
        }

        return $rawInventoryByProductId;
    }

    /**
     * @param array $indexData
     *
     * @return array
     */
    private function getIdBySku(array $indexData): array
    {
        $idBySku = [];

        foreach ($indexData as $productId => $product) {
            $idBySku[$product['sku']] = $productId;
        }

        return $idBySku;
    }
}
