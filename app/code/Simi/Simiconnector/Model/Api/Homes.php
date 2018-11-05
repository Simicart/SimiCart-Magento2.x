<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Model\Api;

use Magento\Framework\App\Filesystem\DirectoryList;

class Homes extends Apiabstract
{

    public $DEFAULT_ORDER = 'sort_order';

    /**
     * @var Magento\Framework\App\Filesystem\DirectoryList $directoryList ;
     */
    public $directoryList;

    public function __construct(\Magento\Framework\ObjectManagerInterface $simiObjectManager, DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
        parent::__construct($simiObjectManager);
    }

    public function setBuilderQuery()
    {
        return null;
    }

    public function index()
    {
        return $this->show();
    }

    public function show()
    {
        $data = $this->getData();
        /*
         * Get Banners
         */
        $banners = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Homebanners');
        $banners->builderQuery = $banners->getCollection();
        $banners->setPluralKey('homebanners');
        $banners = $banners->index();

        /*
         * Get Categories
         */

        $categories = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Homecategories');
        $categories->setData($this->getData());
        $categories->builderQuery = $categories->getCollection();
        $categories->setPluralKey('homecategories');
        $categories = $categories->index();

        /*
         * Get Product List
         */
        $productlists = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Homeproductlists');
        $productlists->builderQuery = $productlists->getCollection();
        if ($data['resourceid'] == 'lite') {
            $productlists->SHOW_PRODUCT_ARRAY = false;
        }
        $productlists->setPluralKey('homeproductlists');
        $productlists->setData($data);
        $productlists = $productlists->index();

        $information = ['home' => [
            'homebanners' => $banners,
            'homecategories' => $categories,
            'homeproductlists' => $productlists,
        ]];
        return $information;
    }
}
