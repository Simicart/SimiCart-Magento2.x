<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

class Customer extends Data
{

    public function _getSession()
    {
        return $this->simiObjectManager->get('Magento\Customer\Model\Session');
    }

    public function renewCustomerSesssion($data)
    {
        if (isset($data['params']['quote_id']) && $data['params']['quote_id']) {
            $checkoutsession = $this->simiObjectManager->get('Magento\Checkout\Model\Session');
            $checkoutsession->setQuoteId($data['params']['quote_id']);
        }
        if (($data['resource'] == 'customers')
                && (($data['resourceid'] == 'login') || ($data['resourceid'] == 'sociallogin'))) {
            return;
        }
        if (isset($data['contents_array']['email']) && isset($data['contents_array']['password'])) {
            $data['params']['email']    = $data['contents_array']['email'];
            $data['params']['password'] = $data['contents_array']['password'];
        }

        if ((!$data['params']['email']) || (!$data['params']['password'])) {
            return;
        }

        if (($this->_getSession()->isLoggedIn()) && 
            ($this->_getSession()->getCustomer()->getEmail() == $data['params']['email'])){
            return;
        }
        try {
            $this->loginByEmailAndPass($data['params']['email'], $data['params']['password']);
        } catch (\Exception $e) {
            return;
        }
    }

    public function loginByEmailAndPass($username, $password)
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customer  = $this->simiObjectManager->get('Magento\Customer\Model\Customer')
                ->setWebsiteId($websiteId);
        if ($this->validateSimiPass($username, $password)) {
            $customer = $this->getCustomerByEmail($username);
            if ($customer->getId()) {
                $this->loginByCustomer($customer);
                return true;
            }
        } elseif ($customer->authenticate($username, $password)) {
            $this->loginByCustomer($customer);
            return true;
        }
        return false;
    }

    public function getCustomerByEmail($email)
    {
        return $this->simiObjectManager->get('Magento\Customer\Model\Customer')
                        ->setWebsiteId($this->storeManager->getStore()->getWebsiteId())
                        ->loadByEmail($email);
    }

    public function loginByCustomer($customer)
    {
        $this->_getSession()->setCustomerAsLoggedIn($customer);
    }

    /*
     * $customer - Customer Model
     * $data - Data Object
     * a. Magento\Customer\Model\Data\Customer
     * b. Magento\Customer\Model\Customer
     *
     */

    public function applyDataToCustomer(&$customer, $data)
    {
        if (isset($data->day) && $data->day != "") {
            $birthday = $data->year . "-" . $data->month . "-" . $data->day;
            $customer->setDob($birthday);
        }

        if (isset($data->taxvat)) {
            $customer->setTaxvat($data->taxvat);
        }

        if (isset($data->gender) && $data->gender) {
            $customer->setGender($data->gender);
        }
        if (isset($data->prefix) && $data->prefix) {
            $customer->setPrefix($data->prefix);
        }

        if (isset($data->middlename) && $data->middlename) {
            $customer->setMiddlename($data->middlename);
        }

        if (isset($data->suffix) && $data->suffix) {
            $customer->setSuffix($data->suffix);
        }
        if (!isset($data->password)) {
            $encodeMethod = 'md5';
            $data->password = 'simipassword'
                    . rand(pow(10, 9), pow(10, 10)) . substr($encodeMethod(microtime()), rand(0, 26), 5);
        }
    }

    public function validateSimiPass($username, $password)
    {
        $encodeMethod = 'md5';
        if ($password == $encodeMethod($this->simiObjectManager
                ->get('Magento\Framework\App\Config\ScopeConfigInterface')
                                ->getValue('simiconnector/general/secret_key') . $username)) {
            return true;
        }
        return false;
    }
}
