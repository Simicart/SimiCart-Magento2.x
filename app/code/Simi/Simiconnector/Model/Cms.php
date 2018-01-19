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
     * */
    public $websiteHelper;
    public $tableresource;
    public $simiObjectManager;

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
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Simi\Simiconnector\Helper\Website $websiteHelper
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->tableresource    = $tableresource;
        $this->websiteHelper    = $websiteHelper;

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
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Cms');
    }

    /**
     * @return array Status
     */
    public function toOptionStatusHash()
    {
        $status = [
            '1' => __('Enable'),
            '2' => __('Disabled'),
        ];
        return $status;
    }

    /**
     * @return array Website
     */
    public function toOptionWebsiteHash()
    {
        $website_collection = $this->websiteHelper->getWebsiteCollection();
        $list               = [];
        $list[0]            = __('All');
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($website_collection) > 0) {
            foreach ($website_collection as $website) {
                $list[$website->getId()] = $website->getName();
            }
        }
        return $list;
    }

    /*
     * Get CMS pages that shown on categories
     */

    public function getCategoryCMSPages()
    {
        $simiObjectManager = $this->simiObjectManager;
        $storeManager      = $simiObjectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $typeID            = $simiObjectManager
            ->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('cms');
        $visibilityTable   = $this->tableresource->getTableName('simiconnector_visibility');
        $cmsCollection     = $simiObjectManager
            ->get('Simi\Simiconnector\Model\Cms')->getCollection()
            ->addFieldToFilter('type', '2')
            ->setOrder('sort_order', 'ASC')
            ->getCategoryCMSPages($visibilityTable, $typeID, $storeManager
                ->getStore()->getId());

        $cmsArray          = [];
        foreach ($cmsCollection as $cms) {
            $result = $cms->toArray();
            $result['cms_content'] = $this->simiObjectManager
                ->get('Magento\Cms\Model\Template\FilterProvider')
                ->getPageFilter()->filter($result['cms_content']);
            $cmsArray[] = $result;
        }
        return $cmsArray;
    }

    public function delete()
    {
        $typeID            = $this->simiObjectManager
                ->get('Simi\Simiconnector\Helper\Data')->getVisibilityTypeId('cms');
        $visibleStoreViews = $this->simiObjectManager->create('Simi\Simiconnector\Model\Visibility')->getCollection()
                ->addFieldToFilter('content_type', $typeID)
                ->addFieldToFilter('item_id', $this->getId());
        foreach ($visibleStoreViews as $visibilityItem) {
            $this->simiObjectManager
                            ->get('Simi\Simiconnector\Helper\Data')->deleteModel($visibilityItem);
        }
        return parent::delete();
    }
}
