<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Storeviews extends Apiabstract {

    protected $_DEFAULT_ORDER = 'store_id';
    protected $_method = 'callApi';
    protected $_scope_interface;

    public function setBuilderQuery() {
        $data = $this->getData();
        $collection = $this->_objectManager->get('\Magento\Store\Model\Store')->getCollection();
        if ($data['resourceid']) {
            $this->setStoreView($data);
            $this->setCurrency($data);
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
        $country_code = $this->getStoreConfig('general/country/default');
        $country = $this->_objectManager->get('\Magento\Directory\Model\Country')->loadByCode($country_code);

        $locale = $this->getLocale();
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currency = $this->_objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($currencyCode);
        $currencySymbol = $currency->getCurrencySymbol();
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

        $rtlCountry = $this->getStoreConfig('simiconnector/general/rtl_country');
        $isRtl = '0';
        $rtlCountry = explode(',', $rtlCountry);
        if (in_array($country_code, $rtlCountry)) {
            $isRtl = '1';
        }
        $currencies = $this->getCurrencies();


        $cmsData = $this->getData();
        $cmsData['resourceid'] = NULL;
        $cmsData['resource'] = 'cmspages';
        $model = $this->_objectManager->get('Simi\Simiconnector\Model\Api\Cmspages');
        $cmsPageList = call_user_func_array(array(&$model, $this->_method), array($cmsData));


        $additionInfo = array(
            'base' => array(
                'country_code' => $country->getId(),
                'country_name' => $country->getName(),
                'locale_identifier' => $locale,
                'store_id' => $this->_storeManager->getStore()->getId(),
                'store_name' => $this->_storeManager->getStore()->getName(),
                'store_code' => $this->_storeManager->getStore()->getCode(),
                'group_id' => $this->_storeManager->getStore()->getGroupId(),
                'base_url' => $this->getStoreConfig('simiconnector/general/base_url'),
                'use_store' => $this->getStoreConfig('web/url/use_store'),
                'is_rtl' => $isRtl,
                'is_show_sample_data' => $this->getStoreConfig('simiconnector/general/is_show_sample_data'),
                'android_sender' => $this->getStoreConfig('simiconnector/notification/android_app_key'),
                'currency_symbol' => $currencySymbol,
                'currency_code' => $currencyCode,
                'currency_position' => $this->getCurrencyPosition(),
                'thousand_separator' => $this->getStoreConfig('simiconnector/currency/thousand_separator'),
                'decimal_separator' => $this->getStoreConfig('simiconnector/currency/decimal_separator'),
                'min_number_of_decimals' => $this->getStoreConfig('simiconnector/currency/min_number_of_decimals'),
                'max_number_of_decimals' => $this->getStoreConfig('simiconnector/currency/max_number_of_decimals'),
                'currencies' => $currencies,
                'is_show_home_title' => $this->getStoreConfig('simiconnector/general/is_show_home_title'),
            ),
            'sales' => array(
                'sales_reorder_allow' => $this->getStoreConfig('sales/reorder/allow'),
                'sales_totals_sort_subtotal' => $this->getStoreConfig('sales/totals_sort/subtotal'),
                'sales_totals_sort_discount' => $this->getStoreConfig('sales/totals_sort/discount'),
                'sales_totals_sort_shipping' => $this->getStoreConfig('sales/totals_sort/shipping'),
                'sales_totals_sort_weee' => $this->getStoreConfig('sales/totals_sort/weee'),
                'sales_totals_sort_tax' => $this->getStoreConfig('sales/totals_sort/tax'),
                'sales_totals_sort_grand_total' => $this->getStoreConfig('sales/totals_sort/grand_total'),
            ),
            'checkout' => array(
                'enable_guest_checkout' => $this->getStoreConfig('checkout/options/guest_checkout'),
                'enable_agreements' => is_null($this->getStoreConfig('checkout/options/enable_agreements')) ? 0 : $this->getStoreConfig('checkout/options/enable_agreements'),
            ),
            'tax' => array(
                'tax_display_type' => $this->getStoreConfig('tax/display/type'),
                'tax_display_shipping' => $this->getStoreConfig('tax/display/shipping'),
                'tax_cart_display_price' => $this->getStoreConfig('tax/cart_display/price'),
                'tax_cart_display_subtotal' => $this->getStoreConfig('tax/cart_display/subtotal'),
                'tax_cart_display_shipping' => $this->getStoreConfig('tax/cart_display/shipping'),
                'tax_cart_display_grandtotal' => $this->getStoreConfig('tax/cart_display/grandtotal'),
                'tax_cart_display_full_summary' => $this->getStoreConfig('tax/cart_display/full_summary'),
                'tax_cart_display_zero_tax' => $this->getStoreConfig('tax/cart_display/zero_tax'),
            ),
            'google_analytics' => array(
                'google_analytics_active' => $this->getStoreConfig('google/analytics/active'),
                'google_analytics_type' => $this->getStoreConfig('google/analytics/type'),
                'google_analytics_account' => $this->getStoreConfig('google/analytics/account'),
                'google_analytics_anonymization' => $this->getStoreConfig('google/analytics/anonymization'),
            ),
            'customer' => array(
                'address_option' => array(
                    'street_lines' => $this->getStoreConfig('customer/address/street_lines'),
                    'prefix_show' => $this->getStoreConfig('customer/address/prefix_show') ? $this->getStoreConfig('customer/address/prefix_show') : '',
                    'middlename_show' => $this->getStoreConfig('customer/address/middlename_show') ? $this->getStoreConfig('customer/address/middlename_show') : '',
                    'suffix_show' => $this->getStoreConfig('customer/address/suffix_show') ? $this->getStoreConfig('customer/address/suffix_show') : '',
                    'dob_show' => $this->getStoreConfig('customer/address/dob_show') ? $this->getStoreConfig('customer/address/dob_show') : '',
                    'taxvat_show' => $this->getStoreConfig('customer/address/taxvat_show') ? $this->getStoreConfig('customer/address/taxvat_show') : '',
                    'gender_show' => $this->getStoreConfig('customer/address/gender_show') ? $this->getStoreConfig('customer/address/gender_show') : '',
                    'gender_value' => $values,
                ),
                'account_option' => array(
                    'taxvat_show' => $this->getStoreConfig('customer/create_account/vat_frontend_visibility'),
                ),
            ),
            'wishlist' => array(
                'wishlist_general_active' => $this->getStoreConfig('wishlist/general/active'),
                'wishlist_wishlist_link_use_qty' => $this->getStoreConfig('wishlist/wishlist_link/use_qty'),
            ),
            'catalog' => array(
                'frontend' => array(
                    'view_products_default' => $this->getStoreConfig('simiconnector/general/show_product_type'),
                    'is_show_zero_price' => $this->getStoreConfig('simiconnector/general/is_show_price_zero'),
                    'is_show_link_all_product' => $this->getStoreConfig('simiconnector/general/is_show_all_product'),
                    'catalog_frontend_list_mode' => $this->getStoreConfig('catalog/frontend/list_mode'),
                    'catalog_frontend_grid_per_page_values' => $this->getStoreConfig('catalog/frontend/grid_per_page_values'),
                    'catalog_frontend_list_per_page' => $this->getStoreConfig('catalog/frontend/list_per_page'),
                    'catalog_frontend_list_allow_all' => $this->getStoreConfig('catalog/frontend/list_allow_all'),
                    'catalog_frontend_default_sort_by' => $this->getStoreConfig('catalog/frontend/default_sort_by'),
                    'catalog_frontend_flat_catalog_category' => $this->getStoreConfig('catalog/frontend/flat_catalog_category'),
                    'catalog_frontend_flat_catalog_product' => $this->getStoreConfig('catalog/frontend/flat_catalog_product'),
                    'catalog_frontend_parse_url_directives' => $this->getStoreConfig('catalog/frontend/parse_url_directives'),
                ),
                'review' => array(
                    'catalog_review_allow_guest' => $this->getStoreConfig('catalog/review/allow_guest'),
                ),
            ),
            'cms' => $cmsPageList,
            'category_cmspages' => $this->_objectManager->get('\Simi\Simiconnector\Model\Cms')->getCategoryCMSPages(),
            'zopim_config' => array(
                'enable' => $this->getStoreConfig('simiconnector/zopim/enable'),
                'account_key' => $this->getStoreConfig('simiconnector/zopim/account_key'),
                'show_profile' => $this->getStoreConfig('simiconnector/zopim/show_profile'),
                'name' => $this->getStoreConfig('simiconnector/zopim/name'),
                'email' => $this->getStoreConfig('simiconnector/zopim/email'),
                'phone' => $this->getStoreConfig('simiconnector/zopim/phone'),
            ),
            'mixpanel_config' => array(
                'token' => $this->getStoreConfig('simiconnector/mixpanel/token'),
            ),
            'allowed_countries' => $this->getAllowedCountries(),
            'stores' => $this->getStores(),
        );

        if ($checkout_info_setting = $this->_objectManager->get('\Simi\Simiconnector\Helper\Address')->getCheckoutAddressSetting())
            $additionInfo['customer']['address_fields_config'] = $checkout_info_setting;

        if ($checkout_terms = $this->_objectManager->get('\Simi\Simiconnector\Helper\Checkout')->getCheckoutTermsAndConditions())
            $additionInfo['checkout']['checkout_terms_and_conditions'] = $checkout_terms;

        if ($this->_objectManager->get('\Simi\Simiconnector\Helper\Instantcontact')->isEnabled())
            $additionInfo['instant_contact'] = $this->_objectManager->get('\Simi\Simiconnector\Helper\Instantcontact')->getContacts();

        $information['storeview'] = $additionInfo;
        return $information;
    }

    function getLocale() {
        $resolver = $this->_objectManager->get('Magento\Framework\Locale\Resolver');
        return $resolver->getLocale();
    }

    public function getAllowedCountries() {
        $list = array();
        $country_default = $this->getStoreConfig('general/country/default');
        $countries = $this->_objectManager->create('\Magento\Directory\Model\ResourceModel\Country\Collection')->loadByStore($this->_storeManager->getStore()->getId());
        $cache = null;
        foreach ($countries as $country) {
            if ($country_default == $country->getId()) {
                $cache = array(
                    'country_code' => $country->getId(),
                    'country_name' => $country->getName(),
                    'states' => $this->_objectManager->get('\Simi\Simiconnector\Helper\Address')->getStates($country->getId()),
                );
            } else {
                $list[] = array(
                    'country_code' => $country->getId(),
                    'country_name' => $country->getName(),
                    'states' => $this->_objectManager->get('\Simi\Simiconnector\Helper\Address')->getStates($country->getId()),
                );
            }
        }
        if ($cache) {
            array_unshift($list, $cache);
        }
        return $list;
    }

    public function getCurrencyPosition() {
        $formated = $this->_storeManager->getStore()->getCurrentCurrency()->formatTxt(0);
        $number = $this->_storeManager->getStore()->getCurrentCurrency()->formatTxt(0, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);
        $ar_curreny = explode($number, $formated);
        if ($ar_curreny['0'] != '') {
            return 'before';
        }
        return 'after';
    }

    public function getCurrencies() {
        $currencies = array();
        $codes = $this->_storeManager->getStore()->getAvailableCurrencyCodes(true);
        $locale = $this->getLocale();
        $options = new \Zend_Currency(null, $locale);

        if (is_array($codes) && count($codes) > 1) {
            $rates = $this->_objectManager
                            ->create('Magento\Directory\Model\ResourceModel\Currency')->getCurrencyRates(
                    $this->_storeManager->getStore()->getCurrentCurrencyCode(), $codes
            );
            foreach ($codes as $code) {
                if (isset($rates[$code])) {
                    $currencies[] = array(
                        'value' => $code,
                        'title' => $options->getName($code, $locale),
                    );
                }
            }
        } elseif (count($codes) == 1) {
            $currencies[] = array(
                'value' => $codes[0],
                'title' => $options->getName($codes[0], $locale),
            );
        }
        return $currencies;
    }

    public function setCurrency($data) {
        if (isset($data['params']['currency'])) {
            $currency = $data['params']['currency'];
            if ($currency) {
                $this->_storeManager->getStore()->setCurrentCurrencyCode($currency);
                //$this->_objectManager->get('Vendor\Module\Cookie\Example')->set('currency_code', $currency, TRUE);
            }
        }
    }

    public function setStoreView($data) {
        if (($data['resourceid'] == 'default') || ($data['resourceid'] == $this->_storeManager->getStore()->getId()))
            return;
        $storeCode = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore($data['resourceid'])->getCode();

        $store = $this->storeRepository->getActiveStoreByCode($storeCode);

        $defaultStoreView = $this->_storeManager->getDefaultStoreView();
        if ($defaultStoreView->getId() == $store->getId()) {
            $this->storeCookieManager->deleteStoreCookie($store);
        } else {
            $this->storeCookieManager->setStoreCookie($store);
        }

        $this->_storeManager->setCurrentStore(
                $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore($data['resourceid'])
        );
    }

    public function getStores() {
        $storeAPIModel = $this->_objectManager->get('Simi\Simiconnector\Model\Api\Stores');
        $storeAPIModel->setData($this->getData());
        $storeAPIModel->builderQuery = $this->_objectManager->get('\Magento\Store\Model\Group')->getCollection()->addFieldToFilter('website_id', $this->_storeManager->getStore()->getWebsiteId());
        $storeAPIModel->pluralKey = 'stores';
        return $storeAPIModel->index();
    }

}
