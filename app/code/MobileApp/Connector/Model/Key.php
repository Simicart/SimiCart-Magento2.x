<?php

namespace MobileApp\Connector\Model;

/**
 * Connector Model
 *
 * @method \MobileApp\Connector\Model\Resource\Page _getResource()
 * @method \MobileApp\Connector\Model\Resource\Page getResource()
 */
class Key extends \Magento\Framework\Model\AbstractModel
{
    /** @var \MobileApp\Connector\Helper\Website */
    protected $_websiteHelper;
    /**
     * @var AppFactory
     */
    protected $_appFactory;
    /**
     * @var PluginFactory
     */
    protected $_pluginFactory;
    /**
     * @var DesignFactory
     */
    protected $_designFactory;
    /**
     * @var ResourceModel\App\CollectionFactory
     */
    protected $_appCollectionFactory;
    /**
     * @var ResourceModel\Key\CollectionFactory
     */
    protected $_keyCollectionFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \MobileApp\Connector\Model\ResourceModel\Key $resource,
        \MobileApp\Connector\Model\ResourceModel\Key\Collection $resourceCollection,
        \MobileApp\Connector\Helper\Website $websiteHelper,
        \MobileApp\Connector\Model\AppFactory $app,
        \MobileApp\Connector\Model\PluginFactory $plugin,
        \MobileApp\Connector\Model\DesignFactory $design,
        \MobileApp\Connector\Model\ResourceModel\App\CollectionFactory $appCollection,
        \MobileApp\Connector\Model\ResourceModel\Key\CollectionFactory $keyCollection
    )
    {
        $this->_appFactory = $app;
        $this->_pluginFactory = $plugin;
        $this->_designFactory = $design;
        $this->_appCollectionFactory = $appCollection;
        $this->_keyCollectionFactory = $keyCollection;

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
        $this->_init('MobileApp\Connector\Model\ResourceModel\Key');
    }

    /**
     * @return mixed
     */
    public function getAppModel()
    {
        return $this->_appFactory->create();
    }

    /**
     * @return mixed
     */
    public function getDesignModel()
    {
        return $this->_designFactory->create();
    }

    /**
     * @return mixed
     */
    public function getHelper()
    {
        return $this->_pluginFactory->create();
    }

    /**
     * @return ResourceModel\App\Collection
     */
    public function getAppCollection()
    {
        return $this->_appCollectionFactory->create();
    }

    /**
     * @return mixed
     */
    public function getKeyCollection()
    {
        return $this->_keyCollectionFactory->create();
    }

    /**
     * @param $webId
     * @return mixed
     */
    public function getKey($webId)
    {
        $collection = $this->getCollection()->addFieldToFilter('website_id', array('eq' => $webId));
        $appCollection = $this->getAppCollection()->addFieldToFilter('website_id', array('eq' => $webId));

        if (!$appCollection->getFirstItem()->getId()) {
            $data = $this->getHelper()->getDataDesgin();
            foreach ($data as $item) {

                $model_d = $this->getDesignModel();
                $model_d->setData($item);
                $model_d->setWebsiteId($webId);
                $model_d->save();

                $model_a = $this->getAppModel();
                $model_a->setData($item);
                $model_a->setWebsiteId($webId);
                $model_a->save();
            }
        }
        return $collection->getFirstItem();
    }

    /**
     * @param $key
     * @param $webId
     */
    public function setKey($key, $webId)
    {

        $cache_key = $this->getKey($webId);
        $this->setData('key_secret', $key);
        $this->setData('website_id', $webId);

        if ($cache_key->getId()) {
            $this->setId($cache_key->getId());
            $this->save();
        }
    }

}
