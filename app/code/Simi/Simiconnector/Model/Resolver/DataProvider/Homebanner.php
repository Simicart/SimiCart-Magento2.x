<?php

/**
 * Created by PhpStorm.
 * User: codynguyen
 * Date: 8/16/18
 * Time: 9:01 AM
 */


namespace Simi\Simiconnector\Model\Resolver\DataProvider;

use Simi\Simiconnector\Model\Resolver\DataProvider\DataProviderInterface;

class Homebanner extends DataProviderInterface
{
    public function getListData() {
        $apiModel =  $this->simiObjectManager
            ->get('Simi\Simiconnector\Model\Api\Homebanners');
        $apiModel->setData(array('resourceid'=> null, 'params'=>array()));
        $apiModel->setBuilderQuery();
        $apiModel->setPluralKey('homebanners');
        $apiModel->setSingularKey('homebanner');
        return $apiModel->index();
    }
}