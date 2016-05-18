<?php

namespace MobileApp\Connector\Controller\Customer\Sign;

class In extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Quote\Api\CartManagementInterface';
        $this->_serviceMethodName = 'getCartForCustomer';
        $params = $this->_getParams();

        $customer = $this->_objectManager->get('Magento\Customer\Model\Customer')
                ->setWebsiteId($this->storeManager->getStore()->getWebsiteId());

        try{
            if($customer->authenticate($params['user_email'], $params['user_password'])){
                $session = $this->_objectManager->get('Magento\Customer\Model\Session');
                $session->setCustomerAsLoggedIn($customer);
                $this->_params = ['customerId' => $customer->getId()];
                return parent::execute();
            }
        }catch (\Exception $e){
            $outputData = ['status' => 'FAIL', 'message' => [$e->getMessage()]];
            /** @param \Magento\Framework\Controller\Result\Json $result */
            $result = $this->resultJsonFactory->create();
            return $result->setData($outputData);
        }
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

        $customerData = [
            'user_id' => $data['customer']['id'],
            'user_name' => $data['customer']['firstname'].' '.$data['customer']['lastname'],
            'user_email' => $data['customer']['email'],
            'cart_qty' => $data['items_qty'],
            'loyalty_balance' => '',
        ];
        return ['data' => $customerData, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}
