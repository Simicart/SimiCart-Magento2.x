<?php

namespace MobileApp\Connector\Controller\Customer\Forgot;

class Password extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Customer\Api\AccountManagementInterface';
        $this->_serviceMethodName = 'initiatePasswordReset';
        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $inputData = ['email' => $params['user_email'], 'template' => 'email_reset'];
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
        return ['status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}
