<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Simi\Simiconnector\Model\Resolver\DataProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * StoreConfig field data provider, used for GraphQL request processing.
 */
class SimiCategoryCmsDataProvider extends DataProviderInterface
{
    /**
     * Get store config data
     *
     * @return array
     */
    public function getCategoryCms($args){

        $categoryId = $this->storeManager->getStore()->getRootCategoryId();
        
        if($args && isset($args['categoryId']) && $args['categoryId']) {
            $categoryId = $args['categoryId'];
        }

        $model = $this->simiObjectManager->create('\Magento\Catalog\Model\Category');
        $category = $model->load($categoryId);
        $displayMode = $category->getDisplayMode();
        $landingPage = $category->getlandingPage();
        $cmsIdentifer = '';
        if($landingPage) {
            $blockModel = $this->simiObjectManager->create('Magento\Cms\Model\Block')->load($landingPage);
            $cmsIdentifer = $blockModel->getIdentifier();
        }
        return [
            'display_mode' => $displayMode,
            'cms_identifier' => $cmsIdentifer,
        ];
    }
}
