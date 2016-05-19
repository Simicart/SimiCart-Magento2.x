<?php

namespace MobileApp\Connector\Controller\Checkout;

class Checkout extends \MobileApp\Connector\Controller\Connector
{
    /*
     * Get show type
     *
     * @param $methodCode string
     * @return int
     */
    protected function _getShowType($methodCode){
        if(in_array($methodCode, ['free', 'checkmo', 'cashondelivery', 'banktransfer', 'purchaseorder', 'transfer_mobile']))
            return 0;
        elseif(in_array($methodCode, ['authorizenet_directpost',]))
            return 3;
        elseif(in_array($methodCode, ['braintree'])){
            return 2; //sdk
        }elseif(in_array($methodCode, ['ccsave'])){
            return 1; //credit card
        }else
            return 0;
    }
}
