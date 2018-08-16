<?php

/**
 * Created by PhpStorm.
 * User: codynguyen
 * Date: 8/16/18
 * Time: 9:01 AM
 */


namespace Simi\Simiconnector\Model\Resolver\DataProvider;

use Simi\Simiconnector\Model\Resolver\DataProvider\DataProviderInterface;

class Homecategory extends DataProviderInterface
{
    public function getList() {
        return $this->simiObjectManager
            ->get('Simi\Simiconnector\Model\Simicategory')
            ->getCollection();
    }
}