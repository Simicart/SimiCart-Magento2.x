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

    public function _getCart()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
    }

    public function renewCustomerSession($data)
    {
        if (isset($data['params']['quote_id']) && $data['params']['quote_id']) {
            $quoteId = $data['params']['quote_id'];
            $quoteIdMask = $this->simiObjectManager->get('Magento\Quote\Model\QuoteIdMask');
            if ($quoteIdMask->load($quoteId, 'masked_id')) {
                if ($quoteIdMask && $maskQuoteId = $quoteIdMask->getData('quote_id'))
                    $quoteId = $maskQuoteId;
            }
            $quoteModel = $this->simiObjectManager->create('Magento\Quote\Model\Quote')->load($quoteId);
            if ($quoteModel->getId() && $quoteModel->getData('is_active')) {
                try {
                    $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->setQuoteToSession($quoteModel);
                } catch (\Exception $e) {

                }
            }
        }
        if (($data['resource'] == 'customers')
                && (($data['resourceid'] == 'login') || ($data['resourceid'] == 'sociallogin'))) {
            return;
        }
        if (isset($data['params']['email']) && isset($data['params']['simi_hash'])) {
            $data['params']['password'] = $data['params']['simi_hash'];
        } else if (isset($data['contents_array']['email'])) {
            if (isset($data['contents_array']['password'])) {
                $data['params']['email']    = $data['contents_array']['email'];
                $data['params']['password'] = $data['contents_array']['password'];
            } else if (isset($data['contents_array']['simi_hash'])) {
                $data['params']['email']    = $data['contents_array']['email'];
                $data['params']['password'] = $data['contents_array']['simi_hash'];
            }
        }

        if (!isset($data['params']['email']) || !isset($data['params']['password'])) {
            return;
        }

        if (($this->_getSession()->isLoggedIn()) &&
            ($this->_getSession()->getCustomer()->getEmail() == $data['params']['email'])) {
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
//        if (!isset($data->password)) {
//            $encodeMethod = 'md5';
//            $data->password = 'simipassword'
//                    . rand(pow(10, 9), pow(10, 10)) . substr($encodeMethod(microtime()), rand(0, 26), 5);
//        }
    }

    public function validateSimiPass($username, $password, $from = null)
    {
        $tokenModel = $this->simiObjectManager->get('Simi\Simiconnector\Model\Customertoken')
            ->getCollection()
            ->addFieldToFilter('token', $password)
            ->getFirstItem();
        if ($tokenModel->getId() && $customerId = $tokenModel->getData('customer_id')) {
            $customerModel = $this->simiObjectManager->get('Magento\Customer\Model\Customer')->load($customerId);
            if ($customerEmail = $customerModel->getData('email')) {
                if (strtolower($customerEmail) == strtolower($username))
                    return true;
            }
        }
        $encodeMethod = 'md5';
        if ($from && $from == 'social_login') {
            if ($password == 'Simi123a@'.$encodeMethod($this->simiObjectManager
                    ->get('Magento\Framework\App\Config\ScopeConfigInterface')
                                ->getValue('simiconnector/general/secret_key') . $username)) {
                return true;
            }
        }
        if ($password == $encodeMethod($this->simiObjectManager
                ->get('Magento\Framework\App\Config\ScopeConfigInterface')
                                ->getValue('simiconnector/general/secret_key') . $username)) {
            return true;
        }
        return false;
    }


    public function getToken($data) {
        $customerSession = $this->_getSession();
        if ($customerSession->isLoggedIn()) {
            $customerId = $this->_getSession()->getCustomer()->getId();
            if ($customerId) {
                $createNewToken = false;
                // if ($data && isset($data['resourceid']) && $data['resourceid'] == 'login')
                //     $createNewToken = true;
                // else if ($data && isset($data['resource']) && $data['resource'] == 'sociallogins')
                //     $createNewToken = true;

                $tokenModel = $this->simiObjectManager->create('Simi\Simiconnector\Model\Customertoken')
                    ->getCollection()
                    ->addFieldToFilter('customer_id', $customerId)
                    ->getFirstItem();
                if (!$tokenModel->getId() || $createNewToken) {
                    $encodeMethod = 'md5';
                    $newToken = 'tk_'
                    . $encodeMethod(rand(pow(10, 9), pow(10, 10)))
                    . $encodeMethod(microtime());
                    $tokenModel->setData('token', $newToken);
                    $tokenModel->setData('customer_id', $customerId);
                    $tokenModel->setData('created_time', time());
                    $tokenModel->save();
                }
                return $tokenModel->getData('token');
            }
        }
        return '';
    }
}
