<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper;



class Total extends Data
{
    public $data;

    protected function _getCart() {
        return $this->_objectManager->get('Magento\Checkout\Model\Cart');
    }

    protected function _getQuote() {
        return $this->_getCart()->getQuote();
    }

    /*
     * Get Quote Price
     */

    public function getTotal() {
        $orderTotal = array();
        if ($this->_getQuote()->isVirtual()) {
            $total = $this->_getQuote()->getBillingAddress()->getTotals();
        } else {
            $total = $this->_getQuote()->getShippingAddress()->getTotals();
        }
        $this->setTotal($total, $orderTotal);
        return $orderTotal;
    }

    /*
     * For Cart and OnePage Order
     */

    public function setTotal($total, &$data) {
        if (isset($total['shipping']) && ($total['shipping']->getValue())) {
            /*
             * tax_cart_display_shipping
             */
            $data['shipping_hand_incl_tax'] = $this->getShippingIncludeTax($total['shipping']);
            $data['shipping_hand_excl_tax'] = $this->getShippingExcludeTax($total['shipping']);
        }
        /*
         * tax_cart_display_zero_tax
         */
        if (isset($total['tax'])) {
            $data['tax'] = $total['tax']->getValue();
            $taxSumarry = array();
            foreach ($total['tax']->getFullInfo() as $info) {
                if (isset($info['hidden']) && $info['hidden'])
                    continue;
                $amount = $info['amount'];
                $rates = $info['rates'];
                foreach ($rates as $rate) {
                    $title = $rate['title'];
                    if (!is_null($rate['percent'])) {
                        $title.= ' ('.$rate['percent'].'%)';
                    }
                    $taxSumarry[] = array('title' => $title,
                        'amount' => $amount,
                    );
                    /*
                     * SimiCart only show the first Rate for Each Item 
                     */
                    break;
                }
            }
            if (count($taxSumarry))
                $data['tax_summary'] = $taxSumarry;
        }
        if (isset($total['discount'])) {
            $data['discount'] = abs($total['discount']->getValue());
        }
        /*
         * tax_cart_display_subtotal
         */
        if ($this->displayTypeSubOrder() == 3) {
            $data['subtotal_excl_tax'] = $total['subtotal']->getValueExclTax();
            $data['subtotal_incl_tax'] = $total['subtotal']->getValueInclTax();
        } else if ($this->displayTypeSubOrder() == 1) {
            $data['subtotal_excl_tax'] = $total['subtotal']->getValue();   
            $data['subtotal_incl_tax'] = $data['subtotal_excl_tax'] + $data['tax'];
        } else if ($this->displayTypeSubOrder() == 2) {
            $data['subtotal_incl_tax'] = $total['subtotal']->getValue();   
            $data['subtotal_excl_tax'] = $data['subtotal_incl_tax'] - $data['tax'];
        }
        /*
         * tax_cart_display_grandtotal
         */
        
        $data['grand_total_incl_tax'] = $total['grand_total']->getValue();
        $data['grand_total_excl_tax'] = $this->getTotalExclTaxGrand($data);

        $coupon = '';
        if ($this->_getQuote()->getCouponCode() ) {
            $coupon = $this->_getQuote()->getCouponCode();
            $data['coupon_code'] = $coupon;
        }

        $this->data = $data;
        $this->_objectManager->get('\Magento\Framework\Event\ManagerInterface')->dispatch('simi_simiconnector_helper_total_settotal_after', array('object' => $this, 'data' => $this->data));
        $data = $this->data;
    }

    public function displayTypeSubOrder() {
        return $this->getStoreConfig('tax/cart_display/subtotal');
    }
    
    /*
     * For Order History
     */

    public function showTotalOrder($order) {
        $data = array();
        $data['subtotal_excl_tax'] = $order->getSubtotal();
        $data['subtotal_incl_tax'] = $order->getSubtotalInclTax();
	if ($data['subtotal_incl_tax'] == null) {
            $data['subtotal_incl_tax'] = $order->getSubtotal() + $order->getTaxAmount();
        }
        $data['shipping_hand_excl_tax'] = $order->getShippingAmount();
        $data['shipping_hand_incl_tax'] = $order->getShippingInclTax();
        $data['tax'] = $order->getTaxAmount();
        $data['discount'] = abs($order->getDiscountAmount());
        $data['grand_total_excl_tax'] = $order->getGrandTotal() - $data['tax'];
        $data['grand_total_incl_tax'] = $order->getGrandTotal();

        if ($this->_objectManager->get('Magento\Directory\Model\Currency')->load($order->getData('order_currency_code'))->getCurrencySymbol() != null) {
            $data['currency_symbol'] = $this->_objectManager->get('Magento\Directory\Model\Currency')->load($order->getData('order_currency_code'))->getCurrencySymbol();
        } else {
            $data['currency_symbol'] = $order->getOrderCurrency()->getCurrencyCode();
        }
        return $data;
    }

    public function addCustomRow($title, $sortOrder, $value, $valueString = null) {
        if (isset($this->data['custom_rows']))
            $customRows = $this->data['custom_rows'];
        else
            $customRows = array();
        if (!$valueString)
            $customRows[] = array('title' => $title, 'sort_order' => $sortOrder, 'value' => $value);
        else
            $customRows[] = array('title' => $title, 'sort_order' => $sortOrder, 'value' => $value, 'value_string' => $valueString);
        $this->data['custom_rows'] = $customRows;
    }

    public function displayBothTaxSub() {
        return $this->_objectManager->get('Magento\Tax\Model\Tax')->displayCartSubtotalBoth($this->_storeManager->getStore());
    }

    public function includeTaxGrand($total) {
        if ($total->getAddress()->getGrandTotal()) {
            return $this->_objectManager->get('Magento\Tax\Model\Tax')->displayCartTaxWithGrandTotal($this->_storeManager->getStore());
        }
        return false;
    }

    public function getTotalExclTaxGrand($total) {
        if(isset($total['tax'])) {
            $excl = $total['grand_total_incl_tax'] - $total['tax'];
            $excl = max($excl, 0);
            return $excl;
        }
        return $total['grand_total'];
    }

    public function displayBothTaxShipping() {
        return $this->_objectManager->get('Magento\Tax\Model\Tax')->displayCartShippingBoth($this->_storeManager->getStore());
    }

    public function displayIncludeTaxShipping() {
        return $this->_objectManager->get('Magento\Tax\Model\Tax')->displayCartShippingInclTax($this->_storeManager->getStore());
    }

    public function getShippingIncludeTax($total) {
        return $total->getAddress()->getShippingInclTax();
    }

    public function getShippingExcludeTax($total) {
        return $total->getAddress()->getShippingAmount();
    }

}
