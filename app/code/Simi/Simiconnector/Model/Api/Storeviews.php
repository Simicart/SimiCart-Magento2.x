<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Storeviews extends Apiabstract
{

    public $storeviewInfo;
    public $DEFAULT_ORDER = 'store_id';
    public $method        = 'callApi';
    public $scope_interface;
    
    public function setBuilderQuery()
    {
        $data       = $this->getData();
        $collection = $this->simiObjectManager->get('\Magento\Store\Model\Store')->getCollection();
        if ($data['resourceid']) {
            $this->setStoreView($data);
            $this->setCurrency($data);
            $this->builderQuery = $this->simiObjectManager
                    ->get('\Magento\Store\Model\Store')->load($data['resourceid']);
        } else {
            $this->builderQuery = $collection
                    ->addFieldToFilter('group_id', $this->storeManager->getStore()->getGroupId());
        }
    }

    public function index()
    {
        $result = parent::index();
        foreach ($result['storeviews'] as $index => $storeView) {
            $result['storeviews'][$index]['base_url'] = $this->scopeConfig
                    ->getValue(
                        'simiconnector/general/base_url',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeView['store_id']
                    );
        }
        return $result;
    }

    public function show()
    {
        $country_code = $this->getStoreConfig('general/country/default');
        $country      = $this->simiObjectManager->get('\Magento\Directory\Model\Country')->loadByCode($country_code);

        $locale         = $this->scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        $currencyCode   = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $currency       = $this->simiObjectManager->create('Magento\Directory\Model\CurrencyFactory')
                ->create()->load($currencyCode);
        $currencySymbol = $currency->getCurrencySymbol();
        $options        = $this->simiObjectManager->get('Magento\Customer\Model\Customer')
                ->getAttribute('gender')->getSource()->getAllOptions();

        $values = [];
        foreach ($options as $option) {
            if ($option['value']) {
                $values[] = [
                    'label' => $option['label'],
                    'value' => $option['value'],
                ];
            }
        }

        $currencies = $this->getCurrencies();

        $cmsData               = $this->getData();
        $cmsData['resourceid'] = null;
        $cmsData['resource']   = 'cmspages';
        $model                 = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Cmspages');
        $callFunctionName = 'call_user_func_array';
        $cmsPageList           = $callFunctionName([&$model, $this->method], [$cmsData]);
        $base_url = $this->getStoreConfig('simiconnector/general/base_url');
        if ($this->getStoreConfig('web/url/use_store') && (!$base_url || $base_url=='')) {
            $base_url = $this->simiObjectManager->get('Magento\Framework\UrlInterface')
                ->getUrl('', ['_secure' => true]);
        }
        
        $connectorVersion = $this->simiObjectManager
            ->get('\Magento\Framework\Module\ResourceInterface')
            ->getDbVersion('Simi_Simiconnector');

        $customerSession = $this->simiObjectManager->get('Magento\Customer\Model\Session');
        $additionInfo = [
            'base'              => [
                'country_code'           => $country->getId(),
                'country_name'           => $country->getName(),
                'magento_version'        => '2',
                'locale_identifier'      => $locale,
                'store_id'               => $this->storeManager->getStore()->getId(),
                'store_name'             => $this->storeManager->getStore()->getName(),
                'store_code'             => $this->storeManager->getStore()->getCode(),
                'group_id'               => $this->storeManager->getStore()->getGroupId(),
                'base_url'               => $base_url,
                'use_store'              => $this->getStoreConfig('web/url/use_store'),
                'is_rtl'                 => $this->getStoreConfig('simiconnector/general/is_rtl'),
                'is_show_sample_data'    => $this->getStoreConfig('simiconnector/general/is_show_sample_data'),
                'android_sender'         => $this->getStoreConfig('simi_notifications/notification/android_app_key'),
                'currency_symbol'        => $currencySymbol,
                'currency_code'          => $currencyCode,
                'currency_position'      => $this->getCurrencyPosition(),
                'thousand_separator'     => $this->getStoreConfig('simiconnector/currency/thousand_separator'),
                'decimal_separator'      => $this->getStoreConfig('simiconnector/currency/decimal_separator'),
                'min_number_of_decimals' => $this->getStoreConfig('simiconnector/currency/min_number_of_decimals'),
                'max_number_of_decimals' => $this->getStoreConfig('simiconnector/currency/max_number_of_decimals'),
                'currencies'             => $currencies,
                'is_show_home_title'     => $this->getStoreConfig('simiconnector/general/is_show_home_title'),
                'cust_group'             => $customerSession->getGroupId(),
                'customer_identity'      => $customerSession->getSessionId(),
                'customer_ip'            => $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Data')->getRealIp(),
                'is_show_in_row_price' => $this->getStoreConfig('simiconnector/config_price/price_one_row'),
                'is_show_price_for_guest' => $this->getStoreConfig('simiconnector/config_price/is_show_price_for_guest'),
                'open_url_in_app' => $this->getStoreConfig('simiconnector/general/open_url_in_app'),
                'image_aspect_ratio' => $this->getStoreConfig('simiconnector/general/image_aspect_ratio'),
                'customer_email' => $customerSession->isLoggedIn()?$customerSession->getCustomer()->getEmail():null,
                'connector_version' => $connectorVersion,
                'is_support_put' => $this->getStoreConfig('simiconnector/methods_support/put'),
                'is_support_delete' => $this->getStoreConfig('simiconnector/methods_support/delete'),
                'pwa_studio_client_ver_number' => $this->getStoreConfig('simiconnector/general/pwa_studio_client_ver_number'),
                'default_title' => $this->getStoreConfig('design/head/default_title'),
                'default_description' => $this->getStoreConfig('design/head/default_description'),
                'title_prefix' => $this->getStoreConfig('design/head/title_prefix'),
                'title_suffix' => $this->getStoreConfig('design/head/title_suffix'),
                'default_keywords' => $this->getStoreConfig('design/head/default_keywords'),
            ],
            'sales'             => [
                'sales_reorder_allow'           => $this->getStoreConfig('sales/reorder/allow'),
                'sales_totals_sort_subtotal'    => $this->getStoreConfig('sales/totals_sort/subtotal'),
                'sales_totals_sort_discount'    => $this->getStoreConfig('sales/totals_sort/discount'),
                'sales_totals_sort_shipping'    => $this->getStoreConfig('sales/totals_sort/shipping'),
                'sales_totals_sort_weee'        => $this->getStoreConfig('sales/totals_sort/weee'),
                'sales_totals_sort_tax'         => $this->getStoreConfig('sales/totals_sort/tax'),
                'sales_totals_sort_grand_total' => $this->getStoreConfig('sales/totals_sort/grand_total'),
            ],
            'checkout'          => [
                'enable_guest_checkout' => $this->getStoreConfig('checkout/options/guest_checkout'),
                'enable_agreements'     => ($this->getStoreConfig('checkout/options/enable_agreements') === null) ?
                0 : $this->getStoreConfig('checkout/options/enable_agreements'),
                'checkout_webview' => [
                    'enable' => $this->getStoreConfig('simiconnector/checkout_config/enable_checkout_webview'),
                    'checkout_url' => $this->getStoreConfig('simiconnector/checkout_config/checkout_page_url'),
                    'success_url' => $this->getStoreConfig('simiconnector/checkout_config/url_success'),
                    'fail_url' => $this->getStoreConfig('simiconnector/checkout_config/url_failed'),
                ],
            ],
            'tax'               => [
                'tax_display_type'               => $this->getStoreConfig('tax/display/type'),
                'tax_display_shipping'           => $this->getStoreConfig('tax/display/shipping'),
                'tax_cart_display_price'         => $this->getStoreConfig('tax/cart_display/price'),
                'tax_cart_display_subtotal'      => $this->getStoreConfig('tax/cart_display/subtotal'),
                'tax_cart_display_shipping'      => $this->getStoreConfig('tax/cart_display/shipping'),
                'tax_cart_display_grandtotal'    => $this->getStoreConfig('tax/cart_display/grandtotal'),
                'tax_cart_display_full_summary'  => $this->getStoreConfig('tax/cart_display/full_summary'),
                'tax_cart_display_zero_tax'      => $this->getStoreConfig('tax/cart_display/zero_tax'),
                'tax_sales_display_price'        => $this->getStoreConfig('tax/sales_display/price'),
                'tax_sales_display_subtotal'     => $this->getStoreConfig('tax/sales_display/subtotal'),
                'tax_sales_display_shipping'     => $this->getStoreConfig('tax/sales_display/shipping'),
                'tax_sales_display_grandtotal'   => $this->getStoreConfig('tax/sales_display/grandtotal'),
                'tax_sales_display_full_summary' => $this->getStoreConfig('tax/sales_display/full_summary'),
                'tax_sales_display_zero_tax'     => $this->getStoreConfig('tax/sales_display/zero_tax'),
            ],
            'google_analytics'  => [
                'google_analytics_active'        => $this->getStoreConfig('google/analytics/active'),
                'google_analytics_type'          => $this->getStoreConfig('google/analytics/type'),
                'google_analytics_account'       => $this->getStoreConfig('google/analytics/account'),
                'google_analytics_anonymization' => $this->getStoreConfig('google/analytics/anonymization'),
            ],
            'customer'          => [
                'address_option' => [
                    'street_lines'    => $this->getStoreConfig('customer/address/street_lines'),
                    'prefix_show'     => $this->getStoreConfig('customer/address/prefix_show') ?
                $this->getStoreConfig('customer/address/prefix_show') : '',
                    'middlename_show' => $this->getStoreConfig('customer/address/middlename_show') ?
                $this->getStoreConfig('customer/address/middlename_show') : '',
                    'suffix_show'     => $this->getStoreConfig('customer/address/suffix_show') ?
                $this->getStoreConfig('customer/address/suffix_show') : '',
                    'dob_show'        => $this->getStoreConfig('customer/address/dob_show') ?
                $this->getStoreConfig('customer/address/dob_show') : '',
                    'taxvat_show'     => $this->getStoreConfig('customer/address/taxvat_show') ?
                $this->getStoreConfig('customer/address/taxvat_show') : '',
                    'gender_show'     => $this->getStoreConfig('customer/address/gender_show') ?
                $this->getStoreConfig('customer/address/gender_show') : '',
                    'gender_value'    => $values,
                ],
                'account_option' => [
                    'taxvat_show' => $this->getStoreConfig('customer/create_account/vat_frontend_visibility'),
                ],
                'password_validation' => $this->_passwordValidationConfiguration()
            ],
            'wishlist'          => [
                'wishlist_general_active'        => $this->getStoreConfig('wishlist/general/active'),
                'wishlist_wishlist_link_use_qty' => $this->getStoreConfig('wishlist/wishlist_link/use_qty'),
            ],
            'catalog'           => [
                'seo' => [
                    'product_url_suffix' => $this
                        ->getStoreConfig('catalog/seo/product_url_suffix'),
                    'category_url_suffix' => $this
                        ->getStoreConfig('catalog/seo/category_url_suffix'),
                    'product_use_categories_inherit' => $this
                        ->getStoreConfig('catalog/seo/product_use_categories_inherit'),
                ],
                'frontend'         => [
                    'view_products_default'                  => $this
                ->getStoreConfig('simiconnector/general/show_product_type'),
                    'is_show_zero_price'                     => $this
                ->getStoreConfig('simiconnector/general/is_show_price_zero'),
                    'is_show_link_all_product'               => $this
                ->getStoreConfig('simiconnector/general/is_show_all_product'),
                    'catalog_frontend_list_mode'             => $this
                ->getStoreConfig('catalog/frontend/list_mode'),
                    'catalog_frontend_grid_per_page_values'  => $this
                ->getStoreConfig('catalog/frontend/grid_per_page_values'),
                    'catalog_frontend_list_per_page'         => $this
                ->getStoreConfig('catalog/frontend/list_per_page'),
                    'catalog_frontend_list_allow_all'        => $this
                ->getStoreConfig('catalog/frontend/list_allow_all'),
                    'catalog_frontend_default_sort_by'       => $this
                ->getStoreConfig('catalog/frontend/default_sort_by'),
                    'catalog_frontend_flat_catalog_category' => $this
                ->getStoreConfig('catalog/frontend/flat_catalog_category'),
                    'catalog_frontend_flat_catalog_product'  => $this
                ->getStoreConfig('catalog/frontend/flat_catalog_product'),
                    'catalog_frontend_parse_url_directives'  => $this
                ->getStoreConfig('catalog/frontend/parse_url_directives'),
                    'show_discount_label_in_product'         => $this
                ->getStoreConfig('simiconnector/general/show_discount_label_in_product'),
                    'show_size_in_compare'                   => $this
                ->getStoreConfig('siminiaconfig/compareconfig/show_size_in_compare'),
                    'footer_title1'                            => $this
                ->getStoreConfig('siminiaconfig/footer_link/ft_title1'),
                    'footer_title2'                            => $this
                ->getStoreConfig('siminiaconfig/footer_link/ft_title2'),
                    'footer_link'                            => $this
                ->getStoreConfig('siminiaconfig/footer_link/ft_link'),
                ],
                'cataloginventory' => [
                    'cataloginventory_item_options_manage_stock'          => $this
                ->getStoreConfig('cataloginventory/item_options/manage_stock'),
                    'cataloginventory_item_options_backorders'            => $this
                ->getStoreConfig('cataloginventory/item_options/backorders'),
                    'cataloginventory_item_options_max_sale_qty'          => $this
                ->getStoreConfig('cataloginventory/item_options/max_sale_qty'),
                    'cataloginventory_item_options_min_qty'               => $this
                ->getStoreConfig('cataloginventory/item_options/options_min_qty'),
                    'cataloginventory_item_options_min_sale_qty'          => $this
                ->getStoreConfig('cataloginventory/item_options/min_sale_qty'),
                    'cataloginventory_item_options_notify_stock_qty'      => $this
                ->getStoreConfig('cataloginventory/item_options/notify_stock_qty'),
                    'cataloginventory_item_options_enable_qty_increments' => $this
                ->getStoreConfig('cataloginventory/item_options/enable_qty_increments'),
                    'cataloginventory_item_options_qty_increments'        => $this
                ->getStoreConfig('cataloginventory/item_options/qty_increments'),
                    'cataloginventory_item_options_auto_return'           => $this
                ->getStoreConfig('cataloginventory/item_options/auto_return'),
                ],
                'review'           => [
                    'catalog_review_allow_guest' => $this->getStoreConfig('catalog/review/allow_guest'),
                ],
            ],
            'cms'               => $cmsPageList,
            'category_cmspages' => $this->simiObjectManager
                ->get('\Simi\Simiconnector\Model\Cms')->getCategoryCMSPages(),
            'zopim_config'      => [
                'enable'       => $this->getStoreConfig('simiconnector/zopim/enable'),
                'account_key'  => $this->getStoreConfig('simiconnector/zopim/account_key'),
                'show_profile' => $this->getStoreConfig('simiconnector/zopim/show_profile'),
                'name'         => $this->getStoreConfig('simiconnector/zopim/name'),
                'email'        => $this->getStoreConfig('simiconnector/zopim/email'),
                'phone'        => $this->getStoreConfig('simiconnector/zopim/phone'),
            ],
            'mixpanel_config'   => [
                'token' => $this->getStoreConfig('simiconnector/mixpanel/token'),
            ],
            'allowed_countries' => $this->getAllowedCountries(),
            'stores'            => $this->getStores()
        ];

        if ($this->getStoreConfig('siminiaconfig/storeview_api/add_home_api_to_storeview_api')) {
            $additionInfo['home_lite'] = $this->_getHomeLite();
        }

        if ($checkout_info_setting = $this->simiObjectManager
                ->get('\Simi\Simiconnector\Helper\Address')->getCheckoutAddressSetting()) {
            $additionInfo['customer']['address_fields_config'] = $checkout_info_setting;
        }

        if ($checkout_terms = $this->simiObjectManager
                ->get('\Simi\Simiconnector\Helper\Checkout')->getCheckoutTermsAndConditions()) {
            $additionInfo['checkout']['checkout_terms_and_conditions'] = $checkout_terms;
        }

        if ($this->simiObjectManager->get('\Simi\Simiconnector\Helper\Instantcontact')->isEnabled()) {
            $additionInfo['instant_contact'] = $this->simiObjectManager
                    ->get('\Simi\Simiconnector\Helper\Instantcontact')->getContacts();
        }

        if (class_exists('Magento\Framework\Serialize\Serializer\Json')) {
            $serializerInterface = $this->simiObjectManager->get('Magento\Framework\Serialize\SerializerInterface');
            $contactEmails = $this->getStoreConfig('siminiaconfig/contactus/email');
            if ($contactEmails) {
                $contactEmails = $serializerInterface
                    ->unserialize($contactEmails);
            }
            $contactHotlines = $this->getStoreConfig('siminiaconfig/contactus/hotline');
            if ($contactHotlines) {
                $contactHotlines = $serializerInterface
                    ->unserialize($contactHotlines);
            }
            $contactSms = $this->getStoreConfig('siminiaconfig/contactus/sms');
            if ($contactSms) {
                $contactSms = $serializerInterface
                    ->unserialize($contactSms);
            }
            $contactWebsites = $this->getStoreConfig('siminiaconfig/contactus/website');
            if ($contactWebsites) {
                $contactWebsites = $serializerInterface
                    ->unserialize($contactWebsites);
            }
            $additionInfo['contactus'] = [
                'listEmail' => $contactEmails,
                'listHotline' => $contactHotlines,
                'listSms' => $contactSms,
                'listWebsite' => $contactWebsites
            ];
        }

        $this->storeviewInfo = $additionInfo;
        $this->simiObjectManager->get('\Magento\Framework\Event\ManagerInterface')
                ->dispatch('simiconnector_get_storeview_info_after', ['object' => $this]);
        return $this->getDetail($this->storeviewInfo);
    }

    public function getLocale()
    {
        $resolver = $this->simiObjectManager->get('Magento\Framework\Locale\Resolver');
        return $resolver->getLocale();
    }

    public function getAllowedCountries()
    {
        $cacheId = 'simi_allowed_countries_' . $this->storeManager->getStore()->getId();
        $data = $this->simiObjectManager
            ->get('Magento\Framework\App\CacheInterface')
            ->load($cacheId);
        if ($data && $arrayData = json_decode($data, true)) {
            return $arrayData;
        } else {
            $list = [];
            $country_default = $this->getStoreConfig('general/country/default');
            $countries = $this->simiObjectManager
                ->create('\Magento\Directory\Model\ResourceModel\Country\Collection')
                ->loadByStore($this->storeManager->getStore()->getId());
            $cache = null;
            foreach ($countries as $country) {
                if ($country_default == $country->getId()) {
                    $cache = [
                        'country_code' => $country->getId(),
                        'country_name' => $country->getName(),
                        'states' => $this->simiObjectManager
                            ->get('\Simi\Simiconnector\Helper\Address')->getStates($country->getId()),
                    ];
                } else {
                    $list[] = [
                        'country_code' => $country->getId(),
                        'country_name' => $country->getName(),
                        'states' => $this->simiObjectManager
                            ->get('\Simi\Simiconnector\Helper\Address')->getStates($country->getId()),
                    ];
                }
            }
            if ($cache) {
                array_unshift($list, $cache);
            }
            $this->simiObjectManager
                ->get('Magento\Framework\App\CacheInterface')
                ->save(json_encode($list), $cacheId);
            return $list;
        }
    }

    public function getCurrencyPosition()
    {
        $formated   = $this->storeManager->getStore()->getCurrentCurrency()->formatTxt(0);
        $number     = $this->storeManager->getStore()->getCurrentCurrency()
                ->formatTxt(0, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);
        $ar_curreny = explode($number, $formated);
        if ($ar_curreny['0'] != '') {
            return 'before';
        }
        return 'after';
    }
    
    public function getCurrencies()
    {
        $currencies = [];
        $codes      = $this->storeManager->getStore()->getAvailableCurrencyCodes(true);
        $locale     = $this->getLocale();
        foreach ($codes as $code) {
            $currencyTitle = '';
            try {
                $options    = $this->simiObjectManager->create('\Magento\Framework\CurrencyFactory')
                                ->create([null, $locale]);
                $currencyTitle = $options->getName($code, $locale);
            } catch (\Exception $e) {
                $currencyTitle = $code;
            }
            $currencies[] = [
                'value' => $code,
                'title' => $currencyTitle,
            ];
        }
        
        return $currencies;
    }

    public function setCurrency($data)
    {
        if (isset($data['params']['currency'])) {
            $currency = $data['params']['currency'];
            if ($currency) {
                $this->storeManager->getStore()->setCurrentCurrencyCode($currency);
            }
        }
    }

    public function setStoreView($data)
    {
        if (($data['resourceid'] == 'default') || ($data['resourceid'] == $this->storeManager->getStore()->getId())) {
            return;
        }
        try {
            $storeCode = $this->simiObjectManager
                ->get('Magento\Store\Model\StoreManagerInterface')->getStore($data['resourceid'])->getCode();

            $store = $this->storeRepository->getActiveStoreByCode($storeCode);

            $defaultStoreView = $this->storeManager->getDefaultStoreView();
            if ($defaultStoreView->getId() == $store->getId()) {
                $this->storeCookieManager->deleteStoreCookie($store);
            } else {
                $this->storeCookieManager->setStoreCookie($store);
            }

            $this->storeManager->setCurrentStore(
                $this->simiObjectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore($data['resourceid'])
            );
        } catch (\Exception $e) {

        }
    }

    public function getStores()
    {
        $storeAPIModel               = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Stores');
        $storeAPIModel->setData($this->getData());
        $storeAPIModel->builderQuery = $this->simiObjectManager
                ->get('\Magento\Store\Model\Group')->getCollection()
                ->addFieldToFilter('website_id', $this->storeManager->getStore()->getWebsiteId());
        $storeAPIModel->pluralKey    = 'stores';
        return $storeAPIModel->index();
    }

    private function _getHomeLite(){
        $data = $this->getData();
        $data['resourceid'] = 'lite';
        $homeAPIModel               = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Homes');
        $homeAPIModel->setData($data);
        $homeAPIModel->pluralKey    = 'homes';
        $homeAPIModel->singularKey = 'home';
        return $homeAPIModel->show();
    }

    private function _passwordValidationConfiguration(){
        $result = [];
        $result['minimum_password_length'] = $this->getStoreConfig('customer/password/minimum_password_length');
        $result['required_character_classes_number'] = $this->getStoreConfig('customer/password/required_character_classes_number');
        return $result;
    }
}
