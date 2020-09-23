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
    }
}