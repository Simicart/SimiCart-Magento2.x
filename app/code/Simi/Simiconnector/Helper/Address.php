<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

class Address extends Data
{

    public function _getCart()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Cart');
    }

    public function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    /*
     * Get States
     */

    public function getStates($code)
    {
        $list = [];
        if ($code) {
            if($country = $this->simiObjectManager
                ->create('\Magento\Directory\Model\ResourceModel\Country\Collection')
                ->getItemByColumnValue('country_id', $code)) {
                $states = $country->getRegions();
                if ($states) {
                    foreach ($states as $state) {
                        $list[] = [
                            'state_id' => $state->getRegionId(),
                            'state_name' => $state->getName(),
                            'state_code' => $state->getCode(),
                        ];
                    }
                }
            }
        }
        
        return $list;
    }

    /*
     * Add Hidden Address Fields on Storeview Config Result
     */

    public function getCheckoutAddressSetting()
    {
        if ($this->getStoreConfig('simiconnector/hideaddress/hideaddress_enable') != '1') {
            return null;
        }

        $addresss = ['company', 'street', 'country_id', 'region_id', 'city', 'zipcode',
            'telephone', 'fax', 'prefix', 'suffix', 'dob', 'gender', 'taxvat'];

        foreach ($addresss as $address) {
            $path  = "simiconnector/hideaddress/" . $address;
            $value = $this->getStoreConfig($path);
            if (!$value || $value == null || !isset($value)) {
                $value = 3;
            }

            $address.='_show';
            if ($value == 1) {
                $data[$address] = "req";
            } elseif ($value == 2) {
                $data[$address] = "opt";
            } elseif ($value == 3) {
                $data[$address] = "";
            }
        }

        // address default value
        $address_default = ['street', 'country_id', 'region_id', 'city', 'zipcode', 'telephone'];
        foreach ($address_default as $address_key) {
            $path  = "simiconnector/hideaddress/" . $address_key. "_default";
            $value = $this->getStoreConfig($path);
            $data[$address_key.'_default'] = $value;
        }

        //sample add custom address fields
        $data['custom_fields']   = [];
        /*
        //text field
        $data['custom_fields'][] = ['code'     => 'text_field_sample',
            'title'    => 'Text Field',
            'type'     => 'text',
            'position' => '7',
        ];
        //number field
        $data['custom_fields'][] = ['code'     => 'number_field_sample',
            'title'    => 'Number Field',
            'type'     => 'number',
            'position' => '8',
        ];
        //single choice Option
        $data['custom_fields'][] = ['code'         => 'single_option_sample',
            'title'        => 'Sample Field Single Option',
            'type'         => 'single_option',
            'option_array' => ['Option Single 1', 'Option Single 2', 'Option Single 3'],
            'position'     => '9',
        ];
        //multi choice Option
        $data['custom_fields'][] = ['code'         => 'multi_option_sample',
            'title'        => 'Sample Field Multi Option',
            'type'         => 'multi_option',
            'option_array' => ['Option Multi 1', 'Option Multi 2'
                , 'Option Multi 3', 'Option Multi 4', 'Option Multi 5'],
            'separated_by' => '%',
            'position'     => '10',
        ];
        */
        return $data;
    }

    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    public function _getOnepage()
    {
        return $this->simiObjectManager->get('Magento\Checkout\Model\Type\Onepage');
    }

    /*
     * Convert Address before Saving
     */

    public function convertDataAddress($data)
    {
        $address = [];
        if (isset($data->country_id)) {
            $address['region'] = null;
            $address['region_code'] = null;
            $state_id = null;
            $country     = $data->country_id;
            $listState   = $this->getStates($country);
            $check_state = false;
            if (count($listState) == 0) {
                $check_state = true;
            }

            foreach ($listState as $state) {
                if (isset($data->region_code) && in_array($data->region_code, $state) ||
                        isset($data->region) && in_array($data->region, $state) ||
                        isset($data->region_id) && in_array($data->region_id, $state)) {
                    $state_id    = $state['state_id'];
                    $check_state = true;
                    break;
                }
            }
            
            if (!$check_state) {
                if (!$state_id) {
                    throw new \Simi\Simiconnector\Helper\SimiException(__('State invalid'), 4);
                }
            }
            $address['region_id'] = $state_id;
        }
        if ($this->getStoreConfig('simiconnector/hideaddress/hideaddress_enable')) {
            $this->applyDefaultValue($data);
        }
        foreach ((array) $data as $index => $info) {
            $address[$index] = $info;
        }
        if (isset($data->street)) {
            $address['street'] = [$data->street];
        }
        return $address;
    }
    
    public function applyDefaultValue(&$data)
    {
        if (!isset($data->country_id) && !isset($data->country_name) || empty($data->country_id)) {
            $data->country_id = $this->getStoreConfig('simiconnector/hideaddress/country_id_default');
        }

        if (!isset($data->street) || empty($data->street)) {
            $data->street = $this->getStoreConfig('simiconnector/hideaddress/street_default');
        }

        if (!isset($data->city) || empty($data->city)) {
            $data->city = $this->getStoreConfig('simiconnector/hideaddress/city_default');
        }

        if (!isset($data->postcode) || empty($data->postcode)) {
            $data->postcode = $this->getStoreConfig('simiconnector/hideaddress/zipcode_default');
        }
        if (!isset($data->telephone) || empty($data->telephone)) {
            $data->telephone = $this->getStoreConfig('simiconnector/hideaddress/telephone_default');
        }
        if (!isset($data->region_id) || empty($data->region_id)) {
            $data->region_id = $this->getStoreConfig('simiconnector/hideaddress/region_id_default');
        }
        return $data;
    }

    /*
     * Get Address to be Shown
     */

    public function getAddressDetail($data, $customer = null)
    {
        $street = $data->getStreet();
        if (!($email  = $data->getData('email')) && $customer && $customer->getEmail()) {
            $email = $customer->getEmail();
        }

        if (!isset($street[1])) {
            $street[1] = null;
        }

        if (!isset($street[2])) {
            $street[2] = null;
        }

        $detail = [
            'firstname'    => $data->getFirstname(),
            'lastname'     => $data->getLastname(),
            'prefix'       => $data->getPrefix(),
            'suffix'       => $data->getSuffix(),
            'vat_id'       => $data->getVatId(),
            'street'       => $street[0],
            'street2'       => $street[1],
            'city'         => $data->getCity(),
            'region'       => $data->getRegion(),
            'region_id'    => $data->getRegionId(),
            'region_code'  => $data->getRegionCode(),
            'postcode'     => $data->getPostcode(),
            'country_name' => $data->getCountry() ?
                $data->getCountryModel()->loadByCode($data->getCountry())->getName() : null,
            'country_id'   => $data->getCountry(),
            'telephone'    => $data->getTelephone(),
            'email'        => $email,
            'company'      => $data->getCompany(),
            'fax'          => $data->getFax(),
            'latlng'       => $street[2] != null ? $street[2] : "",
        ];
        if($customer != null){
            $detail['is_default_billing'] = $customer->getDefaultBilling()  == $data->getId();
            $detail['is_default_shipping'] = $customer->getDefaultShipping()  == $data->getId();
        }

        return $detail;
    }

    /*
     * Save Billing Address To Quote
     */

    public function saveBillingAddress($billingAddress)
    {
        $is_register_mode = false;
        if (isset($billingAddress->customer_password) && $billingAddress->customer_password) {
            $is_register_mode = true;
            $this->_getOnepage()->saveCheckoutMethod('register');
            $passwordHash     = $this->simiObjectManager
                    ->get('Magento\Customer\Model\Customer')->hashPassword($billingAddress->customer_password);
            $this->_getQuote()->setPasswordHash($passwordHash);
        } elseif ($this->simiObjectManager->get('Magento\Customer\Model\Session')->isLoggedIn()) {
            $this->_getOnepage()->saveCheckoutMethod('customer');
        } else {
            $this->_getOnepage()->saveCheckoutMethod('guest');
        }

        if ($is_register_mode) {
            $customer_email = $billingAddress->email;
            $customer       = $this->simiObjectManager->get('Magento\Customer\Model\Customer')
                ->setWebsiteId($this->storeManager->getStore()->getWebsiteId())
                ->loadByEmail($customer_email);
            if ($customer->getData('entity_id') != null) {
                throw new \Simi\Simiconnector\Helper\SimiException(__('There is already a customer '
                    . 'registered using this email address. '
                    . 'Please login using this email address or enter '
                    . 'a different email address to register your account.'), 7);
            }
        }

        $address                         = $this->convertDataAddress($billingAddress);
        $address['save_in_address_book'] = '1';

        if (isset($billingAddress->entity_id) && $billingAddress->entity_id) {
            $addressInterface = $this->simiObjectManager
                ->create('Magento\Customer\Api\AddressRepositoryInterface')->getById($billingAddress->entity_id);

            $billingAddress                  = $this->simiObjectManager
                ->get('Magento\Quote\Model\Quote\Address')->importCustomerAddressData($addressInterface);
            $this->_getQuote()->setBillingAddress($billingAddress);
            return;
        }

        $addressInterface                = $this->simiObjectManager
            ->create('Magento\Customer\Api\Data\AddressInterface');
        $billingAddress                  = $this->simiObjectManager
            ->get('Magento\Quote\Model\Quote\Address')->importCustomerAddressData($addressInterface);
        $billingAddress->setData($address);
        $this->_getQuote()->setBillingAddress($billingAddress);
    }

    /*
     * Save Shipping Address To quote
     */

    public function saveShippingAddress($shippingAddress)
    {
        $address                         = $this->convertDataAddress($shippingAddress);
        $address['save_in_address_book'] = '1';
        
        if (isset($shippingAddress->entity_id)) {
            $saveShipping = $this->_getOnepage()->saveShipping($address, $shippingAddress->entity_id);
            
            if (count($saveShipping) != 0 && isset($saveShipping['error']) && $saveShipping['error'] ==1) {
                $errorMessage ="";
                foreach ($saveShipping['message'] as $message) {
                    $errorMessage .= $message->__toString()."\n";
                }
                throw new \Simi\Simiconnector\Helper\SimiException($errorMessage, 4);
            }
        } else {
            $quote = $this->_getQuote();
            $quote->getShippingAddress()->addData($address);
            $quote->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates();
            $quote->save();
            $quote->collectTotals();
        }
    }

    /*
     * Get Geocode result from Lat and Long
     */
    public function getLocationInfo($lat, $lng) {
        try {
            $url    = 'https://geocode.xyz/'
                . trim($lat) . ',' . trim($lng) . '?geoit=xml';

            $note = file_get_contents($url);
            $data = simplexml_load_string($note);
            if ($data->geocode) {
                $addresses = [];
                $addresses['city'] = isset($data->city)?(string)$data->city:'';
                $addresses['state'] = isset($data->state)?
                    (string)$data->state:(
                    isset($data->region)?
                        (string)$data->region:
                        '');
                $addresses['country'] = isset($data->country)?
                    (string)$data->country:(
                    isset($data->prov)?
                        (string)$data->prov:
                        '');
                $addresses['zipcode'] = isset($data->postal)?
                    (string)$data->postal:
                    '';

                $addresses['address'] = isset($data->staddress)?
                    (string)$data->staddress:(
                    isset($data->region) ?
                        (string)$data->region:
                        '');
                $addresses['geocoding'] = $data;
                return $addresses;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function oldGetLocationInfo($lat, $lng)
    {
        try {
            $url    = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='
                    . trim($lat) . ',' . trim($lng) . '&sensor=false';
            $file_get_contents = 'file_get_contents';
            $json   = $file_get_contents($url);
            $data   = json_decode($json);
            $status = $data->status;
            if ($status == "OK") {
                $addresses = [];
                $address   = '';
                $addressComponentsCount = count($data->results[0]->address_components);
                for ($j = 0; $j < $addressComponentsCount; $j++) {
                    $addressComponents = $data->results[0]->address_components[$j];
                    $types             = $addressComponents->types;
                    if (in_array('street_number', $types)) {
                        $address .= $addressComponents->long_name;
                    }
                    if (in_array('route', $types) || in_array('locality', $types)) {
                        $address .= ', ' . $addressComponents->long_name;
                    }
                    if (in_array('postal_town', $types) || in_array('administrative_area_level_1', $types)) {
                        $city              = $addressComponents->long_name;
                        $addresses['city'] = $city;
                    }
                    if (in_array('administrative_area_level_2', $types)) {
                        $state              = $addressComponents->long_name;
                        $addresses['state'] = $state;
                    }
                    if (in_array('country', $types)) {
                        $country              = $addressComponents->short_name;
                        $addresses['country'] = $country;
                    }
                    if (in_array('postal_code', $types)) {
                        $zipcode              = $addressComponents->long_name;
                        $addresses['zipcode'] = $zipcode;
                    }
                }
                $addresses['address']   = $address;
                $addresses['geocoding'] = $data;
                return $addresses;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
