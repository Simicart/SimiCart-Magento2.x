<?php

namespace MobileApp\Connector\Controller\Customer\Change;

class User extends \MobileApp\Connector\Controller\Customer\Customer
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Customer\Api\CustomerRepositoryInterface';
        $this->_serviceMethodName = 'save';

        $params = $this->_getParams();

        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $session = $this->_objectManager->get('Magento\Customer\Model\Session');
        $customerId = $session->getCustomer()->getId();


        $isChangePassword = $params['change_password'];
        if($isChangePassword){
            $passwordData = [
                'currentPassword' => $params['old_password'],
                'newPassword' => $params['new_password'],
                'customerId' => $customerId
            ];
            $serviceClassName = 'Magento\Customer\Api\AccountManagementInterface';
            $serviceMethodName = 'changePasswordById';
            $output = $this->getOutputData($passwordData, $serviceClassName, $serviceMethodName);

            if(isset($output['message'])){
                /** @param \Magento\Framework\Controller\Result\Json $result */
                $result = $this->resultJsonFactory->create();
                return $result->setData(['status' => 'FAIL', 'message' => [$output['message']]]);

            }
        }

        $customerData = $this->_parseCustomerName($params['user_name']);
        $customerData['email'] = $params['user_email'];
        $customerData['id'] = $customerId;
        $customerData['website_id'] = $websiteId;


        unset($params['user_email']);
        unset($params['user_name']);
        unset($params['old_password']);
        unset($params['new_password']);
        unset($params['com_password']);
        unset($params['change_password']);

        foreach($params as $key => $value){
            $customerData[$key] = $value;
        }

        $inputData = ['customer' => $customerData];
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
