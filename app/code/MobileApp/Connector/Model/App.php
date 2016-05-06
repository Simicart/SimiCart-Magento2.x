<?php

namespace MobileApp\Connector\Model;

/**
 * Connector Model
 *
 * @method \MobileApp\Connector\Model\Resource\Page _getResource()
 * @method \MobileApp\Connector\Model\Resource\Page getResource()
 */
class App extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var ResourceModel\App\CollectionFactory
     */
    protected $_appCollectionFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ResourceModel\App $resource
     * @param ResourceModel\App\Collection $resourceCollection
     * @param ResourceModel\App\CollectionFactory $appCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \MobileApp\Connector\Model\ResourceModel\App $resource,
        \MobileApp\Connector\Model\ResourceModel\App\Collection $resourceCollection,
        \MobileApp\Connector\Model\ResourceModel\App\CollectionFactory $appCollection,
        array $data = []
    )
    {
        $this->_appCollectionFactory = $appCollection;
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
        $this->_init('MobileApp\Connector\Model\ResourceModel\App');
    }

    /**
     * @param $website_id
     */
    public function deleteList($website_id)
    {
        $model = $this;

        $collection = $this->getCollection()
            ->addFieldToFilter('website_id', array('eq' => $website_id));

        foreach ($collection as $item) {
            $model->setAppName("N/A");
            $model->setId($item->getId())->save();
        }
        return;
    }

    /**
     * @param $data
     * @param $website_id
     */
    public function saveList($data, $website_id)
    {
        $status = $data->status;
        if ($status == 'SUCCESS') {
            $list = $data->app_list;
            foreach ($list as $item) {
                $model = $this;
                $collection = $model->getCollection()
                    ->addFieldToFilter('website_id', array('eq' => $website_id))
                    ->addFieldToFilter('device_id', array('eq' => $item->device_id));
                $model->setAppName($item->name);
                $model->setExpiredTime($item->expired_time);
                $model->setStatus($item->status);
                $model->setId($collection->getFirstItem()->getId())->save();
            }
        }
        return;
    }

    /**
     * @param $today
     */
    public function checkExpriedTime($today)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('expired_time', array('to' => $today, 'data' => true))
            ->addFieldToFilter('status', array('nin' => array(0, 2)));
        foreach ($collection as $item) {
            $item->setStatus(0);
        }
        $collection->save();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getDeviceById($id)
    {
        return $this->load($id)->getDeviceId();
    }

    /**
     * @param $web_id
     * @param $device_id
     * @return \Magento\Framework\DataObject
     */
    public function getAppByWebDevice($web_id, $device_id)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('website_id', array('eq' => $web_id))
            ->addFieldToFilter('device_id', array('eq' => $device_id));
        return $collection->getFirstItem();
    }

    /**
     * @param $web_id
     * @param $categories
     */
    public function saveCategories($web_id, $categories)
    {
        $collection = $this->getCollection()->addFieldToFilter('website_id', array('eq' => $web_id));
        foreach ($collection as $item) {
            $item->setData("categories", $categories);
        }
        $collection->save();
    }

}
