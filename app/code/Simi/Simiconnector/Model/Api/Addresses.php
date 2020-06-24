<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

class Addresses extends Apiabstract
{

    public $DEFAULT_ORDER = 'entity_id';

    public function setSingularKey($singularKey)
    {
        if ($singularKey != 'Address') {
            $this->singularKey = 'Address';
        }
        return $this;
    }

    public function setBuilderQuery()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            if ($data['resourceid'] != 'geocoding') {
                $auth = false;
                $customer     = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getCustomer();
                foreach ($customer->getAddresses() as $address) {
                    if ($address->getId() == $data['resourceid'])
                        $auth = true;
                }
                if ($auth)
                    $this->builderQuery = $this->simiObjectManager
                        ->create('Magento\Customer\Model\Address')->load($data['resourceid']);
                return;
            }
        } else {
            if (!$this->simiObjectManager->get('Magento\Customer\Model\Session')->isLoggedIn()) {
                throw new \Simi\Simiconnector\Helper\SimiException(__('You have not logged in'), 4);
            } else {
                $customer     = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getCustomer();
                $addressArray = [];
                $billing      = $customer->getPrimaryBillingAddress();
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
                $this->builderQuery = $this->simiObjectManager
                        ->create('Magento\Customer\Model\Address')->getCollection()
                        ->addFieldToFilter('entity_id', ['in' => $addressArray]);
            }
        }
    }

    /*
     * Add Address
     */

    public function store()
    {
        $data               = $this->getData();
        $address            = $this->simiObjectManager->get('Simi\Simiconnector\Model\Address')->saveAddress($data);
        $this->builderQuery = $address;
        return $this->show();
    }

    /*
     * Edit Address
     */

    public function update()
    {
        $data               = $this->getData();
        $address            = $this->simiObjectManager->get('Simi\Simiconnector\Model\Address')->saveAddress($data);
        $this->builderQuery = $address;
        return $this->show();
    }

    /*
     * Remove Address
     */

    public function destroy()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            $this->builderQuery = $this->simiObjectManager
                    ->create('Magento\Customer\Model\Address')->load($data['resourceid']);
            $this->builderQuery->delete();
            return $this->show();
        }
        throw new \Simi\Simiconnector\Helper\SimiException(__('No Address ID sent'), 4);
    }

    /*
     * Get Address Detail
     */

    public function index()
    {
        $result    = parent::index();
        $customer  = $this->simiObjectManager->get('Magento\Customer\Model\Session')->getCustomer();
        $addresses = $result['addresses'];
        foreach ($addresses as $index => $address) {
            $addressModel = $this->loadAddressWithId($address['entity_id']);
            $addresses[$index] = array_merge($address, $this->simiObjectManager
                    ->get('Simi\Simiconnector\Helper\Address')->getAddressDetail($addressModel, $customer));
        }
        $result['addresses'] = $addresses;
        return $result;
    }
    
    public function loadAddressWithId($id)
    {
        $addressModel    = $this->getAddressModel()->load($id);
        return $addressModel;
    }
    
    public function getAddressModel()
    {
        return $this->simiObjectManager
                    ->get('Magento\Customer\Model\Address');
    }

    /*
     * Geocoding
     */

    public function show()
    {
        $data = $this->getData();
        if ($data['resourceid']) {
            if ($data['resourceid'] == 'geocoding') {
                $result        = [];
                $addressDetail = [];
                $longitude     = $data['params']['longitude'];
                $latitude      = $data['params']['latitude'];
                $dataresult    = $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address')
                        ->getLocationInfo($latitude, $longitude);
                $address   = '';
                if (!$dataresult || !isset($dataresult['geocoding']) || !$dataresult['geocoding'])
                    throw new \Simi\Simiconnector\Helper\SimiException(__('Cannot get address for the given location'), 6);
                $addressDetail = $dataresult;
                /*
                $addressComponentsCount = count($dataresult->results[0]->address_components);
                for ($j = 0; $j < $addressComponentsCount; $j++) {
                    $addressComponents = $dataresult->results[0]->address_components[$j];
                    $types             = $addressComponents->types;
                    if (in_array('street_number', $types)) {
                        $address .= $addressComponents->long_name;
                    }
                    if (in_array('route', $types) || in_array('locality', $types)) {
                        $address .= ', ' . $addressComponents->long_name;
                    }
                    $addressDetail['street'] = $address;
                    if (in_array('postal_town', $types) || in_array('administrative_area_level_1', $types)) {
                        $addressDetail['region']    = $addressComponents->long_name;
                        $addressDetail['region_id'] = $addressComponents->short_name;
                    }

                    if (in_array('administrative_area_level_2', $types)) {
                        $addressDetail['city'] = $addressComponents->short_name;
                    }

                    if (in_array('country', $types)) {
                        $addressDetail['country_name'] = $addressComponents->long_name;
                        $addressDetail['country_id']   = $addressComponents->short_name;
                    }
                    if (in_array('postal_code', $types)) {
                        $addressDetail['postcode'] = $addressComponents->long_name;
                    }
                }
                */
                $addressDetail['region']    = $addressDetail['state'];
                $addressDetail['region_id'] = $addressDetail['state'];
                $addressDetail['country_name'] = $addressDetail['country'];
                $addressDetail['country_id'] = $addressDetail['country'];
                $addressDetail['postcode'] = $addressDetail['zipcode'];
                $addressDetail['street'] = $addressDetail['address'];
                $result['address'] = $addressDetail;
                return $result;
            }
        }
        return parent::show();
    }
}
