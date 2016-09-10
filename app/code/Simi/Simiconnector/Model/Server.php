<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model;

class Server
{
    protected $_helper;
    protected $_data = array();
    protected $_method = 'callApi';
    public $eventManager;
    public $objectManager;
    public $_coreRegistry;
    
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
	$this->_coreRegistry = $registry;
    }
    
    public function init(
            \Magento\Framework\App\Action\Action $controller)
    {   
        $this->initialize($controller);
        return $this;
    }

    public function setData($data)
    {
        $this->_data = $data;
    }

    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return mixed|string
     * @throws Exception
     * error code
     * 1 Not Login
     * 2 Miss username or password to login
     * 3 Access Denied
     * 4 Invalid method
     * 5 Login failed
     * 6 Resource cannot callable
     * 7 Missed input Value
     */
    public function run()
    {
        $this->_helper = $this->objectManager->get('\Simi\Simiconnector\Helper\Data');
        $data = $this->_data;
        if (count($data) == 0) {
            throw new \Exception(__('Invalid method.'), 4);
        }

        if (!isset($data['resource'])) 
            throw new \Exception(__('Invalid method.'), 4);
        $className = 'Simi\\'.ucfirst($data['module']).'\Model\Api\\'.ucfirst($data['resource']);
        if (!class_exists($className)) {
            throw new \Exception(__('Invalid method.'), 4);
        }
        
        $model = $this->objectManager->get('Simi\\'.$data['module'].'\Model\Api\\'.$data['resource']);

        if (is_callable(array(&$model, $this->_method))) {
            return call_user_func_array(array(&$model, $this->_method), array($data));
        }
        throw new \Exception(__('Resource cannot callable.'), 4);
    }

    /**
     * @param Mage_Api_Controller_Action $controller
     * $is_method = 1 - get
     * $is_method = 2 - post
     * $is_method = 3 - update
     * $is_method = 4 - delete
     */
    public function initialize(\Magento\Framework\App\Action\Action $controller)
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $request_string = $controller->getRequest()->getRequestString();
        $action_string = $controller->getRequest()->getActionName() . '/';
        $cache = explode($action_string, $request_string);
        $resources_string = $cache[1];
        $resources = explode('/', $resources_string);

        $resource = isset($resources[0]) ? $resources[0] : null;
        $resourceid = isset($resources[1]) ? $resources[1] : null;
        $nestedresource = isset($resources[2]) ? $resources[2] : null;
        $nestedid = isset($resources[3]) ? $resources[3] : null;



        $module = $controller->getRequest()->getModuleName();
        $params = $controller->getRequest()->getQuery();
        $zendHTTPRequestHttp = new \Zend_Controller_Request_Http;
        $contents = $zendHTTPRequestHttp->getRawBody();
        $contents_array = array();
        if ($contents && strlen($contents)) {
            $contents_paser = urldecode($contents);
            $contents = json_decode($contents_paser);
            $contents_array = json_decode($contents_paser, true);
        }

        $is_method = 1;
        if ($controller->getRequest()->isPost()) {
            $is_method = 2;
        } elseif ($controller->getRequest()->isPut()) {
            $is_method = 3;
        } elseif ($controller->getRequest()->isDelete()) {
            $is_method = 4;
        }
        $this->_data = array(
            'resource' => $resource,
            'resourceid' => $resourceid,
            'nestedresource' => $nestedresource,
            'nestedid' => $nestedid,
            'params' => $params,
            'contents' => $contents,
            'contents_array' => $contents_array,
            'is_method' => $is_method,
            'module' => $module,
            'controller' => $controller,
        );
        $this->_coreRegistry->register('simidata', $this->_data);
        $this->eventManager->dispatch('Simi_Simiconnector_Model_Server_Initialize', array('object' => $this, 'data' => $this->_data));
    }

}