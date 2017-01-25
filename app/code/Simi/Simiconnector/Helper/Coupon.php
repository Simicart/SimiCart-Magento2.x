<?php

/**
 * Coupon helper
 */
namespace Simi\Simiconnector\Helper;

class Coupon extends \Simi\Simiconnector\Helper\Data
{
    
    protected function _getCart()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Cart');
    }


    public function setCoupon($couponCode)
    {
        $this->_getCart()->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        $this->_getCart()->getQuote()->setCouponCode(strlen($couponCode) ? $couponCode : '')
                ->collectTotals()
                ->save();
        $total = $this->_getCart()->getQuote()->getShippingAddress()->getTotals();
        $return['discount'] = 0;
        if (isset($total['discount']) && $total['discount']->getValue()) {
            $return['discount'] = abs($total['discount']->getValue());
        }
        if (strlen($couponCode)) {
            if ($couponCode == $this->_getCart()->getQuote()->getCouponCode() && $return['discount'] != 0) {
                $message = __('Coupon code "%s" was applied.', $couponCode);
            } else {
                $message = __('Coupon code "%s" is not valid.', $couponCode);
            }
        } else {
            $message = __('Coupon code was canceled.', $couponCode);
        }
        return $message;
    }
}
