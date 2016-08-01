<?php

namespace Simi\Simisimiconnector\Controller\AbstractController;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;

interface SimisimiconnectorLoaderInterface
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Simi\Simisimiconnector\Model\Simisimiconnector
     */
    public function load(RequestInterface $request, ResponseInterface $response);
}
