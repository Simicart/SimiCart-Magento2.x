<?php

namespace Simi\Simiconnector\Model;

/**
 * Simiconnector Model
 *
 * @method \Simi\Simiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simiconnector\Model\Resource\Page getResource()
 */
class Banner extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Simi\Simiconnector\Helper\Website
     **/
    protected $_websiteHelper;

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
    protected $_objectManager;
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Simi\Simiconnector\Model\ResourceModel\Banner $resource,
        \Simi\Simiconnector\Model\ResourceModel\Banner\Collection $resourceCollection,
        \Simi\Simiconnector\Helper\Website $websiteHelper
    )
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
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

        $this->_init('Simi\Simiconnector\Model\ResourceModel\Banner');
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
    
    public function delete() {
        $typeID = $this->_objectManager->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('banner');
        $visibleStoreViews = $this->_objectManager->create('Simi\Simiconnector\Model\Visibility')->getCollection()
                            ->addFieldToFilter('content_type', $typeID)
                            ->addFieldToFilter('item_id', $this->getId());
        foreach ($visibleStoreViews as $visibilityItem)
            $visibilityItem->delete();
        return parent::delete();
    }

}
