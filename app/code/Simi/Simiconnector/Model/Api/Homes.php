<?php
/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;


class Homes extends Apiabstract
{
    protected $_DEFAULT_ORDER = 'sort_order';

    public function setBuilderQuery() {
        
    }

    public function index() {
        return $this->show();
    }

    public function show() {
        $data = $this->getData();
        /*
         * Get Banners
         */
        $banners = $this->_objectManager->get('Simi\Simiconnector\Model\Api\Homebanners');
        $banners->builderQuery = $banners->getCollection();
        $banners->setPluralKey('homebanners');
        $banners = $banners->index();

        /*
         * Get Categories
         */
        
        $categories = $this->_objectManager->get('Simi\Simiconnector\Model\Api\Homecategories');
        $categories->setData($this->getData());
        $categories->builderQuery = $categories->getCollection();
        $categories->setPluralKey('homecategories');
        $categories = $categories->index();

        /*
         * Get Product List
         */
        $productlists = $this->_objectManager->get('Simi\Simiconnector\Model\Api\Homeproductlists');
        $productlists->builderQuery = $productlists->getCollection();
        if ($data['resourceid'] == 'lite') {
            $productlists->SHOW_PRODUCT_ARRAY = FALSE;
        }
        $productlists->setPluralKey('homeproductlists');
        $productlists->setData($this->getData());
        $productlists = $productlists->index();


        $information = array('home' => array(
                'homebanners' => $banners,
                'homecategories' => $categories,
                'homeproductlists' => $productlists,
        ));
        return $information;
    }
}
