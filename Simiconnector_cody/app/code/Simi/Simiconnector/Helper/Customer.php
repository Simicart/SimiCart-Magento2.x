<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper;


class Customer extends Data
{
    protected function _getSession() {
        return $this->_objectManager->get('Magento\Customer\Model\Session');
    }

    public function renewCustomerSesssion($data) {
        if ($data['params']['quote_id']) {
            $checkoutsession = $this->_objectManager->get('Magento\Checkout\Model\Session');
            $checkoutsession->setQuoteId($data['params']['quote_id']);
        }
        if (($data['resource'] == 'customers') && (($data['resourceid'] == 'login') || ($data['resourceid'] == 'sociallogin')))
            return;
        if ((!$data['params']['email']) || (!$data['params']['password']))
            return;
        try {
            $this->loginByEmailAndPass($data['params']['email'], $data['params']['password']);
        } catch (\Exception $e) {
            
        }
    }

    public function loginByEmailAndPass($username, $password) {
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $customer = $this->_objectManager->get('Magento\Customer\Model\Customer')
                ->setWebsiteId($websiteId);
        if ($password == md5($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue('simiconnector/general/secret_key') . $username)) {
            $customer = $this->getCustomerByEmail($username);
            if ($customer->getId()) {
                $this->loginByCustomer($customer);
                return true;
            }
        } else if ($customer->authenticate($username, $password)) {
            $this->loginByCustomer($customer);
            return true;
        }
        return false;
    }

    public function getCustomerByEmail($email) {
        return $this->_objectManager->get('Magento\Customer\Model\Customer')
                        ->setWebsiteId($this->_storeManager->getStore()->getWebsiteId())
                        ->loadByEmail($email);
    }

    public function loginByCustomer($customer) {
        $this->_getSession()->setCustomerAsLoggedIn($customer);
    }

}
