<?php

namespace MobileApp\Connector\Controller\Config\Get;

class Stores extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $this->_serviceClassName = 'Magento\Store\Api\StoreRepositoryInterface';
        $this->_serviceMethodName = 'getList';
        $this->_params = [];
        return parent::execute();
    }

    /*
    * Format data
    *
    * @var $data array
    * @return array
    */
    protected function _formatData($data){
        $stores = [];
        foreach($data as $store){
            if($store['id'] > 0)
                $stores[] = [
                    'store_id' => $store['id'],
                    'store_name' => $store['name'],
                ];
        }

        return ['data' => $stores, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];
    }
}
