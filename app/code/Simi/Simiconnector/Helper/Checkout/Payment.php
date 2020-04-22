<?php

/**
 * Shipping helper
 */

namespace Simi\Simiconnector\Helper\Checkout;

class Payment extends \Simi\Simiconnector\Helper\Data
{

    public $detail;
    public $dataToSave;

    public function _getCart()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
    }

    public function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    public function _getOnepage()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Type\Onepage');
    }

    public function _getConfig()
    {
        return $this->simiObjectManager->get('Magento\Payment\Model\Config');
    }

    public $listPayment = [];
    public $listCase;

    public function savePaymentMethod($data)
    {
        $this->_setListPayment();
        $this->setListCase();
        $this->dataToSave = $data;
        $this->simiObjectManager->get('\Magento\Framework\Event\ManagerInterface')
            ->dispatch('simiconnector_save_payment_method_before', ['object' => $this, 'data' => $this->dataToSave]);
        $data = $this->dataToSave;
        $method = ['method' => strtolower($data->method)];
        if (isset($data->cc_type) && $data->cc_type) {
            $method = ['method'       => strtolower($data->method),
                'cc_type'      => $data->cc_type,
                'cc_number'    => $data->cc_number,
                'cc_exp_month' => $data->cc_exp_month,
                'cc_exp_year'  => $data->cc_exp_year,
                'cc_cid'       => $data->cc_cid,
            ];
        }
        $this->_getOnepage()->savePayment($method);

        $this->simiObjectManager->get('\Magento\Framework\Event\ManagerInterface')
            ->dispatch('simiconnector_save_payment_method_after', ['object' => $this, 'data' => $this->dataToSave]);
    }

    /**
     * Add payment method
     * @param $method_code
     * @param $type
     */
    public function addPaymentMethod($method_code, $type)
    {
        $this->listPayment[]          = $method_code;
        $this->listPayment            = array_unique($this->listPayment);
        $this->listCase[$method_code] = $type;
    }

    public function getMethods()
    {
        $this->_setListPayment();
        $this->setListCase();

        /*
         * Dispatch event simiconnector_add_payment_method
         */
        $this->simiObjectManager->get('\Magento\Framework\Event\ManagerInterface')
            ->dispatch('simiconnector_add_payment_method', ['object' => $this]);

        $quote   = $this->_getQuote();
        $store   = $quote ? $quote->getStoreId() : null;
        $methods = $this->simiObjectManager->get('Magento\Payment\Helper\Data')->getStoreMethods($store, $quote);
        $total   = $quote->getBaseSubtotal() + $quote->getShippingAddress()->getBaseShippingAmount();

        foreach ($methods as $key => $method) {
            if ($this->_canUseMethod($method, $quote) && (!in_array($method->getCode(), $this->_getListPaymentNoUse())
                    && in_array($method->getCode(), $this->_getListPayment()))
                && ($total != 0 || $method->getCode() == 'free'
                    || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles()))) {
                $this->_assignMethod($method, $quote);
            } else {
                unset($methods[$key]);
            }
        }
        return $methods;
    }

    public function _canUseMethod($method, $quote)
    {
        if (!$method->canUseForCountry($quote->getBillingAddress()->getCountry())) {
            return false;
        }

        if (!$method->canUseForCurrency($quote->getStore()->getBaseCurrencyCode())) {
            return false;
        }

        /**
         * Checking for min/max order total for assigned payment method
         */
        $total    = $quote->getBaseGrandTotal();
        $minTotal = $method->getConfigData('min_order_total');
        $maxTotal = $method->getConfigData('max_order_total');

        if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
            return false;
        }
        return true;
    }

    public function _getListPaymentNoUse()
    {
        return [
            'authorizenet_directpost'
        ];
    }

    public function _setListPayment()
    {
        $this->listPayment[] = 'transfer_mobile';
        $this->listPayment[] = 'cashondelivery';
        $this->listPayment[] = 'checkmo';
        $this->listPayment[] = 'free';
        $this->listPayment[] = 'banktransfer';
        $this->listPayment[] = 'phoenix_cashondelivery';
        $this->listPayment[] = 'braintree';
    }

    public function _getListPayment()
    {
        return $this->listPayment;
    }

    public function _assignMethod($method, $quote)
    {
        $method->setInfoInstance($quote->getPayment());
        return $this;
    }

    public function setListCase()
    {
        $this->listCase = [
            'banktransfer'           => 0,
            'transfer_mobile'        => 0,
            'cashondelivery'         => 0,
            'checkmo'                => 0,
            'free'                   => 0,
            'phoenix_cashondelivery' => 0,
            'braintree'              => 0,
        ];
    }

    public function getDetailsPayment($method)
    {
        $code = $method->getCode();
        $list = $this->getListCase();

        $type = 1;
        if (in_array($code, $this->_getListPayment())) {
            $type = $list[$code];
        }

        $detail = [];
        switch ($type) {
            case 0:
                if ($code == "checkmo") {
                    $detail['payment_method'] = strtoupper($method->getCode());
                    $detail['title']          = $method->getConfigData('title');
                    $detail['content']        = __('Make Check Payable to: ')
                        . $method->getConfigData('payable_to') . __('Send Check to: ')
                        . $method->getConfigData('mailing_address');
                    $detail['show_type']      = 0;
                } else if ($code == "braintree") {
                    $detail['payment_method'] = strtoupper($method->getCode());
                    $detail['title']          = $method->getConfigData('title');
                    $detail['show_type']      = 0;
                    $detail['braintree_token']     = $this->simiObjectManager
                        ->get('\Magento\Framework\App\Config\ScopeConfigInterface')
                        ->getValue('siminiaconfig/braintree/braintree_token');
                } else {
                    $detail['content']        = $method->getConfigData('instructions');
                    $detail['payment_method'] = strtoupper($method->getCode());
                    $detail['title']          = $method->getConfigData('title');
                    $detail['show_type']      = 0;
                }
                break;
            case 1:
                $detail['cc_types']       = $this->getCcAvailableTypes($method);
                $detail['payment_method'] = strtoupper($method->getCode());
                $detail['title']          = $method->getConfigData('title');
                $detail['useccv']         = $method->getConfigData('useccv');
                $detail['is_show_name']   = '0';
                $detail['show_type']      = 1;
                break;
            case 2:
                $m_code = strtoupper($method->getCode());
                if ($method->getConfigData('client_id')) {
                    $detail['email'] = $method->getConfigData('business_account');
                    $detail['client_id'] = $method->getConfigData('client_id');
                    $detail['is_sandbox'] = $method->getConfigData('is_sandbox');
                }
                $detail['payment_method'] = $m_code;
                $detail['title']          = $method->getConfigData('title');
                $detail['show_type']      = 2;
                if (strcasecmp($m_code, 'PAYPAL_MOBILE') == 0) {
                    $detail['bncode']          = "Magestore_SI_MagentoCE";
                    $detail['use_credit_card'] = $this->simiObjectManager
                        ->get('\Magento\Framework\App\Config\ScopeConfigInterface')
                        ->getValue('payment/paypal_mobile/use_credit_cart');
                }
                break;
            default:
                $detail['payment_method'] = strtoupper($method->getCode());
                $detail['title']          = $method->getConfigData('title');
                $detail['show_type']      = 3;
                break;
        }
        $detail['p_method_selected'] = false;
        if (($this->_getQuote()->getPayment()->getMethod())
            && ($this->_getQuote()->getPayment()->getMethodInstance()->getCode() == $method->getCode())) {
            $detail['p_method_selected'] = true;
        }
        $this->detail = $detail;
        $this->simiObjectManager->get('\Magento\Framework\Event\ManagerInterface')
            ->dispatch('simiconnector_change_payment_detail', ['object' => $this]);
        return $this->detail;
    }

    public function getListCase()
    {
        return $this->listCase;
    }

    public function getCcAvailableTypes($method)
    {
        $types          = $this->_getConfig()->getCcTypes();
        $availableTypes = $method->getConfigData('cctypes');
        $cc_types       = [];
        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
            foreach ($types as $code => $name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                } else {
                    $cc_types[] = [
                        'cc_code' => $code,
                        'cc_name' => $name,
                    ];
                }
            }
        }
        return $cc_types;
    }
}
