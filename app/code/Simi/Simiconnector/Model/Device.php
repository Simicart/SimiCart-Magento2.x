<?php

namespace Simi\Simiconnector\Model;

/**
 * Connector Model
 *
 * @method \Simi\Simiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simiconnector\Model\Resource\Page getResource()
 */
class Device extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @var \Simi\Simiconnector\Helper\Website
     * */
    public $websiteHelper;
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
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        \Magento\Framework\Registry $registry,
        \Simi\Simiconnector\Model\ResourceModel\Device $resource,
        \Simi\Simiconnector\Model\ResourceModel\Device\Collection $resourceCollection,
        \Simi\Simiconnector\Helper\Website $websiteHelper
    ) {
   
        $this->simiObjectManager = $simiObjectManager;
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
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Device');
    }

    /**
     * @return array Website
     */
    public function toOptionStoreviewHash()
    {
        $storeViewCollection = $this->simiObjectManager->get('\Magento\Store\Model\Store')->getCollection();
        $list                = [];
        $list[0]             = __('All');
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($storeViewCollection) > 0) {
            foreach ($storeViewCollection as $storeView) {
                $list[$storeView->getId()] = $storeView->getName();
            }
        }
        return $list;
    }

    /**
     * @return array Website
     */
    public function toOptionCountryHash()
    {
        $country_collection = $this->websiteHelper->getCountryCollection();
        $list               = [];
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countArray($country_collection) > 0) {
            foreach ($country_collection as $country) {
                $list[$country->getId()] = $country->getName();
            }
        }
        return $list;
    }

    /**
     * @return array Devices
     */
    public function toOptionDeviceHash()
    {
        $devices = [
            '1' => __('iPhone'),
            '2' => __('iPad'),
            '3' => __('Android'),
        ];
        return $devices;
    }

    /**
     * @return array Devices
     */
    public function toOptionDemoHash()
    {
        $demos = [
            '0' => __('NO'),
            '1' => __('YES'),
            '3' => __('N/A'),
        ];
        return $demos;
    }

    public function detectMobile()
    {
        return 1;
    }

    public function saveDevice($data)
    {
        $deviceData = $data['contents'];
        if (!$deviceData->device_token) {
            throw new \Simi\Simiconnector\Helper\SimiException(__('No Device Token Sent'), 4);
        }
        if (isset($deviceData->plaform_id)) {
            $device_id = $deviceData->plaform_id;
        } else {
            $device_id = $this->detectMobile();
        }
        
        if (isset($deviceData->latitude) && isset($deviceData->longitude)) {
            $this->setData('latitude', $deviceData->latitude);
            $this->setData('longitude', $deviceData->longitude);
            $latitude  = $deviceData->latitude;
            $longitude = $deviceData->longitude;
            $addresses = $this->simiObjectManager
                    ->get('Simi\Simiconnector\Helper\Address')->getLocationInfo($latitude, $longitude);
            if ($addresses) {
                $this->setData($addresses);
            }
        }
        $this->setData('device_token', $deviceData->device_token);
        $this->setData('plaform_id', $device_id);
        $this->setData('storeview_id', $this->simiObjectManager
                ->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId());
        $this->setData('created_time', $this->simiObjectManager
                ->get('\Magento\Framework\Stdlib\DateTime\DateTimeFactory')->create()->gmtDate());
        if (isset($deviceData->user_email)) {
            $this->setData('user_email', $deviceData->user_email);
        }
        if (isset($deviceData->app_id)) {
            $this->setData('app_id', $deviceData->app_id);
        }
        $obj = $this->simiObjectManager->get('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
        $ip =  $obj->getRemoteAddress();
        $this->setData('device_ip', $ip);
        /*
         Incase customer want to get User Agent
         * Use the function below, it's now hidden to pass 
         * Magento connect warning check
        $this->setData('device_user_agent', $_SERVER['HTTP_USER_AGENT']);
         * 
         */
        if (isset($deviceData->build_version)) {
            $this->setData('build_version', $deviceData->build_version);
        }
        if (!isset($deviceData->is_demo)) {
            $this->setData('is_demo', 3);
        } else {
            $this->setData('is_demo', $deviceData->is_demo);
        }

        $existed_device = $this->getCollection()
                ->getItemByColumnValue('device_token', $deviceData->device_token);
        if ($existed_device && $existed_device->getId()) {
            $this->setId($existed_device->getId());
        }
        $this->save();
    }
}
