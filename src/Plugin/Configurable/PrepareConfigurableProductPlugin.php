<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Plugin\Configurable;

use Divante\VsbridgeIndexerCatalog\Model\Indexer\DataProvider\Product\Configurable\PrepareConfigurableProduct;

/**
 * Plugin responsible for applying correct salable qty for configurable product based on children.
 */
class PrepareConfigurableProductPlugin
{
    /**
     * Set correct salable qty for configurable product based on children.
     *
     * @param PrepareConfigurableProduct $subject
     * @param array $result
     *
     * @return array
     */
    public function afterExecute(PrepareConfigurableProduct $subject, array $result): array
    {
        $configurableChildren = $result['configurable_children'];
        $salableQty = 0;

        foreach ($configurableChildren as $child) {
            $salableQty += $child['stock']['salable_qty'] ?? 0;
        }

        $result['stock']['salable_qty'] = $salableQty;
        //TODO: Test is_in_stock & stock_status when using backorders/not-managed-stock
        $result['stock']['is_in_stock'] = $result['stock_status'] = (bool)($salableQty > 0);

        return $result;
    }
}
