<?php


namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;

class ControllerSendResponseBefore implements ObserverInterface
{
    //modify system rest api data
    private $simiObjectManager;
    private $contentArray;
    private $inputParamsResolver;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Webapi\Controller\Rest\InputParamsResolver $inputParamsResolver
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->inputParamsResolver = $inputParamsResolver;
    }

    public function setContentArray($contentArray) {
        $this->contentArray = $contentArray;
    }

    public function getContentArray() {
        return $this->contentArray;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->isVersion(array('2.0', '2.1')))
                return;
            $state = $this->simiObjectManager->get('Magento\Framework\App\State');
            if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_WEBAPI_REST) {
                $response = $observer->getResponse();
                $content = $response->getContent();
                $request = $observer->getRequest();
                if ($jsonContent = json_decode($content, true)) {
                    $router = $this->simiObjectManager->get('Magento\Webapi\Controller\Rest\Router');
                    $apiRequest = $this->simiObjectManager->get('\Magento\Framework\Webapi\Rest\Request');
                    $route = $router->match($apiRequest);


                    //set customer and quote to session cause rest does not
                    $cartId = false;
                    $customerContext = $this->simiObjectManager->create('Magento\Authorization\Model\UserContextInterface');
                    $customerId = $customerContext->getUserId();
                    if ($customerId &&
                        $customerModel = $this->simiObjectManager->get('Magento\Customer\Model\Customer')->load($customerId)) {
                        $this->simiObjectManager
                            ->get('Magento\Customer\Model\Session')
                            ->setCustomerAsLoggedIn($customerModel);
                        $inputData = $this->simiObjectManager->create('Magento\Webapi\Controller\Rest\ParamsOverrider')
                            ->override(array(), array('cartId'=>array('force' => true, 'value' => '%cart_id%')));
                        if ($inputData && isset($inputData['cartId'])) {
                            $cartId = $inputData['cartId'];
                        }
                    }
                    if (!$cartId) {
                        $inputParams = $this->inputParamsResolver->resolve();
                        if ($inputParams && is_array($inputParams) && isset($inputParams[0])) {
                            $quoteId = $inputParams[0];
                            $quoteIdMask = $this->simiObjectManager->get('Magento\Quote\Model\QuoteIdMask');
                            if ($quoteIdMask->load($quoteId, 'masked_id')) {
                                if ($quoteIdMask && $maskQuoteId = $quoteIdMask->getData('quote_id'))
                                    $cartId = $maskQuoteId;
                            }
                        }
                    }
                    if ($cartId) {
                        $quoteModel = $this->simiObjectManager->get('Magento\Quote\Model\Quote')->load($cartId);
                        if ($quoteModel->getId() && $quoteModel->getData('is_active')) {
                            $this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->setQuoteToSession($quoteModel);
                        }
                    }

                    $routeData = array(
                        'serviceClass' => $route->getServiceClass(),
                        'serviceMethod' => $route->getServiceMethod(),
                        'aclResources' => $route->getAclResources(),
                        'parameters' => $route->getParameters(),
                        'routePath' => $route->getRoutePath()
                    );
                    $this->setContentArray($jsonContent);
                    $requestContent = array();
                    if($requestRaw = $request->getContent()) {
                        $requestContent = json_decode($requestRaw, 1);
                    }
                    $this->simiObjectManager->get('\Magento\Framework\Event\ManagerInterface')
                        ->dispatch(
                            'simiconnector_system_rest_modify',
                            array(
                                'object' => $this,
                                'routeData' => $routeData,
                                'requestContent' => $requestContent,
                                'request' => $request,
                            )
                        );
                    $response->setContent(json_encode($this->getContentArray()));
                }
            }
        } catch (\Exception $e) {

        }
    }
}
