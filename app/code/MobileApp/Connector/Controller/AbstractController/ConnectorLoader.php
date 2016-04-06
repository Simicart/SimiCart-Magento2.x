<?php

namespace MobileApp\Connector\Controller\AbstractController;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Registry;

class ConnectorLoader implements ConnectorLoaderInterface
{
    /**
     * @var \MobileApp\Connector\Model\ConnectorFactory
     */
    protected $connectorFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @param \MobileApp\Connector\Model\ConnectorFactory $connectorFactory
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param Registry $registry
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \MobileApp\Connector\Model\ConnectorFactory $connectorFactory,
        Registry $registry,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->connectorFactory = $connectorFactory;
        $this->registry = $registry;
        $this->url = $url;
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    public function load(RequestInterface $request, ResponseInterface $response)
    {
        $id = (int)$request->getParam('id');
        if (!$id) {
            $request->initForward();
            $request->setActionName('noroute');
            $request->setDispatched(false);
            return false;
        }

        $connector = $this->connectorFactory->create()->load($id);
        $this->registry->register('current_connector', $connector);
        return true;
    }
}
