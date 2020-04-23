<?php

namespace Simi\Simiconnector\Model\Api;

class Sociallogins extends Apiabstract
{

    public $DEFAULT_ORDER = 'id';
    public $RETURN_MESSAGE;

    public function setBuilderQuery()
    {

        $data = $this->getData();
        $socialCustomerModel = $this->simiObjectManager->get('Simi\Simiconnector\Model\Customermap');
        $customerHelper = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Customer');
        $params = $data['params'];
        $customer = $socialCustomerModel->getCustomer($params);
        $customerHelper->loginByCustomer($customer);
        $this->builderQuery = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getCustomer();
        if(!$this->builderQuery->getId()){
            throw new \Exception(__('Login Failed'), 4);
        }
    }

    public function index()
    {
        return $this->show();
    }

    public function getDetail($info)
    {
        $data = $this->getData();
        if (isset($info['email'])) {
            if ($this->simiObjectManager->get('\Magento\Newsletter\Model\Subscriber') &&
                $this->simiObjectManager->get('\Magento\Newsletter\Model\Subscriber')
                    ->loadByEmail($info['email'])->isSubscribed()) {
                $info['news_letter'] = '1';
            } else {
                $info['news_letter'] = '0';
            }
            $hash = $this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Customer')
                ->getToken($data);
            $info['simi_hash'] = $hash;
            $info['social_login'] = true;
        }
        return ['customer' => $this->modifyFields($info)];
    }
}
