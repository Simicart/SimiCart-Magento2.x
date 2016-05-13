<?php

namespace MobileApp\Connector\Controller\Checkout\Get;

class States extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Directory\Api\CountryInformationAcquirerInterface';
        $this->_serviceMethodName = 'getCountryInfo';

        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $this->_params = ['countryId' => $params['country_code']];
        return parent::execute();
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
        $states = [];
        if(isset($data['available_regions'])){
            foreach($data['available_regions'] as $region){
                $states[] = [
                    'state_id' => $region['id'],
                    'state_name' => $region['code'],
                    'state_code' => $region['name'],
                ];
            }
        }

        if(isset($data['message']))
            return ['status' => 'FAIL', 'message' => [$data['message']]];
        return ['data' => $states, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}
