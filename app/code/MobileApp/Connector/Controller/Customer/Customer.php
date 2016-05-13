<?php

namespace MobileApp\Connector\Controller\Customer;

class Customer extends \MobileApp\Connector\Controller\Connector
{
    /*
     * Format address
     *
     * @param $address array
     * @param $countries array
     * @return array
     */
    protected function _formatAddress($address, $countries){
        return [
            'address_id' => $address['id'],
            'name' => $address['firstname'].' '.$address['lastname'],
            'prefix'=> isset($address['prefix'])?$address['prefix']:'',
            'suffix'=> isset($address['suffix'])?$address['suffix']:'',
            'taxvat'=> '',
            'vat_id'=> isset($address['vat_id'])?$address['vat_id']:'',
            'street'=> implode(', ', $address['street']),
            'city'=> $address['city'],
            'state_name'=> isset($address['region']['region'])?$address['region']['region']:'',
            'state_id'=> isset($address['region']['region_id'])?$address['region']['region_id']:'',
            'state_code'=> isset($address['region']['region_code'])?$address['region']['region_code']:'',
            'zip'=> $address['postcode'],
            'country_code'=> $address['country_id'],
            'country_name' => $countries[$address['country_id']],
            'phone'=> $address['telephone'],
            'email'=> ''
        ];
    }

    /*
     * Get countries
     *
     * @return array
     */
    protected function _getCountries(){
        $countries = [];
        $serviceClassName = 'Magento\Directory\Api\CountryInformationAcquirerInterface';
        $serviceMethodName = 'getCountriesInfo';
        $output = $this->getOutputData([], $serviceClassName, $serviceMethodName);
        foreach($output as $item){
            $countries[$item['id']] = $item['full_name_english'];
        }
        return $countries;
    }
}
