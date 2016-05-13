<?php

namespace MobileApp\Connector\Controller\Checkout\Get\Allow;

class Countries extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Directory\Api\CountryInformationAcquirerInterface';
        $this->_serviceMethodName = 'getCountriesInfo';
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
        $scopeConfig = $this->scopeConfig;
        $defaultCountryCode = $scopeConfig->getValue('general/country/default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $countries = [];
        foreach($data as $country){
            if($defaultCountryCode == $country['id']){
                $defaultCountry = $this->_formatCountry($country);
            }else{
                $countries[] = $this->_formatCountry($country);
            }
        }

        array_unshift($countries, $defaultCountry);
        if(isset($data['message']))
            return ['status' => 'FAIL', 'message' => [$data['message']]];
        return ['data' => $countries, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }

    /*
     * Format country
     *
     * @param $country array
     * @return array
     */
    protected function _formatCountry($country){
        $states = [];
        if(isset($country['available_regions'])){
            foreach($country['available_regions'] as $region){
                $states[] = [
                    'state_id' => $region['id'],
                    'state_name' => $region['code'],
                    'state_code' => $region['name'],
                ];
            }
        }
        return  [
            'country_id' => $country['id'],
            'country_code' => $country['id'],
            'country_name' => $country['full_name_english'],
            'state' => $states
        ];
    }
}
