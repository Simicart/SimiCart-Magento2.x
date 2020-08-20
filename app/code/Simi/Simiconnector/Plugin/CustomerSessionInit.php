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
        	$objectManager->create('Magento\Framework\Session\SessionManager')->setSessionId($simiSessId);
        }
    }
}