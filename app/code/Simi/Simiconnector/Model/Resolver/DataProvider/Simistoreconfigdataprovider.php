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
class Simistoreconfigdataprovider extends DataProviderInterface
{
    /**
     * Get store config data
     *
     * @return array
     */
    public function getSimiStoreConfigData($args): array
    {
        $storeApi = $this->simiObjectManager->get('Simi\Simiconnector\Model\Api\Storeviews');
        $params = array();
        if ($args) {
            $params = $args;
        }
        $data = array(
            'resource' => 'storeviews',
            'resourceid' => ($args && isset($args['storeId']))?$args['storeId']:'default',
            'is_method' => 1,
            'params' => $params,
        );
        $storeApi->setData($data);
        $storeApi->setSingularKey('storeviews');
        return array(
            'config_json' => json_encode($storeApi->show())
        );
    }
}
