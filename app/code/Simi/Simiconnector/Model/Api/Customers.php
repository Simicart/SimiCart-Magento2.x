<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Customers extends Apiabstract
{

    public $DEFAULT_ORDER = 'entity_id';
    public $RETURN_MESSAGE;

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            switch ($data['resourceid']) {
                case 'forgetpassword':
                    $this->simiObjectManager->get('Simi\Simiconnector\Model\Customer')->forgetPassword($data);
                    $email                 = $data['params']['email'];
                    $this->builderQuery    = $this->simiObjectManager
                        ->create('Magento\Customer\Model\Session')->getCustomer();
                    $this->RETURN_MESSAGE = $message = __(
                        'If there is an account associated with %1 you will '
                            . 'receive an email with a link to reset your password.',
                        $email
                    );
                    break;
                case 'createpassword':
                    if (!isset($data['params']['password']))
                        throw new \Simi\Simiconnector\Helper\SimiException(__('Missing new password'), 4);
                    if (!isset($data['params']['rptoken']))
                        throw new \Simi\Simiconnector\Helper\SimiException(__('Missing reset password token'), 4);
                    $newPW = $data['params']['password'];
                    $resetPasswordToken = $data['params']['rptoken'];
                    // Check rp_token in database exist or not
                    $customerData = $this->simiObjectManager->create('\Magento\Customer\Model\Customer');
                    $customerSearch = $customerData->getCollection()->addFieldToFilter("rp_token", $resetPasswordToken);
                    if (count($customerSearch) == 0) {
                        throw new \Simi\Simiconnector\Helper\SimiException(__('Token expired or invalid !'), 4);
                    }
                    // If exist, create new password
                    $this->simiObjectManager
                        ->get('Magento\Customer\Model\Session')
                        ->setRpToken($resetPasswordToken);
                    $this->createPassword($newPW, $resetPasswordToken);
                    $this->simiObjectManager
                        ->get('Magento\Customer\Model\Session')
                        ->setRpToken(null);
                    $this->builderQuery    = $this->simiObjectManager
                        ->get('Magento\Customer\Model\Session')->getCustomer();
                    $this->RETURN_MESSAGE = $message = __('You updated your password.');
                    break;
                case 'profile':
                    $this->builderQuery    = $this->simiObjectManager
                        ->get('Magento\Customer\Model\Session')->getCustomer();
                    $this->builderQuery->setData('wishlist_count', $this->getWishlistCount());
                    break;
                case 'login':
                    if ($this->simiObjectManager->get('Simi\Simiconnector\Model\Customer')->login($data)) {
                        $this->builderQuery = $this->simiObjectManager
                            ->get('Magento\Customer\Model\Session')->getCustomer();
                        $this->builderQuery->setData('wishlist_count', $this->getWishlistCount());
                    } else {
                        throw new \Simi\Simiconnector\Helper\SimiException(__('Login Failed'), 4);
                    }
                    break;
                case 'sociallogin':
                    $this->builderQuery = $this->simiObjectManager->get('Simi\Simiconnector\Model\Customer')
                        ->socialLogin($data);
                    $this->builderQuery->setData('wishlist_count', $this->getWishlistCount());
                    break;
                case 'logout':
                    $lastCustomerId     = $this->simiObjectManager->get('Magento\Customer\Model\Session')
                        ->getCustomer()->getId();
                    if ($this->simiObjectManager->get('Simi\Simiconnector\Model\Customer')->logout()) {
                        $this->builderQuery = $this->simiObjectManager
                            ->get('Magento\Customer\Model\Customer')->load($lastCustomerId);

	                    //fix bug logout not clear old quote
	                    $cart  = $this->simiObjectManager->get( 'Magento\Checkout\Model\Cart' );
	                    $quote = $this->simiObjectManager->create( 'Magento\Quote\Model\Quote' );
	                    $cart->setQuote( $quote );
	                    $newCustomer = $this->simiObjectManager->create( 'Magento\Customer\Model\Customer' );
	                    $this->simiObjectManager->get( 'Magento\Customer\Model\Session' )->setCustomer( $newCustomer );
                    } else {
                        throw new \Simi\Simiconnector\Helper\SimiException(__('Logout Failed'), 4);
                    }
                    break;
                case 'checkexisting':
                    $this->builderQuery = $this->simiObjectManager->get('Simi\Simiconnector\Model\Customer')
                        ->getCustomerByEmail($data['params']['customer_email']);
                    break;
                default:
                    throw new \Simi\Simiconnector\Helper\SimiException(__('Invalid Resource Id'));
                    break;
            }
        } else {
            $currentCustomerId  = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getId();
            $this->builderQuery = $this->simiObjectManager->get('Magento\Customer\Model\Customer')->getCollection()
                ->addFieldToFilter('entity_id', $currentCustomerId);
        }
    }

    /*
     * Register
     */

    public function store()
    {
        $data                  = $this->getData();
        $customer              = $this->simiObjectManager->get('Simi\Simiconnector\Model\Customer')->register($data);
        $this->builderQuery    = $customer;
        $this->RETURN_MESSAGE = __("Thank you for registering with "
            . $this->storeManager->getStore()->getName() . " store");
        return $this->show();
    }

    /*
     * Update Profile
     */

    public function update()
    {
        $data                  = $this->getData();
        $customer              = $this->simiObjectManager
            ->get('Simi\Simiconnector\Model\Customer')->updateProfile($data);
        $this->builderQuery    = $customer;
        $this->RETURN_MESSAGE = __('The account information has been saved.');
        return $this->show();
    }

    /*
     * Add Message
     */

    public function getDetail($info)
    {
        $data = $this->getData();
        $resultArray            = parent::getDetail($info);
        if ($this->RETURN_MESSAGE)
            $resultArray['message'] = [$this->RETURN_MESSAGE];

        if (isset($resultArray['customer']) && isset($resultArray['customer']['email'])) {
            if (
                $this->simiObjectManager->get('\Magento\Newsletter\Model\Subscriber') &&
                $this->simiObjectManager->get('\Magento\Newsletter\Model\Subscriber')
                ->loadByEmail($resultArray['customer']['email'])->isSubscribed()
            ) {
                $resultArray['customer']['news_letter'] = '1';
            } else {
                $resultArray['customer']['news_letter'] = '0';
            }
            $hash = $this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Customer')
                ->getToken($data);
            $resultArray['customer']['simi_hash'] = $hash;
        }
        if (isset($resultArray['customer']['entity_id'])) {
            $customerMap = $this->simiObjectManager->create('Simi\Simiconnector\Model\Customermap')->getCollection()
                ->addFieldToFilter('customer_id', $resultArray['customer']['entity_id'])
                ->getFirstItem();
            if ($customerMap->getId()) {
                $resultArray['customer']['social_login'] = true;
            } else {
                $resultArray['customer']['social_login'] = false;
            }
        }

        return $resultArray;
    }

    /*
     * Get Wishlist count
     */

    public function getWishlistCount()
    {
        $customer = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getCustomer();
        if ($customer && $customer->getId()) {
            return (int) $this->simiObjectManager
                ->get('Magento\Wishlist\Model\Wishlist')->loadByCustomerId($customer->getId(), true)
                ->getItemCollection()->getSize();
        }
        return 0;
    }

    /**
     * Reset password
     * @var string $newpw
     * @var string $resetPasswordToken
     */
    public function createPassword($newpw, $resetPasswordToken)
    {
        $newPassword = (string) $newpw;
        if (iconv_strlen($newPassword) <= 0) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Please enter a new password.'));
        }

        $this->simiObjectManager
            ->get('Magento\Customer\Model\AccountManagement')
            ->resetPassword(
                '',
                $resetPasswordToken,
                $newPassword
            );
        $this->simiObjectManager
            ->create('Magento\Customer\Model\Session')
            ->unsRpToken();
    }
}
