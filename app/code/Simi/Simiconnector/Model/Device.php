<?php

namespace Simi\Simiconnector\Model;

/**
 * Connector Model
 *
 * @method \Simi\Simiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simiconnector\Model\Resource\Page getResource()
 */
class Device extends \Magento\Framework\Model\AbstractModel {

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
    \Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Simi\Simiconnector\Model\ResourceModel\Device $resource, \Simi\Simiconnector\Model\ResourceModel\Device\Collection $resourceCollection, \Simi\Simiconnector\Helper\Website $websiteHelper
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
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Device');
    }

    /**
     * @return array Website
     */
    public function toOptionStoreviewHash() {
        $storeViewCollection = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Store\Model\Store')->getCollection();
        $list = array();
        $list[0] = __('All');
        if (sizeof($storeViewCollection) > 0) {
            foreach ($storeViewCollection as $storeView) {
                $list[$storeView->getId()] = $storeView->getName();
            }
        }
        return $list;
    }

    /**
     * @return array Website
     */
    public function toOptionCountryHash() {
        $country_collection = $this->_websiteHelper->getCountryCollection();
        $list = array();
        if (sizeof($country_collection) > 0) {
            foreach ($country_collection as $country) {
                $list[$country->getId()] = $country->getName();
            }
        }
        return $list;
    }

    /**
     * @return array Devices
     */
    public function toOptionDeviceHash() {
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
    public function toOptionDemoHash() {
        $demos = array(
            '0' => __('NO'),
            '1' => __('YES'),
            '3' => __('N/A'),
        );
        return $demos;
    }

    public function detectMobile() {
        $user_agent = '';
        if ($_SERVER["HTTP_USER_AGENT"]) {
            $user_agent = $_SERVER["HTTP_USER_AGENT"];
        }
        if (strstr($user_agent, 'iPhone') || strstr($user_agent, 'iPod')) {
            return 1;
        } elseif (strstr($user_agent, 'iPad')) {
            return 2;
        } elseif (strstr($user_agent, 'Android')) {
            return 3;
        } else {
            return 1;
        }
    }

    public function saveDevice($data) {
        if ($this->_objectManager == null)
            $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $deviceData = $data['contents'];
        if (!$deviceData->device_token)
            throw new \Exception(__('No Device Token Sent'), 4);
        if (isset($deviceData->plaform_id))
            $device_id = $deviceData->plaform_id;

        if (!isset($device_id))
            $device_id = $this->detectMobile();
        $existed_device = $this->getCollection()->addFieldToFilter('device_token', $deviceData->device_token)->getFirstItem();
        if ($existed_device->getId()) {
            //if (($existed_device->getData('storeview_id') != null) && ($existed_device->getData('storeview_id') ==  $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId()))
            $this->setId($existed_device->getId());
        }
        if (isset($deviceData->latitude) && isset($deviceData->longitude)) {
            $this->setData('latitude', $deviceData->latitude);
            $this->setData('longitude', $deviceData->longitude);
            $latitude = $deviceData->latitude;
            $longitude = $deviceData->longitude;
            $addresses = $this->_objectManager->get('Simi\Simiconnector\Helper\Address')->getLocationInfo($latitude, $longitude);
            if ($addresses)
                $this->setData($addresses);
        }
        $this->setData('device_token', $deviceData->device_token);
        $this->setData('plaform_id', $device_id);
        $this->setData('storeview_id', $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId());
        $this->setData('created_time', $this->_objectManager->get('\Magento\Framework\Stdlib\DateTime\DateTimeFactory')->create()->gmtDate());
        if (isset($deviceData->user_email))
            $this->setData('user_email', $deviceData->user_email);
        if (isset($deviceData->app_id))
            $this->setData('app_id', $deviceData->app_id);
        $this->setData('device_ip', $_SERVER['REMOTE_ADDR']);
        $this->setData('device_user_agent', $_SERVER['HTTP_USER_AGENT']);
        if (isset($deviceData->build_version))
            $this->setData('build_version', $deviceData->build_version);
        if (!isset($deviceData->is_demo)) {
            $this->setData('is_demo', 3);
        } else
            $this->setData('is_demo', $deviceData->is_demo);
        $this->save();
    }

}
