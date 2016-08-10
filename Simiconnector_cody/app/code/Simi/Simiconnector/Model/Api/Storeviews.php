<?php
/**
 * Copyright © 2015 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

/**
 * Codymodeltab codymodel model
 */
class Storeviews extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'store_id';
    protected $_scope_interface;


    public function setBuilderQuery() {
        $data = $this->getData();
        $collection = $this->_objectManager->get('\Magento\Store\Model\Store')->getCollection();
        if ($data['resourceid']) {
            $this->builderQuery = $this->_objectManager->get('\Magento\Store\Model\Store')->load($data['resourceid']);
        } else {
            $this->builderQuery = $collection->addFieldToFilter('group_id', $this->_storeManager->getStore()->getGroupId());
        }
    }

    

    public function index() {
        $result = parent::index();
        foreach ($result['storeviews'] as $index => $storeView) {
            $result['storeviews'][$index]['base_url'] = $this->_scopeConfig->getValue('simiconnector/general/base_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeView['store_id']);
        }
        return $result;
    }

    public function show() {
        $information = parent::show();
        $country_code = $this->_scopeConfig->getValue('general/country/default');
        $country = $this->_objectManager->get('\Magento\Directory\Model\Country')->loadByCode($country_code);
        
        $locale = $this->getLocale();
        $currencyCode =  $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currencySymbol = $this->_objectManager->get('\Magento\Directory\Model\Currency')->getCurrencySymbol();
        $options = $this->_objectManager->get('Magento\Customer\Model\Customer')->getAttribute('gender')->getSource()->getAllOptions();
        
        $values = array();
        foreach ($options as $option) {
            if ($option['value']) {
                $values[] = array(
                    'label' => $option['label'],
                    'value' => $option['value'],
                );
            }
        }

        $rtlCountry = $this->_scopeConfig->getValue('simiconnector/general/rtl_country');
        $isRtl = '0';
        $rtlCountry = explode(',', $rtlCountry);
        if (in_array($country_code, $rtlCountry)) {
            $isRtl = '1';
        }
        //$currencies = $this->getCurrencies();
        
        /*
        $cmsData = $this->getData();
        $cmsData['resourceid'] = NULL;
        $cmsData['resource'] = 'cmspages';
        $model = Mage::getSingleton('simiconnector/api_cmspages');
        $cmsPageList = call_user_func_array(array(&$model, $this->_method), array($cmsData));
        */
        
        $additionInfo = array(
            'base' => array(
                'country_code' => $country->getId(),
                'country_name' => $country->getName(),
                'locale_identifier' => $locale,
                'store_id' => $this->_storeManager->getStore()->getId(),
                'store_name' => $this->_storeManager->getStore()->getName(),
                'store_code' => $this->_storeManager->getStore()->getCode(),
                'group_id' => $this->_storeManager->getStore()->getGroupId(),
                'base_url' => $this->_scopeConfig->getValue('simiconnector/general/base_url'),
                'use_store' => $this->_scopeConfig->getValue('web/url/use_store'),
                'is_rtl' => $isRtl,
                'is_show_sample_data' => $this->_scopeConfig->getValue('simiconnector/general/is_show_sample_data'),
                'android_sender' =>  $this->_scopeConfig->getValue('simiconnector/notification/android_app_key'),
                'currency_symbol' => $currencySymbol,
                'currency_code' => $currencyCode,
                //'currency_position' => $this->getCurrencyPosition(),
                'thousand_separator' => $this->_scopeConfig->getValue('simiconnector/currency/thousand_separator'),
                'decimal_separator' => $this->_scopeConfig->getValue('simiconnector/currency/decimal_separator'),
                'min_number_of_decimals' => $this->_scopeConfig->getValue('simiconnector/currency/min_number_of_decimals'),
                'max_number_of_decimals' => $this->_scopeConfig->getValue('simiconnector/currency/max_number_of_decimals'),
                //'currencies' => $currencies,
                'is_show_home_title' => $this->_scopeConfig->getValue('simiconnector/general/is_show_home_title'),
            ),
            
            'sales' => array(
                'sales_reorder_allow' => $this->_scopeConfig->getValue('sales/reorder/allow'),
                'sales_totals_sort_subtotal' => $this->_scopeConfig->getValue('sales/totals_sort/subtotal'),
                'sales_totals_sort_discount' => $this->_scopeConfig->getValue('sales/totals_sort/discount'),
                'sales_totals_sort_shipping' => $this->_scopeConfig->getValue('sales/totals_sort/shipping'),
                'sales_totals_sort_weee' => $this->_scopeConfig->getValue('sales/totals_sort/weee'),
                'sales_totals_sort_tax' => $this->_scopeConfig->getValue('sales/totals_sort/tax'),
                'sales_totals_sort_grand_total' => $this->_scopeConfig->getValue('sales/totals_sort/grand_total'),
            ),
            'checkout' => array(
                'enable_guest_checkout' => $this->_scopeConfig->getValue('checkout/options/guest_checkout'),
                'enable_agreements' => is_null($this->_scopeConfig->getValue('checkout/options/enable_agreements')) ? 0 : Mage::getStoreConfig('checkout/options/enable_agreements'),
            ),
            'tax' => array(
                'tax_display_type' => $this->_scopeConfig->getValue('tax/display/type'),
                'tax_display_shipping' => $this->_scopeConfig->getValue('tax/display/shipping'),
                'tax_cart_display_price' => $this->_scopeConfig->getValue('tax/cart_display/price'),
                'tax_cart_display_subtotal' => $this->_scopeConfig->getValue('tax/cart_display/subtotal'),
                'tax_cart_display_shipping' => $this->_scopeConfig->getValue('tax/cart_display/shipping'),
                'tax_cart_display_grandtotal' => $this->_scopeConfig->getValue('tax/cart_display/grandtotal'),
                'tax_cart_display_full_summary' => $this->_scopeConfig->getValue('tax/cart_display/full_summary'),
                'tax_cart_display_zero_tax' => $this->_scopeConfig->getValue('tax/cart_display/zero_tax'),
            ),
            
            'google_analytics' => array(
                'google_analytics_active' => $this->_scopeConfig->getValue('google/analytics/active'),
                'google_analytics_type' => $this->_scopeConfig->getValue('google/analytics/type'),
                'google_analytics_account' => $this->_scopeConfig->getValue('google/analytics/account'),
                'google_analytics_anonymization' => $this->_scopeConfig->getValue('google/analytics/anonymization'),
            ),
            'customer' => array(
                'address_option' => array(
                    'prefix_show' => $this->_scopeConfig->getValue('customer/address/prefix_show'),
                    'middlename_show' => $this->_scopeConfig->getValue('customer/address/middlename_show'),
                    'suffix_show' => $this->_scopeConfig->getValue('customer/address/suffix_show'),
                    'dob_show' => $this->_scopeConfig->getValue('customer/address/dob_show'),
                    'taxvat_show' => $this->_scopeConfig->getValue('customer/address/taxvat_show'),
                    'gender_show' => $this->_scopeConfig->getValue('customer/address/gender_show'),
                    //'gender_value' => $values,
                ),
                'account_option' => array(
                    'taxvat_show' => $this->_scopeConfig->getValue('customer/create_account/vat_frontend_visibility'),
                ),
            ),
            'wishlist' => array(
                'wishlist_general_active' => $this->_scopeConfig->getValue('wishlist/general/active'),
                'wishlist_wishlist_link_use_qty' => $this->_scopeConfig->getValue('wishlist/wishlist_link/use_qty'),
            ),
            'catalog' => array(
                'frontend' => array(
                    'view_products_default' => $this->_scopeConfig->getValue('simiconnector/general/show_product_type'),
                    'is_show_zero_price' => $this->_scopeConfig->getValue('simiconnector/general/is_show_price_zero'),
                    'is_show_link_all_product' => $this->_scopeConfig->getValue('simiconnector/general/is_show_all_product'),
                    'catalog_frontend_list_mode' => $this->_scopeConfig->getValue('catalog/frontend/list_mode'),
                    'catalog_frontend_grid_per_page_values' => $this->_scopeConfig->getValue('catalog/frontend/grid_per_page_values'),
                    'catalog_frontend_list_per_page' => $this->_scopeConfig->getValue('catalog/frontend/list_per_page'),
                    'catalog_frontend_list_allow_all' => $this->_scopeConfig->getValue('catalog/frontend/list_allow_all'),
                    'catalog_frontend_default_sort_by' => $this->_scopeConfig->getValue('catalog/frontend/default_sort_by'),
                    'catalog_frontend_flat_catalog_category' => $this->_scopeConfig->getValue('catalog/frontend/flat_catalog_category'),
                    'catalog_frontend_flat_catalog_product' => $this->_scopeConfig->getValue('catalog/frontend/flat_catalog_product'),
                    'catalog_frontend_parse_url_directives' => $this->_scopeConfig->getValue('catalog/frontend/parse_url_directives'),
                ),
                'review' => array(
                    'catalog_review_allow_guest' => $this->_scopeConfig->getValue('catalog/review/allow_guest'),
                ),
            ),
            //'cms' => $cmsPageList,
            //'category_cmspages' => Mage::getModel('simiconnector/cms')->getCategoryCMSPages(),
            /*
            'zopim_config' => array(
                'enable' => Mage::getStoreConfig('simiconnector/zopim/enable'),
                'account_key' => Mage::getStoreConfig('simiconnector/zopim/account_key'),
                'show_profile' => Mage::getStoreConfig('simiconnector/zopim/show_profile'),
                'name' => Mage::getStoreConfig('simiconnector/zopim/name'),
                'email' => Mage::getStoreConfig('simiconnector/zopim/email'),
                'phone' => Mage::getStoreConfig('simiconnector/zopim/phone'),
            ),
             */
             
            //'allowed_countries' => $this->getAllowedCountries(),
            //'stores' => $this->getStores(),
        );
        /*
        if ($checkout_info_setting = Mage::helper('simiconnector/address')->getCheckoutAddressSetting())
            $additionInfo['customer']['address_fields_config'] = $checkout_info_setting;

        if ($checkout_terms = Mage::helper('simiconnector/checkout')->getCheckoutTermsAndConditions())
            $additionInfo['checkout']['checkout_terms_and_conditions'] = $checkout_terms;
        //Scott add to get instant contacts
        if (Mage::helper('simiconnector/plugins_instantcontact')->isEnabled())
            $additionInfo['instant_contact'] = Mage::helper('simiconnector/plugins_instantcontact')->getContacts();
        */
        $information['storeview'] = $additionInfo;
        return $information;
    }
    
    function getLocale() {
	/** @var \Magento\Framework\ObjectManagerInterface $om */
	$om = \Magento\Framework\App\ObjectManager::getInstance();
	/** @var \Magento\Framework\Locale\Resolver $resolver */
	$resolver = $om->get('Magento\Framework\Locale\Resolver');
	return $resolver->getLocale();
    }

/*
    public function getAllowedCountries() {
        $list = array();
        $country_default = Mage::getStoreConfig('general/country/default');
        $countries = Mage::getResourceModel('directory/country_collection')->loadByStore();
        $cache = null;
        foreach ($countries as $country) {
            if ($country_default == $country->getId()) {
                $cache = array(
                    'country_code' => $country->getId(),
                    'country_name' => $country->getName(),
                    'states' => Mage::helper('simiconnector/address')->getStates($country->getId()),
                );
            } else {
                $list[] = array(
                    'country_code' => $country->getId(),
                    'country_name' => $country->getName(),
                    'states' => Mage::helper('simiconnector/address')->getStates($country->getId()),
                );
            }
        }
        if ($cache) {
            array_unshift($list, $cache);
        }
        return $list;
    }

    public function getCurrencyPosition() {
        $formated = Mage::app()->getStore()->getCurrentCurrency()->formatTxt(0);
        $number = Mage::app()->getStore()->getCurrentCurrency()->formatTxt(0, array('display' => Zend_Currency::NO_SYMBOL));
        $ar_curreny = explode($number, $formated);
        if ($ar_curreny['0'] != '') {
            return 'before';
        }
        return 'after';
    }

    public function getCurrencies() {
        $currencies = array();
        $codes = Mage::app()->getStore()->getAvailableCurrencyCodes(true);
        if (is_array($codes) && count($codes) > 1) {

            $rates = Mage::getModel('directory/currency')->getCurrencyRates(
                    Mage::app()->getStore()->getBaseCurrency(), $codes
            );
            foreach ($codes as $code) {
                if (isset($rates[$code])) {
                    $currencies[] = array(
                        'value' => $code,
                        'title' => Mage::app()->getLocale()->getTranslation($code, 'nametocurrency'),
                    );
                }
            }
        } elseif (count($codes) == 1) {
            $currencies[] = array(
                'value' => $codes[0],
                'title' => Mage::app()->getLocale()->getTranslation($codes[0], 'nametocurrency'),
            );
        }
        return $currencies;
    }

    public function setCurrency($data) {
        if (isset($data['params']['currency'])) {
            $currency = $data['params']['currency'];
            if ($currency) {
                Mage::app()->getStore()->setCurrentCurrencyCode($currency);
                Mage::app()->getCookie()->set('currency_code', $currency, TRUE);
            }
        }
    }

    public function setStoreView($data) {
        if (($data['resourceid'] == 'default') || ($data['resourceid'] == Mage::app()->getStore()))
            return;
        Mage::app()->getCookie()->set(Mage_Core_Model_Store::COOKIE_NAME, Mage::app()->getStore($data['resourceid'])->getCode(), TRUE);
        Mage::app()->setCurrentStore(
                Mage::app()->getStore($data['resourceid'])->getCode()
        );
        Mage::getSingleton('core/locale')->emulate($data['resourceid']);
    }

    public function getCurrentStoreId() {
        return Mage::app()->getStore()->getId();
    }

    public function getStores() {
        $storeAPIModel = Mage::getModel('simiconnector/api_stores');
        $storeAPIModel->setData($this->getData());
        $storeAPIModel->builderQuery = Mage::getModel('core/store_group')->getCollection()->addFieldToFilter('website_id', Mage::app()->getStore()->getWebsiteId());
        $storeAPIModel->pluralKey = 'stores';
        return $storeAPIModel->index();
    }
     */

}
