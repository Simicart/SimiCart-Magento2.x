<?php

namespace MobileApp\Connector\Controller\Customer\Get\User;

class Addresses extends \MobileApp\Connector\Controller\Customer\Customer
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Customer\Api\CustomerRepositoryInterface';
        $this->_serviceMethodName = 'getById';

        $session = $this->_objectManager->get('Magento\Customer\Model\Session');
        $customerId = $session->getCustomer()->getId();

        $this->_params = ['customerId' => $customerId];
        return parent::execute();
    }

    /*
     * Format data
     *
     * @param $data array
     * @return array
     */
    protected function _formatData($data){
        $addresses = [];
        $countries = $this->_getCountries();
        foreach($data['addresses'] as $address){
            $addresses[] = $this->_formatAddress($address, $countries);
        }
        return ['data' => $addresses, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}
