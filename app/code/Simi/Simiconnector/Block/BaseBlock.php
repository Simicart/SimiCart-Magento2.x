<?php

/**
 * Copyright © 2016 Simi . All rights reserved.
 */

namespace Simi\Simiconnector\Block;

use Magento\Framework\UrlFactory;

class BaseBlock extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Simi\Simiconnector\Helper\Data
     */
    public $devToolHelper;

    /**
     * @var \Magento\Framework\Url
     */
    public $urlApp;

    /**
     * @var \Simi\Simiconnector\Model\Config
     */
    public $config;

    /**
     * @param \Simi\Simiconnector\Block\Context $context
     * @param \Magento\Framework\UrlFactory $urlFactory
     */
    public function __construct(\Simi\Simiconnector\Block\Context $context)
    {
        $this->devToolHelper = $context->getSimiconnectorHelper();
        $this->config        = $context->getConfig();
        $this->urlApp        = $context->getUrlFactory()->create();
        parent::__construct($context);
    }

    /**
     * Function for getting event details
     * @return array
     */
    public function getEventDetails()
    {
        return $this->devToolHelper->getEventDetails();
    }

    /**
     * Function for getting current url
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->urlApp->getCurrentUrl();
    }

    /**
     * Function for getting controller url for given router path
     * @param string $routePath
     * @return string
     */
    public function getControllerUrl($routePath)
    {

        return $this->urlApp->getUrl($routePath);
    }

    /**
     * Function for getting current url
     * @param string $path
     * @return string
     */
    public function getConfigValue($path)
    {
        return $this->config->getCurrentStoreConfigValue($path);
    }

    /**
     * Function canShowSimiconnector
     * @return bool
     */
    public function canShowSimiconnector()
    {
        $isEnabled = $this->getConfigValue('simiconnector/module/is_enabled');
        if ($isEnabled) {
            $allowedIps = $this->getConfigValue('simiconnector/module/allowed_ip');
            if ($allowedIps === null) {
                return true;
            }
        }
        return false;
    }
}
