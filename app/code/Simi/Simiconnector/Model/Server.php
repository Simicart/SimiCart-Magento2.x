<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model;

class Server
{

    public $helper;
    public $data    = [];
    public $method = 'callApi';
    public $eventManager;
    public $simiObjectManager;
    public $coreRegistry;
    public $zendRequest;
    public $result = [];

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Registry $registry
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->coreRegistry     = $registry;
    }

    public function init(
        \Simi\Simiconnector\Controller\Rest\Action $controller
    ) {
        $this->initialize($controller);
        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function _getCheckoutSession()
    {
        return $this->simiObjectManager->create('Magento\Checkout\Model\Session');
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
        $this->helper = $this->simiObjectManager->get('\Simi\Simiconnector\Helper\Data');
        $data          = $this->data;
        if (count($data) == 0) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Invalid method.'), 4);
        }

        if (!isset($data['resource'])) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Invalid method.'), 4);
        }

        if(!$this->_getCheckoutSession()->getData('simiconnector_platform')) {
            $this->_getCheckoutSession()->setData('simiconnector_platform', 'native');
        }

        if ((strpos($data['resource'], 'migrate')) !== false) {
            $migrateResource = explode('_', $data['resource'])[1];
            $className = 'Simi\\' . ucfirst($data['module']) . '\Model\Api\Migrate\\' . ucfirst($migrateResource);
        } else {
            $className = 'Simi\\' . ucfirst($data['module']) . '\Model\Api\\' . ucfirst($data['resource']);
        }
        if (!class_exists($className)) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('Invalid method.'), 4);
        }

        $model = $this->simiObjectManager->get($className);

        if (is_callable([&$model, $this->method])) {
            //Avoid using direct function, need to change solution when found better one
            $callFunctionName = 'call_user_func_array';
            $this->result = $callFunctionName([&$model, $this->method], [$data]);
            $this->eventManager->dispatch(
                'simi_simiconnector_model_server_return_' . $data['resource'],
                ['object' => $this, 'data' => $this->data]
            );
            return $this->result;
        }
        throw new \Simi\Simiconnector\Helper\SimiException(__('Resource cannot callable.'), 4);
    }

    /**
     * @param \Simi\Simiconnector\Controller\Rest\Action $controller
     * $is_method = 1 - get
     * $is_method = 2 - post
     * $is_method = 3 - update
     * $is_method = 4 - delete
     */
    public function initialize(\Simi\Simiconnector\Controller\Rest\Action $controller)
    {
        $request_string   = $controller->getRequest()->getRequestString();
        $action_string    = $controller->getRequest()->getActionName() . '/';
        $cache            = explode($action_string, $request_string);
        $resources_string = $cache[1];
        $resources        = explode('/', $resources_string);
        $resource       = isset($resources[0]) ? $resources[0] : null;
        if ($this->simiObjectManager
            ->get('Simi\Simiconnector\Helper\Data')->countArray($newResources = explode('?', $resource)) > 0) {
            $resource = $newResources[0];
        }
        $resourceid     = isset($resources[1]) ? $resources[1] : null;
        if ($this->simiObjectManager
            ->get('Simi\Simiconnector\Helper\Data')
            ->countArray($newResourceIds = explode('?', $resourceid)) > 0) {
            $resourceid = $newResourceIds[0];
        }
        $nestedresource = isset($resources[2]) ? $resources[2] : null;
        $nestedid       = isset($resources[3]) ? $resources[3] : null;

        $module              = $controller->getRequest()->getModuleName();
        $params              = $controller->getRequest()->getQuery();
        /*
         *
         * Use the function below for lower than 2.2 version of Magento
         * if the using script doesn't work
         *
        $this->zendRequest = $this->simiObjectManager
            ->get('\Magento\Framework\Profiler\Driver\Standard\Output\Firebug')
            ->getRequest();
         *
         *
         *
         *
        */
        $contents            = $controller->getRequest()->getContent();
        $contents_array      = [];
        if ($contents && ($contents != '')) {
            $contents_paser = urldecode($contents);
            $contents       = json_decode($contents_paser);
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
        $this->data = [
            'resource'       => $resource,
            'resourceid'     => $resourceid,
            'nestedresource' => $nestedresource,
            'nestedid'       => $nestedid,
            'params'         => $params,
            'contents'       => $contents,
            'contents_array' => $contents_array,
            'is_method'      => $is_method,
            'module'         => $module,
            'controller'     => $controller,
        ];
        $this->coreRegistry->register('simidata', $this->data);
        $this->eventManager->dispatch(
            'simi_simiconnector_model_server_initialize',
            ['object' => $this, 'data' => $this->data]
        );
    }
}
