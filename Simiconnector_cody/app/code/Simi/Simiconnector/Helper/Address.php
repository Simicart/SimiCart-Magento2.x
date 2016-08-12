<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Address extends \Simi\Simiconnector\Helper\Data
{
    
    /*
     * Get States
     */
    public function getStates($code) {
        $list = array();
        if ($code) {
            $states = \Magento\Framework\App\ObjectManager::getInstance()
                        ->create('\Magento\Directory\Model\ResourceModel\Country\Collection')
                        ->addFieldToFilter('country_id',$code)->getFirstItem()->getRegions();
            foreach ($states as $state) {
                $list[] = array(
                    'state_id' => $state->getRegionId(),
                    'state_name' => $state->getName(),
                    'state_code' => $state->getCode(),
                );
            }
        }
        return $list;
    }
    
    /*
     * Add Hidden Address Fields on Storeview Config Result
     */
    public function getCheckoutAddressSetting() {
        if ($this->getStoreConfig('simiconnector/hideaddress/hideaddress_enable') != '1') 
            return null;
        
        $addresss = array('company', 'street', 'country_id', 'region_id', 'city', 'zipcode',
            'telephone', 'fax', 'prefix', 'suffix', 'birthday', 'gender', 'taxvat');
        
        foreach ($addresss as $address) {
            $path = "simiconnector/hideaddress/" . $address;
            $value = $this->getStoreConfig($path);
            if (!$value || $value == null || !isset($value))
                $value = 3;
            if ($value == 1)
                $data[$address] = "req";
            else if ($value == 2)
                $data[$address] = "opt";
            else if ($value == 3)
                $data[$address] = "";
        }
        //sample add custom address fields
        $data['custom_fields'] = array();
        //text field 
        $data['custom_fields'][] = array('code'=>'text_field_sample',
            'title'=>'Text Field',
            'type'=>'text',
            'position'=>'7',
            );
        //number field 
        $data['custom_fields'][] = array('code'=>'number_field_sample',
            'title'=>'Number Field',
            'type'=>'number',
            'position'=>'8',
            );
        //single choice Option
        $data['custom_fields'][] = array('code'=>'single_option_sample',
            'title'=>'Sample Field Single Option',
            'type'=>'single_option',
            'option_array'=>array('Option Single 1', 'Option Single 2', 'Option Single 3'),
            'position'=>'9',
            );
        //multi choice Option
        $data['custom_fields'][] = array('code'=>'multi_option_sample',
            'title'=>'Sample Field Multi Option',
            'type'=>'multi_option',
            'option_array'=>array('Option Multi 1', 'Option Multi 2', 'Option Multi 3', 'Option Multi 4', 'Option Multi 5'),
            'separated_by'=>'%',
            'position'=>'10',
            );
        return $data;
    }
    
    public function getStoreConfig($path) {
        return $this->_scopeConfig->getValue($path);
    }
}

