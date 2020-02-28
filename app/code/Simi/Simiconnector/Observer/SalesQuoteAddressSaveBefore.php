<?php
/**
 * Created by PhpStorm.
 * User: codynguyen
 * Date: 2/27/19
 * Time: 10:36 AM
 */

namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesQuoteAddressSaveBefore implements ObserverInterface
{
    private $simiObjectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    ) {
        $this->simiObjectManager = $simiObjectManager;
    }

    public function _getCart()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
    }

    public function _getQuote()
    {
        $cart = $this->_getCart();
        if(!$cart)
            return;
        return $cart->getQuote();
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $quote = $this->_getQuote();
            $coupon = $quote->getCouponCode();
            if ($coupon && $coupon != '') {
                $isApp = strpos($this->simiObjectManager
                    ->get('\Magento\Framework\Url')->getCurrentUrl(), 'simiconnector');
                $pre_fix = (string)$this->simiObjectManager->create('Simi\Simiconnector\Helper\Data')
                    ->getStoreConfig('simiconnector/general/app_dedicated_coupon');
                if ($pre_fix && ($pre_fix != '') && ($isApp === false) && $coupon) {
                    if (strpos(strtolower($coupon), strtolower($pre_fix)) !== false) {
                        $quote->setCouponCode('')->collectTotals()->save();
                    }
                }
            }
        } catch (\Exception $e) {

        }
    }
}
