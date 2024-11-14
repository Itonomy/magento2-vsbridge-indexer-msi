<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Plugin\Mview;

use Magento\Framework\Mview\View\SubscriptionFactory;
use Magento\Framework\Mview\View\SubscriptionInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Custom implementation of table subscription model.
 * @see \Magento\Framework\Mview\View\SubscriptionFactory
 * Can use subscriptionModel, but deprecated in vendor/magento/framework/Mview/etc/mview.xsd.
 */
class SubscriptionFactoryPlugin
{
    /**
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @var string[]
     */
    private array $subscriptionModels;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $subscriptionModels
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $subscriptionModels = []
    ) {
        $this->objectManager = $objectManager;
        $this->subscriptionModels = $subscriptionModels;
    }

    /**
     * Create subscription model.
     *
     * @param SubscriptionFactory $subject
     * @param callable $proceed
     * @param array $data
     *
     * @return SubscriptionInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCreate(
        SubscriptionFactory $subject,
        callable $proceed,
        array $data = []
    ): SubscriptionInterface {
        if (isset($data['tableName'], $this->subscriptionModels[$data['tableName']])) {
            return $this->objectManager->create($this->subscriptionModels[$data['tableName']], $data);
        }

        return $proceed($data);
    }
}
