<?php
namespace Simi\Simiconnector\Plugin;

class CustomerSessionInit
{
    private $simiObjectManager;
    private $mockupObserver;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Event\Observer $observer,
        SidResolver $sidResolver
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->mockupObserver = $observer;
        $this->sidResolver = $sidResolver;
    }
    //add session id to continue session with graphql
    public function beforeDispatch($subject, $request)
    {
        $objectManager = $this->simiObjectManager;
        $simiObserver = $objectManager->create('Simi\Simiconnector\Observer\CustomerSessionInit')->execute($this->mockupObserver);
        $simiSessId = $this->sidResolver->afterGetSid($objectManager->create('Magento\Framework\Session\SidResolverInterface'), null);
        if ($simiSessId) {
            // Error on 2.4: Warning: session_id(): Cannot change session id when session is active in vendor/magento/framework/Session/SessionManager.php on line 414
        	try {
                $objectManager->get('Magento\Framework\Session\SessionManager')->setSessionId($simiSessId);
            } catch (\Exception $e) {
            }
        }
        
        //set login from bearer
        try {
            $httpAuthInfo = $_SERVER['HTTP_AUTHORIZATION'];
            if ($httpAuthInfo) {
                $bearerInfo = explode(' ', $httpAuthInfo);
                $objectManager = $this->simiObjectManager;
                $apiRequest = $this->simiObjectManager->get('\Magento\Framework\Webapi\Rest\Request');
                if (isset($bearerInfo[0]) && $bearerInfo[0] == 'Bearer' && isset($bearerInfo[1])) {
                    $bearer = $bearerInfo[1];
                    $tokenModel = $objectManager->get('Magento\Integration\Model\Oauth\Token');
                    $tokenModel->loadByToken($bearer);
                    if ($tokenModel->getId()) {
                        $customerId = $tokenModel->getCustomerId();
                        $customer = $objectManager->get('Magento\Customer\Model\Customer')->load($customerId);
                        if ($customer->getId()) {
                            $customerSession = $this->simiObjectManager->get('Magento\Customer\Model\Session');
                            if ($customer->getId() != $customerSession->getCustomerId()) {
                                $customerSession->setCustomerAsLoggedIn($customer);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {

        }
        //end login from bearer
    }
}