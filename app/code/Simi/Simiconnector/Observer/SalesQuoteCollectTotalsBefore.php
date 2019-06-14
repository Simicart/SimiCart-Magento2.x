<?php
/**
 * Created by PhpStorm.
 * User: codynguyen
 * Date: 2/27/19
 * Time: 10:36 AM
 */

namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesQuoteCollectTotalsBefore implements ObserverInterface
{
    private $simiObjectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    ) {
        $this->simiObjectManager = $simiObjectManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $coupon = $quote->getCouponCode();
        $isApp = strpos($this->simiObjectManager
            ->get('\Magento\Framework\Url')->getCurrentUrl(), 'simiconnector');
        $pre_fix = (string) $this->simiObjectManager->create('Simi\Simiconnector\Helper\Data')
            ->getStoreConfig('simiconnector/general/app_dedicated_coupon');
        if ($pre_fix && ($pre_fix != '') && ($isApp === false) && $coupon) {
            if (strpos(strtolower($coupon), strtolower($pre_fix)) !== false) {
                $quote->setCouponCode('')->save();
            }
        }
    }
}
