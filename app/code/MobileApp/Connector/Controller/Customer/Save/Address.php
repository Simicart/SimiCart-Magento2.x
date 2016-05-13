<?php

namespace MobileApp\Connector\Controller\Customer\Save;

class Address extends \MobileApp\Connector\Controller\Customer\Customer
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Customer\Api\AddressRepositoryInterface';
        $this->_serviceMethodName = 'save';

        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $session = $this->_objectManager->get('Magento\Customer\Model\Session');
        $customerId = $session->getCustomer()->getId();

        $customerData['id'] = $customerId;

        $customerName = $this->_parseCustomerName($params['name']);
        $address = [
            'customerId' => $customerId,
            'region' => [
                'regionCode' => isset($params['state_code'])?$params['state_code']:'',
                'region' => isset($params['state_name'])?$params['state_name']:'',
                'regionId' => isset($params['state_id'])?$params['state_id']:'',
            ],

            'regionId' => isset($params['state_id'])?$params['state_id']:'',
            'countryId' => $params['country_code'],
            'street' => [
                $params['street']
            ],

            'telephone' => $params['phone'],
            'postcode' => $params['zip'],
            'city' => $params['city'],
            'firstname' => $customerName['firstname'],
            'lastname' => $customerName['lastname'],
            'prefix' => $params['prefix'],
            'suffix' => $params['suffix'],
            'vatId' => $params['vat_id'],
        ];

        if(isset($params['address_id']))
            $address['id'] = $params['address_id'];

        $inputData = ['address' => $address];
        $this->_params = $inputData;

        return parent::execute();
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
        $countries = $this->_getCountries();
        $address = $this->_formatAddress($data, $countries);
        if(isset($data['message']))
            return ['status' => 'FAIL', 'message' => [$data['message']]];
        return ['data' => $address, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}
