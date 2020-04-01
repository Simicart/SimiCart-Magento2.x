<?php

/**
 * Copyright © Simicart, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Simi\Simiconnector\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

/**
 * Class CreatePassword
 *
 * @package Simi\Simicustomize\Controller\Account
 */
class CreatePassword extends \Magento\Customer\Controller\AbstractAccount implements HttpGetActionInterface
{
    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param AccountManagementInterface $accountManagement
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement
    ) {
        $this->session = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->accountManagement = $accountManagement;
        parent::__construct($context);
    }

    /**
     * Resetting password handler
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $link = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('simiconnector/general/pwa_studio_url');
        $resetPasswordToken = (string) $this->getRequest()->getParam('token');
        $isDirectLink = $resetPasswordToken != '';
        if (!$isDirectLink) {
            $resetPasswordToken = (string) $this->session->getRpToken();
        }

        // Check rp_token in database exist or not, if not -> redirect to home pwa
        $customerModel = $objectManager->create('\Magento\Customer\Model\Customer');
        $customerSearch = $customerModel->getCollection()->addFieldToFilter("rp_token", $resetPasswordToken);
        if (count($customerSearch) == 0) {
            if (isset($link)) {
                header("Location: {$link}");
                exit;
            }
        }else{
            $customerData = $customerSearch->getData();
            $customerEmail = $customerData[0]['email'];
        }

        try {
            $this->accountManagement->validateResetPasswordLinkToken(null, $resetPasswordToken);

            if ($isDirectLink) {
                $this->session->setRpToken($resetPasswordToken);
                if (isset($link)) {
                    header("Location: {$link}resetPassword.html?token={$resetPasswordToken}&mail={$customerEmail}");
                    exit;
                }
            }
        } catch (\Exception $exception) {

        }
    }
}