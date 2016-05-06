<?php

namespace MobileApp\Connector\Model;

/**
 * Connector Model
 *
 * @method \MobileApp\Connector\Model\Resource\Page _getResource()
 * @method \MobileApp\Connector\Model\Resource\Page getResource()
 */
class Plugin extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \MobileApp\Connector\Helper\Website
     */
    protected $_websiteHelper;

    /**
     * @var ResourceModel\Plugin\CollectionFactory
     */
    protected $_pluginCollectionFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceModel\Plugin $resource
     * @param ResourceModel\Plugin\Collection $resourceCollection
     * @param \MobileApp\Connector\Helper\WebsiteFactory $websiteHelper
     * @param ResourceModel\Plugin\CollectionFactory $pluginCollectionFactory
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \MobileApp\Connector\Model\ResourceModel\Plugin $resource,
        \MobileApp\Connector\Model\ResourceModel\Plugin\Collection $resourceCollection,
        \MobileApp\Connector\Helper\WebsiteFactory $websiteHelper,
        \MobileApp\Connector\Model\ResourceModel\Plugin\CollectionFactory $pluginCollectionFactory
    )
    {
        $this->_websiteHelper = $websiteHelper;
        $this->_pluginCollectionFactory = $pluginCollectionFactory;

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
        $this->_init('MobileApp\Connector\Model\ResourceModel\Plugin');
    }

    /**
     * @return ResourceModel\Plugin\Collection
     */
    public function getCollectionData()
    {
        return $this->_pluginCollectionFactory->create();
    }

    /**
     * @return \MobileApp\Connector\Helper\Website|\MobileApp\Connector\Helper\WebsiteFactory
     */
    public function getHelper()
    {
        return $this->_websiteHelper;
    }

    /**
     * @param $web_id
     */
    public function deleteList($web_id)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('website_id', array('eq' => $web_id));
        foreach ($collection as $item) {
            $item->delete();
        }
        $collection->save();
    }

    /**
     * @param $data
     * @param $web_id
     */
    public function saveList($data, $web_id)
    {
        $status = $data->status;
        if ($status == 'SUCCESS') {
            $list = $data->plugin_list;
            /**
             *  list[
             *      {
             *         version : 12,
             *         name : test,
             *         expired_time : 2014-1-2,
             *         plaform : 2,
             *         status : 1,
             *         plugin_code: 1
             *      },
             *       {
             *      },
             *  ]
             */
            foreach ($list as $item) {
                $it = $this->getItem($item->plugin_code);
                $model = $this;
                $model->setPluginName($item->name);
                $model->setPluginVersion($item->version);
                $model->setExpiredTime($item->expired_time);
                $model->setPluginCode($item->plugin_code);
                $model->setStatus($item->status);
                $model->setDeviceId($item->plaform);
                $model->setWebsiteId($web_id);
                $model->setPluginSku($item->plugin_sku);
                $model->setId($it)->save();
            }
        }

        return;
    }

    /**
     * @param $code
     * @return null
     */
    public function getItem($code)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('plugin_code', array('eq' => $code));
        if ($collection->getSize()) {
            return $collection->getFirstItem()->getId();
        }
        return null;
    }

    /**
     * @param $today
     */
    public function checkExpriedTime($today)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('expired_time', array('to' => $today, 'data' => true))
            ->addFieldToFilter('status', array('nin' => array(0, 1)));
        foreach ($collection as $item) {
            $item->setStatus(1);
        }
        $collection->save();
    }

    /**
     * @param $device_id
     * @return $this
     */
    public function getListPlugin($device_id)
    {
        if ($device_id == 2) {
            $device_id = 1;
        }

        $website = $this->getHelper()->getDefaultWebsite()->getId();
        $collection = $this->getCollection()
            ->addFieldToFilter('website_id', array('eq' => $website))
            ->addFieldToFilter('device_id', array('eq' => $device_id))
            ->addFieldToFilter('status', array('nin' => array(0, 2)));
        return $collection;
    }

    /**
     * @param $sku
     * @return mixed
     */
    public function checkPlugin($sku)
    {
        $website = $this->getHelper()->getDefaultWebsite()->getId();
        $collection = $this->getCollectionData()
            ->addFieldToFilter('website_id', array('eq' => $website))
            ->addFieldToFilter('plugin_sku', array('eq' => $sku))
            ->addFieldToFilter('status', array('nin' => array(0, 2)));
        return $collection->getFirstItem()->getId();
    }

}
