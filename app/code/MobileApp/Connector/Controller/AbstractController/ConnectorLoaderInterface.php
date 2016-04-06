<?php

namespace MobileApp\Connector\Controller\AbstractController;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;

interface ConnectorLoaderInterface
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \MobileApp\Connector\Model\Connector
     */
    public function load(RequestInterface $request, ResponseInterface $response);
}
