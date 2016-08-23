<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;


class Customers extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'entity_id';
    protected $_RETURN_MESSAGE;

    public function setBuilderQuery() {
        $data = $this->getData();
        if ($data['resourceid']) {
            switch ($data['resourceid']) {
                case 'forgetpassword':
                    $this->_objectManager->get('Simi\Simiconnector\Model\Customer')->forgetPassword($data);
                    $email = $data['params']['email'];
                    $this->builderQuery = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
                    $this->_RETURN_MESSAGE = $message = __(
                        'If there is an account associated with %1 you will receive an email with a link to reset your password.',
                        $email);
                    break;
                case 'profile':
                    $this->builderQuery = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
                    break;
                case 'login':
                    if ($this->_objectManager->get('Simi\Simiconnector\Model\Customer')->login($data))
                        $this->builderQuery = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
                    else
                        throw new \Exception($this->_helper->__('Login Failed'), 4);
                    break;
                case 'sociallogin':
                    $this->builderQuery = $this->_objectManager->get('Simi\Simiconnector\Model\Customer')->socialLogin($data);
                case 'logout':
                    $lastCustomerId = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer()->getId();
                    if ($this->_objectManager->get('Simi\Simiconnector\Model\Customer')->logout($data))
                        $this->builderQuery = $this->_objectManager->get('Magento\Customer\Model\Customer')->load($lastCustomerId);
                    else
                        throw new Exception($this->_helper->__('Logout Failed'), 4);
                    break;
                default:
                    $this->builderQuery = $this->_objectManager->get('Magento\Customer\Model\Customer')->setWebsiteId($this->_storeManager->getStore()->getWebsiteId())->load($data['resourceid']);
                    if (!$this->builderQuery->getId())
                        $this->builderQuery = $this->_objectManager->get('Magento\Customer\Model\Customer')->setWebsiteId($this->_storeManager->getStore()->getWebsiteId())->loadByEmail($data['resourceid']);
                    break;
            }
        } else {
            $currentCustomerId = $this->_objectManager->get('Magento\Customer\Model\Session')->getId();
            $this->builderQuery = $this->_objectManager->get('Magento\Customer\Model\Customer')->getCollection()
                    ->addFieldToFilter('entity_id', $currentCustomerId);
        }
    }

    /*
     * Register
     */

    public function store() {
        $data = $this->getData();
        $customer = $this->_objectManager->get('Simi\Simiconnector\Model\Customer')->register($data);
        $this->builderQuery = $customer;
        $this->_RETURN_MESSAGE = __("Thank you for registering with " . $this->_storeManager->getStore()->getName() . " store");
        return $this->show();
    }

    /*
     * Update Profile
     */

    public function update() {
        $data = $this->getData();
        $customer = $this->_objectManager->get('Simi\Simiconnector\Model\Customer')->updateProfile($data);
        $this->builderQuery = $customer;
        $this->_RETURN_MESSAGE = __('The account information has been saved.');
        return $this->show();
    }

    /*
     * Add Message
     */

    public function getDetail($info) {
        if ($this->_RETURN_MESSAGE) {
            $resultArray = parent::getDetail($info);
            $resultArray['message'] = array($this->_RETURN_MESSAGE);
            return $resultArray;
        }
        return parent::getDetail($info);
    }

}
