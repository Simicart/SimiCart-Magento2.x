<?php

namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderSaveAfter implements ObserverInterface
{

    private $simiObjectManager;
    public $new_added_product_sku = '';

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    ) {
        $this->simiObjectManager = $simiObjectManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($platform =
            $this->_getCheckoutSession()->getData('simiconnector_platform')) {
            try {
                $orderId = $observer->getOrder()->getId();
                $existedTransaction = $this->simiObjectManager
                    ->create('Simi\Simiconnector\Model\Appreport')
                    ->getCollection()
                    ->addFieldToFilter('order_id', $orderId)
                    ->getFirstItem();
                if($orderId && (!$existedTransaction || !$existedTransaction->getId())) {
                    $newTransaction = $this->simiObjectManager->create('Simi\Simiconnector\Model\Appreport');
                    $newTransaction->setOrderId($orderId);
                    $platform = ($platform == 'pwa')?'1':'0';
                    $newTransaction->setPlatform($platform);
                    $newTransaction->save();
                }
            } catch (\Exception $exc) {

            }
        }
    }

    public function _getCheckoutSession()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Session');
    }
}
