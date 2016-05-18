<?php

namespace MobileApp\Connector\Model;

/**
 * Connector Model
 *
 * @method \MobileApp\Connector\Model\Resource\Page _getResource()
 * @method \MobileApp\Connector\Model\Resource\Page getResource()
 */
class Device extends \Magento\Framework\Model\AbstractModel
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
        \MobileApp\Connector\Model\ResourceModel\Device $resource,
        \MobileApp\Connector\Model\ResourceModel\Device\Collection $resourceCollection,
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
        $this->_init('MobileApp\Connector\Model\ResourceModel\Device');
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

    /**
     * @return array Website
     */
    public function toOptionCountryHash(){
        $country_collection = $this->_websiteHelper->getCountryCollection();
        $list = array();
        if(sizeof($country_collection) > 0){
            foreach($country_collection as $country){
                $list[$country->getId()] = $country->getName();
            }
        }
        return $list;
    }

    /**
     * @return array Devices
     */
    public function toOptionDeviceHash(){
        $devices = array(
            '1' => __('iPhone'),
            '2' => __('iPad'),
            '3' => __('Android'),
        );
        return $devices;
    }

    /**
     * @return array Devices
     */
    public function toOptionDemoHash(){
        $demos = array(
            '0' => __('NO'),
            '1' => __('YES'),
            '3' => __('N/A'),
        );
        return $demos;
    }

}
