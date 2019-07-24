<?php


namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\ObserverInterface;

class ControllerSendResponseBefore implements ObserverInterface
{
    //modify system rest api data
    private $simiObjectManager;
    private $contentArray;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager
    ) {

        $this->simiObjectManager = $simiObjectManager;
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
            $productMetadata = $this->simiObjectManager->get('Magento\Framework\App\ProductMetadataInterface');
            if ($productMetadata && $magentoVersion = $productMetadata->getVersion()) {
                if (
                    strpos($magentoVersion, '2.0') === 0 ||
                    strpos($magentoVersion, '2.1') === 0
                ) {
                    return; //filter out 2.0 and 2.1 version
                }
            }
            $state = $this->simiObjectManager->get('Magento\Framework\App\State');
            if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_WEBAPI_REST) {
                $response = $observer->getResponse();
                $content = $response->getContent();
                $request = $observer->getRequest();
                if ($jsonContent = json_decode($content, true)) {
                    $router = $this->simiObjectManager->get('Magento\Webapi\Controller\Rest\Router');
                    $apiRequest = $this->simiObjectManager->get('\Magento\Framework\Webapi\Rest\Request');
                    $route = $router->match($apiRequest);
                    $routeData = array(
                        'serviceClass' => $route->getServiceClass(),
                        'serviceMethod' => $route->getServiceMethod(),
                        'aclResources' => $route->getAclResources(),
                        'parameters' => $route->getParameters(),
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
