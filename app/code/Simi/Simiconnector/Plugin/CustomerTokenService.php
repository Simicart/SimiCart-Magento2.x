<?php
namespace Simi\Simiconnector\Plugin;

class CustomerTokenService
{
    private $simiObjectManager;
    private $request;
    private $authorization;
    private $sendEmail;


    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Webapi\Model\Authorization\TokenUserContext $authorization,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->request = $request;
        $this->authorization = $authorization;
    }

    public function beforeCreateCustomerAccessToken($customerTokenService, $username, $password)
    {
        $this->sendEmail = $username;
    }

    //add SessionId to login api
    public function afterCreateCustomerAccessToken($customerTokenService, $result)
    {
        if ($this->request->getParam('getSessionId') && $this->sendEmail) {
            $storeManager = $this->simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $requestCustomer = $this->simiObjectManager->get('Magento\Customer\Model\Customer')
                ->setWebsiteId($storeManager->getStore()->getWebsiteId())
                ->loadByEmail($this->sendEmail);
            $tokenCustomerId = $this->simiObjectManager->create('Magento\Integration\Model\Oauth\Token')
                ->loadByToken($result)->getData('customer_id');
            if ($requestCustomer && $requestCustomer->getId() == $tokenCustomerId) {
                $this->simiObjectManager
                    ->get('Magento\Customer\Model\Session')
                    ->setCustomerAsLoggedIn($requestCustomer);
                return array(
                    'mod_values' => array(
                        'customer_access_token' => $result,
                        'customer_identity' => $this->simiObjectManager
                            ->get('Magento\Customer\Model\Session')
                            ->getSessionId(),
                    )
                );
            }
        }
        return $result;
    }
}