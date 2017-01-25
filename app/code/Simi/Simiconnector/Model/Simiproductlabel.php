<?php

namespace Simi\Simiconnector\Model;

/**
 * Connector Model
 *
 * @method \Simi\Simiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simiconnector\Model\Resource\Page getResource()
 */
class Simiproductlabel extends \Magento\Framework\Model\AbstractModel {

    /**
     * @var \Simi\Simiconnector\Helper\Website
     * */
    protected $_websiteHelper;
    protected $_objectManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceModel\Key $resource
     * @param ResourceModel\Key\Collection $resourceCollection
     * @param \Simi\Simiconnector\Helper\Website $websiteHelper
     * @param AppFactory $app
     * @param PluginFactory $plugin
     * @param DesignFactory $design
     * @param ResourceModel\App\CollectionFactory $appCollection
     * @param ResourceModel\Key\CollectionFactory $keyCollection
     */
    public function __construct(
    \Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Simi\Simiconnector\Model\ResourceModel\Simiproductlabel $resource, \Simi\Simiconnector\Model\ResourceModel\Simiproductlabel\Collection $resourceCollection, \Simi\Simiconnector\Helper\Website $websiteHelper
    ) {

        $this->_websiteHelper = $websiteHelper;

        parent::__construct(
                $context, $registry, $resource, $resourceCollection
        );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct() {
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Simiproductlabel');
    }

    /**
     * @return array Status
     */
    public function toOptionStatusHash() {
        $status = array(
            '1' => __('Enable'),
            '2' => __('Disabled'),
        );
        return $status;
    }

}
