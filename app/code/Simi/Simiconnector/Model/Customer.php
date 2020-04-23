<?php

namespace Simi\Simiconnector\Model;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;

/**
 * Simiconnector Model
 *
 * @method \Simi\Simiconnector\Model\ResourceModel\Page _getResource()
 * @method \Simi\Simiconnector\Model\ResourceModel\Page getResource()
 */
class Customer extends \Magento\Framework\Model\AbstractModel
{

    public $simiObjectManager;
    public $storeManager;
    public $cookieMetadataFactory;
    public $cookieMetadataManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        $this->simiObjectManager = $simiObjectManager;
        $this->storeManager     = $this->simiObjectManager->get('Magento\Store\Model\StoreManagerInterface');
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function reindexCustomerGrid()
    {
        $indexerFactory = $this->simiObjectManager->get('Magento\Indexer\Model\IndexerFactory');
        $indexerIds = array(
            'customer_grid',
        );
        foreach ($indexerIds as $indexerId) {
            $indexer = $indexerFactory->create();
            $indexer->load($indexerId);
            $indexer->reindexAll();
        }
    }

    public function _helperCustomer()
    {
        return $this->simiObjectManager->get('Simi\Simiconnector\Helper\Customer');
    }

    public function _getSession()
    {
        return $this->simiObjectManager->get('Magento\Customer\Model\Session');
    }

    public function getCustomerByEmail($email)
    {
        return $this->_helperCustomer()->getCustomerByEmail($email);
    }

    public function getAccountManagement()
    {
        return $this->simiObjectManager->get('Magento\Customer\Api\AccountManagementInterface');
    }

    public function forgetPassword($data)
    {
        $data  = $data['params'];
        $email = $data['email'];
        if ($email === null) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('No email was sent'), 4);
        } else {
            if (!\Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                throw new \Simi\Simiconnector\Helper\SimiException(__('Please correct the email address.'), 4);
            }
            $customer = $this->_helperCustomer()->getCustomerByEmail($email);
            if ($customer->getId()) {
                $this->getAccountManagement()->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
            } else {
                throw new \Simi\Simiconnector\Helper\SimiException(__('Customer is not exist'));
            }
        }
    }

    public function login($data)
    {
        if (isset($data['params']['simi_hash'])) {
            return $this->simiObjectManager->get('Simi\Simiconnector\Helper\Customer')
                ->loginByEmailAndPass($data['params']['email'], $data['params']['simi_hash']);
        } else {
            return $this->simiObjectManager->get('Simi\Simiconnector\Helper\Customer')
                ->loginByEmailAndPass($data['params']['email'], $data['params']['password']);
        }
    }

    public function logout()
    {
        $lastCustomerId = $this->_getSession()->getId();
        $this->_getSession()->logout()->setBeforeAuthUrl($this->simiObjectManager
            ->get('Magento\Framework\UrlInterface')->getUrl())
            ->setLastCustomerId($lastCustomerId);
        if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
            $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
            $metadata->setPath('/');
            $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
        }
        return true;
    }

    public function register($data)
    {
        $data          = $data['contents'];
        $message       = [];
        $checkCustomer = $this->getCustomerByEmail($data->email);
        if ($checkCustomer->getId()) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Account is already exist'), 4);
        }
        $customer = $this->_createCustomer($data);
        try {
            $this->reindexCustomerGrid();
        } catch (\Exception $e) {
        }
        $confirmationStatus = $this->getAccountManagement()->getConfirmationStatus($customer->getId());
        if ($confirmationStatus === \Magento\Customer\Api\AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Account confirmation is required. '
                . 'Please, check your email.'), 4);
        }
        return $customer;
    }

    public function updateProfile($data)
    {
        $data     = $data['contents'];
        $result   = [];

        $customer = $this->simiObjectManager->create('Magento\Customer\Model\Customer');
        $customer->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        $customer->loadByEmail($data->email);

        $customerData = [
            'firstname' => $data->firstname,
            'lastname'  => $data->lastname,
            'email'     => $data->email,
        ];

	    try {
		    // Fix bug 'invalid state change requested' when change password.
		    // The reason is quote has customer_id is null
		    $cart = $this->simiObjectManager->get( 'Magento\Checkout\Model\Cart' );
		    $cart->getQuote()->setData( 'customer_id', $customer->getId() );
		    $cart->saveQuote();
	    }
	    catch ( \Exception $e ) {
		    throw new \Exception( __( 'Set customer to quote error!' ), 4 );
	    }

        if (isset($data->change_password) && $data->change_password == 1) {
	        $this->validateOldPassword($data);
            $currPass = $data->old_password;
            $newPass  = $data->new_password;
            $confPass = $data->com_password;
            $customer->setChangePassword(1);
            if ($customer->authenticate($data->email, $currPass)) {
                if ($newPass != $confPass) {
                    throw new \Magento\Framework\Exception\InputException(
                        __('Password confirmation doesn\'t match entered password.')
                    );
                }
                $customer->setPassword($newPass);
                $customer->setConfirmation($confPass);
                $customer->setPasswordConfirmation($confPass);
            }
        }
        $this->setCustomerData($customer, $data);
        $customerForm   = $this->simiObjectManager->get('Magento\Customer\Model\Form');
        $customerForm->setFormCode('customer_account_edit')
            ->setEntity($customer);
        $customerErrors = $customerForm->validateData($customer->getData());
        if ($customerErrors !== true) {
            if (is_array($customerErrors)) {
                throw new \Simi\Simiconnector\Helper\SimiException($customerErrors[0], 4);
            } else {
                throw new \Simi\Simiconnector\Helper\SimiException($customerErrors, 4);
            }
        } else {
            $customerForm->compactData($customerData);
        }

        if (is_array($customerErrors)) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Invalid profile information'), 4);
        }

	    if (isset($data->change_email) && $data->change_email == 1 && isset($data->new_email)) {
		    $this->validateOldPassword($data);
		    if (!filter_var($data->new_email, FILTER_VALIDATE_EMAIL)) {
			    throw new \Exception(__('New email is not valid'), 4);
		    }
		    $customer->setEmail($data->new_email);
	    }

        $customer->setConfirmation(null);
        $customer->save();
        $this->_getSession()->setCustomer($customer);
        return $customer;
    }

	private function validateOldPassword($data) {
		if (isset($data->old_password)) {
			$websiteId = $this->storeManager->getStore()->getWebsiteId();
			$customerAuth  = $this->simiObjectManager->get('Magento\Customer\Model\Customer')
			                                         ->setWebsiteId($websiteId);
			try {
				$customerAuth->authenticate($data->email, $data->old_password);
			} catch (\Exception $e) {
				throw new \Exception(__('Please check your current email and password'), 4);
			}
		} else {
			throw new \Exception(__('Please fill your current password'), 4);
		}
	}

    private function setCustomerData($customer, $data)
    {
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
    }

    /*
     * Social Login
     * @param
     * $data - Object with at least:
     * $data->firstname
     * $data->lastname
     * $data->email
     */

    public function socialLogin($data)
    {
        $data = (object) $data['params'];
        if (!isset($data->password) || !$this->simiObjectManager
            ->get('Simi\Simiconnector\Helper\Customer')->validateSimiPass($data->email, $data->password, 'social_login')) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Password is not Valid'), 4);
        }
        if (!$data->email) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Cannot Get Your Email'), 4);
        }
        $customer = $this->simiObjectManager
            ->get('Simi\Simiconnector\Helper\Customer')->getCustomerByEmail($data->email);
        if (!$customer->getId()) {
            if (!$data->firstname) {
                $data->firstname = __('Firstname');
            }
            if (!$data->lastname) {
                $data->lastname = __('Lastname');
            }
            $customer = $this->_createCustomer($data);
        }
        $this->simiObjectManager->get('Simi\Simiconnector\Helper\Customer')->loginByCustomer($customer);
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

    public function _createCustomer($data)
    {
        $customer = $this->simiObjectManager->create('Magento\Customer\Api\Data\CustomerInterface')
            ->setFirstname($data->firstname)
            ->setLastname($data->lastname)
            ->setEmail($data->email);
        $this->simiObjectManager->get('Simi\Simiconnector\Helper\Customer')->applyDataToCustomer($customer, $data);

        $password = null;
        if (isset($data->password) && $data->password) {
            $password = $data->password;
        }
        $customer = $this->getAccountManagement()->createAccount($customer, $password, '');
        $subscriberFactory = $this->simiObjectManager->get('Magento\Newsletter\Model\SubscriberFactory');
        if (isset($data->news_letter) && ($data->news_letter == '1')) {
            $subscriberFactory->create()->subscribeCustomerById($customer->getId());
        } else {
            $subscriberFactory->create()->unsubscribeCustomerById($customer->getId());
        }
        $customer = $this->simiObjectManager->create('Magento\Customer\Model\Customer')->load($customer->getId());
        return $customer;
    }

    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = $this->simiObjectManager->get(PhpCookieManager::class);
        }
        return $this->cookieMetadataManager;
    }

    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = $this->simiObjectManager->get(CookieMetadataFactory::class);
        }
        return $this->cookieMetadataFactory;
    }
}
