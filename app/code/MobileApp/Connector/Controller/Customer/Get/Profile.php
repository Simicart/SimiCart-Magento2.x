<?php

namespace MobileApp\Connector\Controller\Customer\Get;

class Profile extends \MobileApp\Connector\Controller\Connector
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
        $customerData = [
            'user_id' => $data['id'],
            'user_email' => $data['email'],
            'user_name' => $data['firstname'].' '.$data['lastname'],
        ];
        return ['data' => $customerData, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}
