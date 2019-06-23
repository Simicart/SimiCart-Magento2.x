<?php
namespace Simi\Simiconnector\Api;
use Magento\Authorization\Model\UserContextInterface;

class Simiconnectorapi implements \Simi\Simiconnector\Api\SimiconnectorapiInterface
{
    protected $request;
    protected $eventManager;
    public $simiObjectManager;
    private $authorization;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Webapi\Model\Authorization\TokenUserContext $authorization
    )
    {
        $this->simiObjectManager = $simiObjectManager;
        $this->eventManager = $eventManager;
        $this->authorization = $authorization;
        return $this;
    }

    private function authorization()
    {
        try {
            $userId = $this->authorization->getUserId();
            $userType = $this->authorization->getUserType();
            if ($userId && $userType == UserContextInterface::USER_TYPE_CUSTOMER) {
                $storeManager = $this->simiObjectManager
                    ->get('\Magento\Store\Model\StoreManagerInterface');
                $customer = $this->simiObjectManager->get('Magento\Customer\Model\Customer')
                    ->setWebsiteId($storeManager->getStore()->getWebsiteId())
                    ->load($userId);
                if ($customer && $customer->getId()) {
                    $this->simiObjectManager
                        ->get('Magento\Customer\Model\Session')
                        ->setCustomerAsLoggedIn($customer);
                }
            }
        } catch (\Exception $e) {

        }
    }

    private function _getServer()
    {
        $this->authorization();
        $context = $this->simiObjectManager->create('Simi\Simiconnector\Controller\Rest\V2');
        $serverModel               = $this->simiObjectManager->get('Simi\Simiconnector\Model\Server');
        $serverModel->eventManager = $this->eventManager;
        $serverModel->init($context);
        return $serverModel;
    }
    private function _changeData(&$data, $resource, $resource_id) {
        $data['resource'] = $resource;
        $data['resourceid'] = $resource_id;
        $data['nestedresource'] = $data['nestedid'] = null;
        $data['module'] = $data['module']?$data['module']:'Simiconnector';
    }

    public function hasId($resource, $resource_id)
    {
        $server = $this->_getServer();
        $data = $server->getData();
        $this->_changeData($data, $resource, $resource_id);
        $server->setData($data);
        $this->eventManager->dispatch(
            'simi_simiconnector_model_server_initialize',
            ['object' => $server, 'data' => $data]
        );
        $result = $server->run();
        return array(
            'data' => $result
        );
    }


    public function noId($resource)
    {
        $server = $this->_getServer();
        $data = $server->getData();
        $this->_changeData($data, $resource, null);
        $server->setData($data);
        $this->eventManager->dispatch(
            'simi_simiconnector_model_server_initialize',
            ['object' => $server, 'data' => $data]
        );
        $result = $server->run();
        return array(
            'data' => $result
        );
    }
}
