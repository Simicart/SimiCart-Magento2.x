<?php

namespace MobileApp\Connector\Controller\Config\Register;

class Device extends \MobileApp\Connector\Controller\Connector
{
    const ANDROID_PLATFORM = 3;
    const IPHONE_PLATFORM = 1;
    const IPAD_PLATFORM = 2;
    const GOOGLE_MAP_API = 'http://maps.googleapis.com/maps/api/geocode/json?latlng=';
    /**
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getParam('data');
        $params = $data?json_decode($data, true):[];

        $device = $this->_objectManager
            ->create('MobileApp\Connector\Model\Device');

        $data = $this->getAddress($params['latitude'], $params['longitude']);
        $data['platform_id'] = $this->_getPlatform();
        $data['website_id'] = $this->storeManager->getStore()->getWebsiteId();
        $data['device_token'] = $params['device_token'];
        $data['created_time'] = $this->dateTime->date();

        $device->setData($data);
        try{
            $device->save();
            $outputData = ['status' => 'SUCCESS', 'message' => ['SUCCESS']];
        }catch (Exception $e){
            $outputData = ['status' => 'FAIL', 'message' => [$e->getMessage()]];
        }

        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
    }


    /*
     * Get address form
     *
     * @param $latitude float
     * @param $longitude float
     */
    protected function getAddress($latitude, $longitude){
        $url = self::GOOGLE_MAP_API.trim($latitude).','.trim($longitude).'&sensor=false';
        $json = file_get_contents($url);
        $data = json_decode($json, true);

        $street = $city = $state = $country = $zipcode = '';
        if($data['status'] == 'OK'){
            foreach($data['results'][0]['address_components'] as $item){
                if(in_array('street_number', $item['types'])){
                    $street .= $item['long_name'];
                } elseif(in_array('route', $item['types'])){
                    $street .= ' '.$item['long_name'];
                } elseif(in_array('locality', $item['types'])){
                    $street .= ' '.$item['long_name'];
                } elseif(in_array('postal_town', $item['types']) || in_array('administrative_area_level_1', $item['types'])){
                    $city .= $item['long_name'];
                } elseif(in_array('administrative_area_level_2', $item['types'])){
                    $state .= $item['long_name'];
                } elseif(in_array('country', $item['types'])){
                    $country .= $item['long_name'];
                } elseif(in_array('postal_code', $item['types'])){
                    $zipcode .= $item['long_name'];
                }
            }
        }
        return [
            'address' => $street,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'zipcode' => $zipcode,
            'latitude' => $latitude,
            'longitude' => $longitude
        ];
    }

    /*
     * Get platform
     *
     * @return int
     */
    protected function _getPlatform() {
        $userAgent = $_SERVER['HTTP_USER_AGENT']?:'';
        if (strstr($userAgent, 'iPhone') || strstr($userAgent, 'iPod')) {
            return self::IPHONE_PLATFORM;
        } elseif (strstr($userAgent, 'iPad')) {
            return self::IPAD_PLATFORM;
        } elseif (strstr($userAgent, 'Android')) {
            return self::ANDROID_PLATFORM;
        } else {
            return self::IPHONE_PLATFORM;
        }
    }
}
