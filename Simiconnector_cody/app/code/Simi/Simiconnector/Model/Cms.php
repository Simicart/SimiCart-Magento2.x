<?php

namespace Simi\Simiconnector\Model;

/**
 * Connector Model
 *
 * @method \Simi\Simiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simiconnector\Model\Resource\Page getResource()
 */
class Cms extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Simi\Simiconnector\Helper\Website
     **/
    protected $_websiteHelper;
    protected $_tableresource;

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
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ResourceConnection $tableresource,
        \Simi\Simiconnector\Model\ResourceModel\Cms $resource,
        \Simi\Simiconnector\Model\ResourceModel\Cms\Collection $resourceCollection,
        \Simi\Simiconnector\Helper\Website $websiteHelper
    )
    {
        $this->_tableresource = $tableresource;
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
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Cms');
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

    /*
     * Get CMS pages that shown on categories
     */
    public function getCategoryCMSPages(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $typeID = $objectManager->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('cms');
        $visibilityTable = $this->_tableresource->getTableName('simiconnector_visibility');
        $cmsCollection = $objectManager->get('Simi\Simiconnector\Model\Cms')->getCollection()->addFieldToFilter('type', '2')->setOrder('sort_order','ASC');
        $cmsCollection->getSelect()
                ->join(array('visibility' => $visibilityTable), 'visibility.item_id = main_table.cms_id AND visibility.content_type = ' . $typeID . ' AND visibility.store_view_id =' . $storeManager->getStore()->getId());
        $cmsArray = array();
        foreach ($cmsCollection as $cms) {
            $cmsArray[] = $cms->toArray();
        }
        return $cmsArray;
    }
}
