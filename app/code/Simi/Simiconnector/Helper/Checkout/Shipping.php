<?php

/**
 * Shipping helper
 */

namespace Simi\Simiconnector\Helper\Checkout;

class Shipping extends \Simi\Simiconnector\Helper\Data
{

    public function _getCheckoutSession()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Session');
    }

    public function _getOnepage()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Type\Onepage');
    }

    public function _getCart()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
    }

    public function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    public function saveShippingMethod($method_code)
    {
        if (!isset($method_code->method)) {
            return;
        }
        $method = $method_code->method;
        $quote = $this->_getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
        //    ->collectShippingRates()
            ->setShippingMethod($method);
        ;
    }

    public function getAddress()
    {
        return $this->_getCheckoutSession()->getShippingAddress();
    }

    public function getShippingPrice($price, $flag)
    {
        return $this->simiObjectManager->get('Simi\Simiconnector\Helper\Price')
                ->convertPrice($this->simiObjectManager->create('Magento\Tax\Helper\Data')
                        ->getShippingPrice($price, $flag, $this->getAddress()), false);
    }

    public function getMethods()
    {
        $quote = $this->_getCheckoutSession()->getQuote();
        if($quote->getIsVirtual()) {
            return [];
        }
        $shipping = $quote->getShippingAddress();
        //$shipping->collectShippingRates();
        $methods  = $shipping->getGroupedAllShippingRates();

        $list = [];
        foreach ($methods as $_ccode => $_carrier) {
            foreach ($_carrier as $_rate) {
                if ($_rate->getData('error_message') != null) {
                    continue;
                }
                $select = false;
                if ($shipping->getShippingMethod() != null && $shipping->getShippingMethod() == $_rate->getCode()) {
                    $select = true;
                }

                $s_fee      = $this->getShippingPrice($_rate->getPrice(), $this->simiObjectManager
                        ->create('Magento\Tax\Helper\Data')->displayShippingPriceIncludingTax());
                $s_fee_incl = $this->getShippingPrice($_rate->getPrice(), true);

                // if ($this->simiObjectManager->create('Magento\Tax\Helper\Data')
                //         ->displayShippingBothPrices() && $s_fee != $s_fee_incl) {
                    $list[] = [
                        's_method_id'           => $_rate->getId(),
                        's_method_code'         => $_rate->getCode(),
                        's_method_title'        => $_rate->getCarrierTitle(),
                        's_method_fee'          => $s_fee,
                        's_method_fee_incl_tax' => $s_fee_incl,
                        's_method_name'         => $_rate->getMethodTitle(),
                        's_method_selected'     => $select,
                        's_carrier_code'        => $_rate->getCarrier(),
                        's_carrier_title'       => $_rate->getCarrierTitle(),
                    ];
                // } else {
                //     $list[] = [
                //         's_method_id'       => $_rate->getId(),
                //         's_method_code'     => $_rate->getCode(),
                //         's_method_title'    => $_rate->getCarrierTitle(),
                //         's_method_fee'      => $s_fee,
                //         's_method_name'     => $_rate->getMethodTitle(),
                //         's_method_selected' => $select,
                //         's_carrier_code'    => $_rate->getCarrier(),
                //         's_carrier_title'   => $_rate->getCarrierTitle(),
                //     ];
                // }
            }
        }
        return $list;
    }
}
