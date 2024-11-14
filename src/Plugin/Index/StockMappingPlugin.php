<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Plugin\Index;

use Divante\VsbridgeIndexerCatalog\Index\Mapping\StockMapping;
use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;

/**
 * Plugin responsible for adding additional ES index stock mapping.
 */
class StockMappingPlugin
{
    /**
     * Add additional ES index mapping.
     *
     * @param StockMapping $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGet(StockMapping $subject, array $result): array
    {
        $result['salable_qty'] = ['type' => FieldInterface::TYPE_DOUBLE];

        return $result;
    }
}
