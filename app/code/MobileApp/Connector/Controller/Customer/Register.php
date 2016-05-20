<?php

namespace MobileApp\Connector\Controller\Customer;

class Register extends \MobileApp\Connector\Controller\Customer\Customer
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Customer\Api\AccountManagementInterface';
        $this->_serviceMethodName = 'createAccount';
        $params = $this->_getParams();

        $customerData = $this->_parseCustomerName($params['user_name']);
        $customerData['email'] = $params['user_email'];
        $password = $params['user_password'];

        unset($params['user_email']);
        unset($params['user_name']);
        unset($params['user_password']);
        foreach($params as $key => $value){
            $customerData[$key] = $value;
        }

        $inputData = ['customer' => $customerData, 'password' => $password];
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
        if(isset($data['message']))
            return ['status' => 'FAIL', 'message' => [$data['message']]];
        return ['data' => ['user_id' => $data['id']], 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}
