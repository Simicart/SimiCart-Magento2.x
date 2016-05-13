<?php

namespace MobileApp\Connector\Model;

/**
 * Connector Model
 *
 * @method \MobileApp\Connector\Model\Resource\Page _getResource()
 * @method \MobileApp\Connector\Model\Resource\Page getResource()
 */
class Banner extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \MobileApp\Connector\Helper\Website
     **/
    protected $_websiteHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceModel\Key $resource
     * @param ResourceModel\Key\Collection $resourceCollection
     * @param \MobileApp\Connector\Helper\Website $websiteHelper
     * @param AppFactory $app
     * @param PluginFactory $plugin
     * @param DesignFactory $design
     * @param ResourceModel\App\CollectionFactory $appCollection
     * @param ResourceModel\Key\CollectionFactory $keyCollection
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \MobileApp\Connector\Model\ResourceModel\Banner $resource,
        \MobileApp\Connector\Model\ResourceModel\Banner\Collection $resourceCollection,
        \MobileApp\Connector\Helper\Website $websiteHelper
    )
    {

        $this->_websiteHelper = $websiteHelper;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection
        );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_init('MobileApp\Connector\Model\ResourceModel\Banner');
    }

    /**
     * @return array Type
     */
    public function toOptionTypeHash(){
        $platform = array(
            '1' => __('Product In-app'),
            '2' => __('Category In-app'),
            '3' => __('Website Page'),
        );
        return $platform;
    }

    /**
     * @return array Status
     */
    public function toOptionStatusHash(){
        $status = array(
            '1' => __('Enable'),
            '2' => __('Disabled'),
        );
        return $status;
    }

    /**
     * @return array Website
     */
    public function toOptionWebsiteHash(){
        $website_collection = $this->_websiteHelper->getWebsiteCollection();
        $list = array();
        $list[0] = __('All');
        if(sizeof($website_collection) > 0){
            foreach($website_collection as $website){
                $list[$website->getId()] = $website->getName();
            }
        }
        return $list;
    }

}
