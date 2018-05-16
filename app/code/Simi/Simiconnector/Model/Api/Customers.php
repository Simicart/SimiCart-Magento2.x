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
                            ->get('Magento\Customer\Model\Session')->getCustomer();
                    $this->RETURN_MESSAGE = $message = __(
                        'If there is an account associated with %1 you will '
                            . 'receive an email with a link to reset your password.',
                        $email
                    );
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
                    } else {
                        throw new \Simi\Simiconnector\Helper\SimiException(__('Logout Failed'), 4);
                    }
                    break;
                case 'checkexisting':
                    $this->builderQuery = $this->simiObjectManager->get('Simi\Simiconnector\Model\Customer')
                        ->getCustomerByEmail($data['params']['customer_email']);
                    break;
                default:
                    $this->builderQuery = $this->simiObjectManager->get('Magento\Customer\Model\Customer')
                        ->setWebsiteId($this->storeManager->getStore()->getWebsiteId())->load($data['resourceid']);
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
        if ($this->RETURN_MESSAGE) {
            $resultArray            = parent::getDetail($info);
            $resultArray['message'] = [$this->RETURN_MESSAGE];
            return $resultArray;
        }
        return parent::getDetail($info);
    }

    /*
     * Get Wishlist count
     */

    public function getWishlistCount()
    {
        $customer = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getCustomer();
        if ($customer && $customer->getId()) {
            return (int)$this->simiObjectManager
                ->get('Magento\Wishlist\Model\Wishlist')->loadByCustomerId($customer->getId(), true)
                ->getItemCollection()->getSize();
        }
        return 0;
    }
}
