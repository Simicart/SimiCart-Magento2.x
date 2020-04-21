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
        $this->simiObjectManager->get('\Magento\Framework\Registry')->register('simi_magento_rest', true);
        $this->eventManager = $eventManager;
        $this->authorization = $authorization;
        return $this;
    }

    private function _getServer()
    {
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

    private function _getData($server) {
        try {
            $results = $server->run();
        } catch (\Exception $e) {
            $results = [];
            $result  = [];
            if (is_array($e->getMessage())) {
                $messages = $e->getMessage();
                foreach ($messages as $message) {
                    $result[] = [
                        'code'    => $e->getCode(),
                        'message' => $message,
                    ];
                }
            } else {
                $result[] = [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ];
            }
            $results['errors'] = $result;
        }
        return $results;
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
        $result = $this->_getData($server);
        return array(
            'data' => json_decode(json_encode($result), true)
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
        $result = $this->_getData($server);
        return array(
            'data' => $result
        );
    }
}
