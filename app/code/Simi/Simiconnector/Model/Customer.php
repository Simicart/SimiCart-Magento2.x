<?php

namespace Simi\Simiconnector\Model;


use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
/**
 * Simiconnector Model
 *
 * @method \Simi\Simiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simiconnector\Model\Resource\Page getResource()
 */
class Customer extends \Magento\Framework\Model\AbstractModel
{
    protected  $_objectManager;
    protected  $_storeManager;
    public  $cookieMetadataFactory;
    public  $cookieMetadataManager;



    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        
    }

    
    protected function _helperCustomer() {
        return $this->_objectManager->get('Simi\Simiconnector\Helper\Customer');
    }

    protected function _getSession() {
        return $this->_objectManager->get('Magento\Customer\Model\Session');
    }

    public function getCustomerByEmail($email) {
        return $this->_helperCustomer()->getCustomerByEmail($email);
    }
    
    public function getAccountManagement() {
        return $this->_objectManager->get('Magento\Customer\Api\AccountManagementInterface');
    }

    public function forgetPassword($data) {
        $data = $data['params'];
        $email = $data['email'];
        if (is_null($email)) {
            throw new \Exception(__('No email was sent'), 4);
        } else {
            if (!\Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                throw new \Exception(__('Please correct the email address.'), 4);
            }
            $customer = $this->_helperCustomer()->getCustomerByEmail($email);
            if ($customer->getId()) {
                $this->getAccountManagement()->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
            } else {
                throw new \Exception(__('Customer is not exist'));
            }
        }
    }

    public function login($data) {
        return $this->_objectManager->get('Simi\Simiconnector\Helper\Customer')->loginByEmailAndPass($data['params']['email'], $data['params']['password']);
    }

    public function logout($data) {
        $lastCustomerId = $this->_getSession()->getId();
        $this->_getSession()->logout()->setBeforeAuthUrl($this->_objectManager->get('Magento\Framework\UrlInterface')->getUrl())
            ->setLastCustomerId($lastCustomerId);
        if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
            $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
            $metadata->setPath('/');
            $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
        }
        return true;
    }

    public function register($data) {
        $data = $data['contents'];
        $message = array();
        $checkCustomer = $this->getCustomerByEmail($data->email);
        if ($checkCustomer->getId()) {
            throw new \Exception(__('Account is already exist'), 4);
        }
        $customer = $this->_createCustomer($data);
        $result = array();
        $result['user_id'] = $customer->getId();
        $session = $this->_getSession();
        if ($customer->isConfirmationRequired()) {
            $store = $this->_storeManager->getStore();
            $customer->sendNewAccountEmail(
                    'registered', $session->getBeforeAuthUrl(), $store->getId()
            );
            throw new \Exception(__('Account confirmation is required. Please, check your email.'), 4);
        }
        return $customer;
    }

    public function updateProfile($data) {
        $data = $data['contents'];
        $result = array();
        $currPass = $data->old_password;
        $newPass = $data->new_password;
        $confPass = $data->com_password;

        $customer = $this->_objectManager->create('Magento\Customer\Model\Customer');
        $customer->setWebsiteId($this->_storeManager->getStore()->getWebsiteId());
        $customer->loadByEmail($data->email);

        $customerData = array(
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'email' => $data->email,
        );

        if ($data->change_password == 1) {
            $customer->setChangePassword(1);
            $oldPass = $this->_getSession()->getCustomer()->getPasswordHash();
            if ($newPass != $confPass) {
                throw new InputException(__('Password confirmation doesn\'t match entered password.'));
            }
            $customer->setPassword($newPass);
            $customer->setConfirmation($confPass);
            $customer->setPasswordConfirmation($confPass);
        }
        $customerErrors = $customer->validate();
		
	if (isset($data->taxvat)) {
            $customer->setTaxvat($data->taxvat);
        }

        if (isset($data->day) && $data->day != "") {
            $birthday = $data->year . "-" . $data->month . "-" . $data->day;
            $customer->setDob($birthday);
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
        
        
        $customerForm = $this->_objectManager->get('Magento\Customer\Model\Form');
        $customerForm->setFormCode('customer_account_edit')
                ->setEntity($customer);
        $customerErrors = $customerForm->validateData($customer->getData());
        if ($customerErrors !== true) {
            if (is_array($customerErrors))
                throw new \Exception($customerErrors[0], 4);
            else
                throw new \Exception($customerErrors, 4);
        } else {
            $customerForm->compactData($customerData);
        }
		
        
        if (is_array($customerErrors))
            throw new \Exception(__('Invalid profile information'), 4);
        $customer->setConfirmation(null);
        $customer->save();
        $this->_getSession()->setCustomer($customer);
        return $customer;
    }
    

    /*
     * Social Login
     * @param 
     * $data - Object with at least:
     * $data->firstname
     * $data->lastname
     * $data->email
     */

    public function socialLogin($data) {
        $data = (object) $data['params'];
        if (!$data->email)
            throw new \Exception(__('Cannot Get Your Email'), 4);
        $customer = $this->_objectManager->get('Simi\Simiconnector\Helper\Customer')->getCustomerByEmail($data->email);
        if (!$customer->getId()) {
            if (!$data->firstname)
                $data->firstname = __('Firstname');
            if (!$data->lastname)
                $data->lastname = __('Lastname');
            $customer = $this->_createCustomer($data);
            $customer->sendPasswordReminderEmail();
        }
        $this->_objectManager->get('Simi\Simiconnector\Helper\Customer')->loginByCustomer($customer);
        return $customer;
    }

    /*
     * Create Customer
     * @param 
     * $data - Object with at least:
     * $data->firstname
     * $data->lastname
     * $data->email
     * $data->password
     */

    private function _createCustomer($data) {
        $customer = $this->_objectManager->create('Magento\Customer\Model\Customer')
                ->setFirstname($data->firstname)
                ->setLastname($data->lastname)
                ->setEmail($data->email);
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
            $data->password = 'simipassword'.rand(pow(10, 9),pow(10, 10)).substr(md5(microtime()),rand(0,26),5);
        }
        $customer->setPassword($data->password);
        $customer->save();
        return $customer;
    }

    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = $this->_objectManager->get(PhpCookieManager::class);
        }
        return $this->cookieMetadataManager;
    }
    
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = $this->_objectManager->get(CookieMetadataFactory::class);
        }
        return $this->cookieMetadataFactory;
    }
}

