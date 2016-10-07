<?php

/**
 * Shipping helper
 */

namespace Simi\Simiconnector\Helper\Checkout;

class Payment extends \Simi\Simiconnector\Helper\Data {

    public $detail;

    public function __construct() {
        parent::__construct();
        $this->_setListPayment();
        $this->setListCase();
    }

    protected function _getCart() {
        return $this->_objectManager->get('Magento\Checkout\Model\Cart');
    }

    protected function _getQuote() {
        return $this->_getCart()->getQuote();
    }

    public function _getOnepage() {
        return $this->_objectManager->get('Magento\Checkout\Model\Type\Onepage');
    }

    protected function _getConfig() {
        return $this->_objectManager->get('Magento\Payment\Model\Config');
    }

    protected $_listPayment = array();
    protected $_listCase;

    public function savePaymentMethod($data) {
        $method = array('method' => strtolower($data->method));
        if (isset($data->cc_type) && $data->cc_type) {
            $method = array('method' => strtolower($data->method),
                'cc_type' => $data->cc_type,
                'cc_number' => $data->cc_number,
                'cc_exp_month' => $data->cc_exp_month,
                'cc_exp_year' => $data->cc_exp_year,
                'cc_cid' => $data->cc_cid,
            );
        }
        $this->_getOnepage()->savePayment($method);
    }

    /**
     * Add payment method
     * @param $method_code
     * @param $type
     */
    public function addPaymentMethod($method_code, $type) {
        $this->_listPayment[] = $method_code;
        $this->_listPayment = array_unique($this->_listPayment);
        $this->_listCase[$method_code] = $type;
    }

    public function getMethods() {
        /*
         * Dispatch event simiconnector_add_payment_method
         */
        $this->_objectManager->get('\Magento\Framework\Event\ManagerInterface')->dispatch('simiconnector_add_payment_method', array('object' => $this));

        $quote = $this->_getQuote();
        $store = $quote ? $quote->getStoreId() : null;
        $methods = $this->_objectManager->get('Magento\Payment\Helper\Data')->getStoreMethods($store, $quote);
        $total = $quote->getBaseSubtotal() + $quote->getShippingAddress()->getBaseShippingAmount();

        foreach ($methods as $key => $method) {
            if ($this->_canUseMethod($method, $quote) && (!in_array($method->getCode(), $this->_getListPaymentNoUse()) &&
                    (in_array($method->getCode(), $this->_getListPayment()) || $method->getConfigData('cctypes'))) && ($total != 0 || $method->getCode() == 'free' || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles()))) {
                $this->_assignMethod($method, $quote);
            } else {
                unset($methods[$key]);
            }
        }
        return $methods;
    }

    protected function _canUseMethod($method, $quote) {
        if (!$method->canUseForCountry($quote->getBillingAddress()->getCountry())) {
            return false;
        }

        if (!$method->canUseForCurrency($quote->getStore()->getBaseCurrencyCode())) {
            return false;
        }

        /**
         * Checking for min/max order total for assigned payment method
         */
        $total = $quote->getBaseGrandTotal();
        $minTotal = $method->getConfigData('min_order_total');
        $maxTotal = $method->getConfigData('max_order_total');

        if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
            return false;
        }
        return true;
    }

    protected function _getListPaymentNoUse() {
        return array(
            'authorizenet_directpost'
        );
    }

    protected function _setListPayment() {
        $this->_listPayment[] = 'transfer_mobile';
        $this->_listPayment[] = 'cashondelivery';
        $this->_listPayment[] = 'checkmo';
        $this->_listPayment[] = 'free';
        $this->_listPayment[] = 'banktransfer';
        $this->_listPayment[] = 'phoenix_cashondelivery';
    }

    protected function _getListPayment() {
        return $this->_listPayment;
    }

    protected function _assignMethod($method, $quote) {
        $method->setInfoInstance($quote->getPayment());
        return $this;
    }

    public function setListCase() {
        $this->_listCase = array(
            'banktransfer' => 0,
            'transfer_mobile' => 0,
            'cashondelivery' => 0,
            'checkmo' => 0,
            'free' => 0,
            'phoenix_cashondelivery' => 0,
        );
    }

    public function getDetailsPayment($method) {
        $code = $method->getCode();
        $list = $this->getListCase();

        $type = 1;
        if (in_array($code, $this->_getListPayment())) {
            $type = $list[$code];
        }

        $detail = array();
        if ($type == 0) {
            if ($code == "checkmo") {
                $detail['payment_method'] = strtoupper($method->getCode());
                $detail['title'] = $method->getConfigData('title');
                $detail['content'] = __('Make Check Payable to: ') . $method->getConfigData('payable_to') . __('Send Check to: ') . $method->getConfigData('mailing_address');
                $detail['show_type'] = 0;
            } else {
                $detail['content'] = $method->getConfigData('instructions');
                $detail['payment_method'] = strtoupper($method->getCode());
                $detail['title'] = $method->getConfigData('title');
                $detail['show_type'] = 0;
            }
        } elseif ($type == 1) {
            $detail['cc_types'] = $this->getCcAvailableTypes($method);
            $detail['payment_method'] = strtoupper($method->getCode());
            $detail['title'] = $method->getConfigData('title');
            $detail['useccv'] = $method->getConfigData('useccv');
            $detail['show_type'] = 1;
        } elseif ($type == 2) {
            $m_code = strtoupper($method->getCode());
            $detail['email'] = $method->getConfigData('business_account');
            $detail['client_id'] = $method->getConfigData('client_id');
            $detail['is_sandbox'] = $method->getConfigData('is_sandbox');
            $detail['payment_method'] = $m_code;
            $detail['title'] = $method->getConfigData('title');
            $detail['bncode'] = "Magestore_SI_MagentoCE";
            $detail['show_type'] = 2;
            if (strcasecmp($m_code, 'PAYPAL_MOBILE') == 0) {
                $detail['use_credit_card'] = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/paypal_mobile/use_credit_cart');
            }
        } elseif ($type == 3) {
            $detail['payment_method'] = strtoupper($method->getCode());
            $detail['title'] = $method->getConfigData('title');
            $detail['show_type'] = 3;
        }
        $detail['p_method_selected'] = false;
        if (($this->_getQuote()->getPayment()->getMethod()) && ($this->_getQuote()->getPayment()->getMethodInstance()->getCode() == $method->getCode())) {
            $detail['p_method_selected'] = true;
        }
        $this->detail = $detail;
        $this->_objectManager->get('\Magento\Framework\Event\ManagerInterface')->dispatch('simiconnector_change_payment_detail', array('object' => $this));
        return $this->detail;
    }

    public function getListCase() {
        return $this->_listCase;
    }

    public function getCcAvailableTypes($method) {
        $types = $this->_getConfig()->getCcTypes();
        $availableTypes = $method->getConfigData('cctypes');
        $cc_types = array();
        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
            foreach ($types as $code => $name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                } else {
                    $cc_types[] = array(
                        'cc_code' => $code,
                        'cc_name' => $name,
                    );
                }
            }
        }
        return $cc_types;
    }

}
