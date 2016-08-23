<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;


class Addresses extends Apiabstract
{
    
    protected $_DEFAULT_ORDER = 'entity_id';

    public function setSingularKey($singularKey) {
        $this->singularKey = 'Address';
        return $this;
    }

    public function setBuilderQuery() {
        $data = $this->getData();
        if ($data['resourceid']) {
            
        } else {
            if (!$this->_objectManager->get('Magento\Customer\Model\Session')->isLoggedIn()) {
                throw new Exception($this->_helper->__('You have not logged in'), 4);
            } else {
                $customer = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
                $addressArray = array();
                $billing = $customer->getPrimaryBillingAddress();
                if ($billing) {
                    $addressArray[] = $billing->getId();
                }
                $shipping = $customer->getPrimaryShippingAddress();
                if ($shipping) {
                    $addressArray[] = $shipping->getId();
                }
                foreach ($customer->getAddresses() as $index => $address) {
                    $addressArray[] = $index;
                }
                $this->builderQuery = $this->_objectManager->create('Magento\Customer\Model\Address')->getCollection()
                        ->addFieldToFilter('entity_id', array('in' => $addressArray));
            }
        }
    }

    /*
     * Add Address
     */

    public function store() {
        $data = $this->getData();
        $address = $this->_objectManager->get('Simi\Simiconnector\Model\Address')->saveAddress($data);
        $this->builderQuery = $address;
        return $this->show();
    }

    /*
     * Edit Address
     */

    public function update() {
        $data = $this->getData();
        $address = $this->_objectManager->get('Simi\Simiconnector\Model\Address')->saveAddress($data);
        $this->builderQuery = $address;
        return $this->show();
    }

    /*
     * Get Address Detail
     */

    public function index() {
        $result = parent::index();
        $customer = $this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer();
        $addresses = $result['addresses'];
        foreach ($addresses as $index => $address) {
            $addressModel = $this->_objectManager->get('Magento\Customer\Model\Address')->load($address['entity_id']);
            $addresses[$index] = array_merge($address, $this->_objectManager->get('Simi\Simiconnector\Helper\Address')->getAddressDetail($addressModel, $customer));
        }
        $result['addresses'] = $addresses;
        return $result;
    }

    /*
     * Geocoding
     */

    public function show() {
        $data = $this->getData();
        if ($data['resourceid']) {
            if ($data['resourceid'] == 'geocoding') {
                $result = array();
                $addressDetail = array();
                $longitude = $data['params']['longitude'];
                $latitude = $data['params']['latitude'];
                $dataresult = $this->_objectManager->get('Simi\Simiconnector\Helper\Address')->getLocationInfo($latitude, $longitude);
                $dataresult = $dataresult['geocoding'];
                for ($j = 0; $j < count($dataresult->results[0]->address_components); $j++) {
                    $addressComponents = $dataresult->results[0]->address_components[$j];
                    $types = $addressComponents->types;
                    if (in_array('street_number', $types)) {
                        $address .= $addressComponents->long_name;
                    }
                    if (in_array('route', $types)) {
                        $address .= ' ' . $addressComponents->long_name;
                    }
                    if (in_array('locality', $types)) {
                        $address .= ', ' . $addressComponents->long_name;
                    }
                    $addressDetail['street'] = $address;
                    if (in_array('postal_town', $types) || in_array('administrative_area_level_1', $types)) {
                        $addressDetail['region'] = $addressComponents->long_name;
                        $addressDetail['region_id'] = $addressComponents->short_name;
                    }

                    if (in_array('administrative_area_level_2', $types)) {
                        $addressDetail['city'] = $addressComponents->short_name;
                    }

                    if (in_array('country', $types)) {
                        $addressDetail['country_name'] = $addressComponents->long_name;
                        $addressDetail['country_id'] = $addressComponents->short_name;
                    }
                    if (in_array('postal_code', $types)) {
                        $addressDetail['postcode'] = $addressComponents->long_name;
                    }
                }
                $result['address'] = $addressDetail;
                return $result;
            }
        }
        return parent::show();
    }

}
