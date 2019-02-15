<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

class Total extends Data
{

    public $data;

    public function _getCart()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
    }

    public function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    /*
     * Get Quote Price
     */

    public function getTotal()
    {
        $orderTotal = [];
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

    public function setTotal($total, &$data)
    {
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
            $taxSumarry  = [];
            foreach ($total['tax']->getFullInfo() as $info) {
                if (isset($info['hidden']) && $info['hidden']) {
                    continue;
                }
                $amount = $info['amount'];
                $rates  = $info['rates'];
                foreach ($rates as $rate) {
                    $title = $rate['title'];
                    if (!($rate['percent'] === null)) {
                        $title.= ' (' . $rate['percent'] . '%)';
                    }
                    $taxSumarry[] = ['title'  => $title,
                        'amount' => $amount,
                    ];
                    /*
                     * SimiCart only show the first Rate for Each Item
                     */
                    break;
                }
            }
            if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($taxSumarry)) {
                $data['tax_summary'] = $taxSumarry;
            }
        }
        if (isset($total['discount'])) {
            $data['discount'] = abs($total['discount']->getValue());
        }
        /*
         * tax_cart_display_subtotal
         */
        $this->setSubtotal($total, $data);
        /*
         * tax_cart_display_grandtotal
         */

        $data['grand_total_incl_tax'] = $total['grand_total']->getValue();
        $data['grand_total_excl_tax'] = $this->getTotalExclTaxGrand($data);

        $coupon = '';
        if ($this->_getQuote()->getCouponCode()) {
            $coupon              = $this->_getQuote()->getCouponCode();
            $data['coupon_code'] = $coupon;
        }

        $this->data = $data;
        $this->simiObjectManager
                ->get('\Magento\Framework\Event\ManagerInterface')
                ->dispatch(
                    'simi_simiconnector_helper_total_settotal_after',
                    ['object' => $this, 'data' => $this->data]
                );

        if (isset($total['cash_on_delivery_fee'])) {           
            $codFee = $total['cash_on_delivery_fee']->getValue();
            $this->addCustomRow(__('Cash on Delivery fee'), 4, $codFee);
        }

        $data       = $this->data;
    }
    
    private function setSubtotal($total, &$data)
    {
        if ($this->displayTypeSubOrder() == 3) {
            $data['subtotal_excl_tax'] = $total['subtotal']->getValueExclTax();
            $data['subtotal_incl_tax'] = $total['subtotal']->getValueInclTax();
        } elseif ($this->displayTypeSubOrder() == 1) {
            $data['subtotal_excl_tax'] = $total['subtotal']->getValue();
            $data['subtotal_incl_tax'] = $data['subtotal_excl_tax'] + $data['tax'];
        } elseif ($this->displayTypeSubOrder() == 2) {
            $data['subtotal_incl_tax'] = $total['subtotal']->getValue();
            $data['subtotal_excl_tax'] = $data['subtotal_incl_tax'] - $data['tax'];
        }
    }

    public function displayTypeSubOrder()
    {
        return $this->getStoreConfig('tax/cart_display/subtotal');
    }

    /*
     * For Order History
     */

    public function showTotalOrder($order)
    {
        $data                      = [];
        $data['subtotal_excl_tax'] = $order->getSubtotal();
        $data['subtotal_incl_tax'] = $order->getSubtotalInclTax();
        if ($data['subtotal_incl_tax'] == null) {
            $data['subtotal_incl_tax'] = $order->getSubtotal() + $order->getTaxAmount();
        }
        $data['shipping_hand_excl_tax'] = $order->getShippingAmount();
        $data['shipping_hand_incl_tax'] = $order->getShippingInclTax();
        $data['tax']                    = $order->getTaxAmount();
        $data['discount']               = abs($order->getDiscountAmount());
        $data['grand_total_excl_tax']   = $order->getGrandTotal() - $data['tax'];
        $data['grand_total_incl_tax']   = $order->getGrandTotal();

        if ($this->simiObjectManager->get('Magento\Directory\Model\Currency')
                ->load($order->getData('order_currency_code'))->getCurrencySymbol() != null) {
            $data['currency_symbol'] = $this->simiObjectManager
                    ->get('Magento\Directory\Model\Currency')->load($order->getData('order_currency_code'))
                    ->getCurrencySymbol();
        } else {
            $data['currency_symbol'] = $order->getOrderCurrency()->getCurrencyCode();
        }
        return $data;
    }

    public function addCustomRow($title, $sortOrder, $value, $valueString = null)
    {
        if (isset($this->data['custom_rows'])) {
            $customRows = $this->data['custom_rows'];
        } else {
            $customRows = [];
        }
        if (!$valueString) {
            $customRows[] = ['title' => $title, 'sort_order' => $sortOrder, 'value' => $value];
        } else {
            $customRows[] = ['title' => $title, 'sort_order' => $sortOrder, 'value' => $value,
                'value_string' => $valueString];
        }
        $this->data['custom_rows'] = $customRows;
    }

    public function displayBothTaxSub()
    {
        return $this->simiObjectManager->get('Magento\Tax\Model\Tax')
                ->displayCartSubtotalBoth($this->storeManager->getStore());
    }

    public function includeTaxGrand($total)
    {
        if ($total->getAddress()->getGrandTotal()) {
            return $this->simiObjectManager->get('Magento\Tax\Model\Tax')
                    ->displayCartTaxWithGrandTotal($this->storeManager->getStore());
        }
        return false;
    }

    public function getTotalExclTaxGrand($total)
    {
        if (isset($total['tax'])) {
            $excl = $total['grand_total_incl_tax'] - $total['tax'];
            $excl = max($excl, 0);
            return $excl;
        }
        return $total['grand_total'];
    }

    public function displayBothTaxShipping()
    {
        return $this->simiObjectManager->get('Magento\Tax\Model\Tax')
                ->displayCartShippingBoth($this->storeManager->getStore());
    }

    public function displayIncludeTaxShipping()
    {
        return $this->simiObjectManager->get('Magento\Tax\Model\Tax')
                ->displayCartShippingInclTax($this->storeManager->getStore());
    }

    public function getShippingIncludeTax($total)
    {
        return $total->getAddress()->getShippingInclTax();
    }

    public function getShippingExcludeTax($total)
    {
        return $total->getAddress()->getShippingAmount();
    }
}
