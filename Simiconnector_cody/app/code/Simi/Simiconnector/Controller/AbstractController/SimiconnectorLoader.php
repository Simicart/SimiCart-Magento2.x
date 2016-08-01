<?php

namespace Simi\Simisimiconnector\Controller\AbstractController;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Registry;

class SimisimiconnectorLoader implements SimisimiconnectorLoaderInterface
{
    /**
     * @var \Simi\Simisimiconnector\Model\SimisimiconnectorFactory
     */
    protected $simiconnectorFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @param \Simi\Simisimiconnector\Model\SimisimiconnectorFactory $simiconnectorFactory
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param Registry $registry
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \Simi\Simisimiconnector\Model\SimisimiconnectorFactory $simiconnectorFactory,
        Registry $registry,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->simiconnectorFactory = $simiconnectorFactory;
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

        $simiconnector = $this->simiconnectorFactory->create()->load($id);
        $this->registry->register('current_simiconnector', $simiconnector);
        return true;
    }
}
