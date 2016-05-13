<?php

namespace MobileApp\Connector\Controller\Config\Get\Store;

class View extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Store\Api\StoreConfigManagerInterface';
        $this->_serviceMethodName = 'getStoreConfigs';
        $this->_params = [];
        return parent::execute();
    }

    /*
    * Format data
    *
    * @param $data array
    * @return array
    */
    protected function _formatData($data){
        $configs = [];

        $storeConfigs = $data[0];

        $scopeConfig = $this->scopeConfig;
        $websiteScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        //country
        $countryCode = $scopeConfig->getValue('general/country/default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $countryName = $this->_objectManager->get('Magento\Directory\Model\Country')->loadByCode($countryCode)->getName();

        //currency
        $currencyCode = $storeConfigs['default_display_currency_code'];
        $currency = $this->_objectManager->get('Magento\Directory\Model\Currency')->load($currencyCode);
        $currencySymbol = $currency->getCurrencySymbol();
        $currencySymbolPosition = $this->_getSymbolPosition($currency);

        //store
        $storeName = $this->_objectManager->get('Magento\Store\Model\Store')->load($storeConfigs['code'])->getName();

        //right to left
        $rtlCountry = $scopeConfig->getValue('connector/general/rtl_country', $storeScope);
        $isRtl = '0';
        $rtlCountry = explode(',', $rtlCountry);
        if(in_array($countryCode, $rtlCountry)){
            $isRtl = '1';
        }

        $configs['store_config'] = [
            'country_code' => $countryCode,
			'country_name' => $countryName,
			'locale_identifier' => $storeConfigs['locale'],
			'currency_symbol' => $currencySymbol,
			'currency_code' => $currencyCode,
			'currency_position' => $currencySymbolPosition,
			'store_id' => $storeConfigs['id'],
			'store_name' => $storeName,
			'store_code' => $storeConfigs['code'],
            'is_show_zero_price' => $scopeConfig->getValue('connector/general/is_show_price_zero', $websiteScope),
            'is_show_link_all_product' => $scopeConfig->getValue('connector/general/is_show_all_product', $websiteScope),
            'use_store' => $scopeConfig->getValue('web/url/use_store', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES),
            'is_reload_payment_method' => $scopeConfig->getValue('connector/general/is_reload_payment_method', $websiteScope),
            'is_rtl' => $isRtl,
        ];

        $options = $this->_objectManager->get('Magento\Customer\Model\Customer')->getAttribute('gender')
            ->getSource()->getAllOptions();

        foreach ($options as $key => $option) {
            if (!$option['value'])
                unset($options[$key]);
        }



        $configs['customer_address_config'] = [
            'prefix_show' => $scopeConfig->getValue('customer/address/prefix_show', $websiteScope),
			'suffix_show' => $scopeConfig->getValue('customer/address/suffix_show', $websiteScope),
			'dob_show' => $scopeConfig->getValue('customer/address/dob_show', $websiteScope),
			'taxvat_show' => $scopeConfig->getValue('customer/address/taxvat_show', $websiteScope),
			'gender_show' => $scopeConfig->getValue('customer/address/gender_show', $websiteScope),
            'gender_value' => $options
        ];


        $configs['checkout_config'] = [
            'enable_guest_checkout' => $scopeConfig->getValue('checkout/options/guest_checkout', $storeScope),
            'enable_agreements' => is_null($scopeConfig->getValue('checkout/options/enable_agreements', $storeScope)) ? 0 :
                $scopeConfig->getValue('checkout/options/enable_agreements', $storeScope),
            'taxvat_show' => $scopeConfig->getValue('customer/create_account/vat_frontend_visibility', $websiteScope),
        ];

        $configs['view_products_default'] = $scopeConfig->getValue('connector/general/show_product_type', $websiteScope);
        $configs['android_sender'] = $scopeConfig->getValue('connector/android_sendid', $websiteScope);

        return ['data' => $configs, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }

    /*
     * Get currency symbol position
     *
     * @param $currency \Magento\Directory\Model\Currency
     * @return string
     */
    protected function _getSymbolPosition($currency){
        $formatted = $currency->formatTxt(0);
        $number = $currency->formatTxt(0, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);
        $temp = explode($number, $formatted);

        if ($temp['0'] != '')
            return 'before';
        return 'after';
    }
}
