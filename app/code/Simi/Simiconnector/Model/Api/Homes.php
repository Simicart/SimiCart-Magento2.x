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

        $storeId = $this->storeManager->getStore()->getId();

        //get cache
        if (isset($data['resourceid']) && ($data['resourceid'] == 'lite')) {
            $filePath = $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'Simiconnector' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR
                . $storeId . DIRECTORY_SEPARATOR . "home_cached.json";
            if (file_exists($filePath)) {
                $homeJson = file_get_contents($filePath);
                if ($homeJson) {
                    return ['home' => json_decode($homeJson, true)];
                }
            }
        }

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
        $productlists->setData($this->getData());
        $productlists = $productlists->index();

        $information = ['home' => [
            'homebanners' => $banners,
            'homecategories' => $categories,
            'homeproductlists' => $productlists,
        ]];

        //save cache
        if (isset($data['resourceid']) && ($data['resourceid'] == 'lite')) {
            $path = $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'Simiconnector'
                . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $storeId;
            if (!is_dir($path)) {
                try {
                    mkdir($path, 0777, true);
                } catch (\Exception $e) {
                }
            }
            $filePath = $path = $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'Simiconnector'
                . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . $storeId . DIRECTORY_SEPARATOR . "home_cached.json";

            if (!file_exists($filePath)) {
                $file = @fopen($filePath, 'w+');
                $data_json = json_encode($information['home']);
                file_put_contents($filePath, $data_json);
            }
        }
        return $information;
    }
}
