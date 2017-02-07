<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

class Simivideo extends \Simi\Simiconnector\Helper\Data
{

    public function getProductVideo($product)
    {
        $videoCollection = $reviews         = $this->simiObjectManager
                ->create('Simi\Simiconnector\Model\Simivideo')->getCollection();
        if ($this->simiObjectManager->get('Simi\Simiconnector\Helper\Data')->countCollection($videoCollection) == 0) {
            return;
        }
        $productId       = $product->getId();
        if (!$productId) {
            return;
        }
        $videoArray      = [];
        foreach ($videoCollection as $video) {
            if (in_array($productId, explode(",", $video->getData('product_ids')))) {
                $videoArray[] = $video->getData('video_id');
            }
        }
        $collection  = $this->simiObjectManager
                ->create('Simi\Simiconnector\Model\Simivideo')->getCollection()
                ->addFieldToFilter('status', '1')->addFieldToFilter('video_id', ['in' => $videoArray]);
        $returnArray = [];
        foreach ($collection as $productVideo) {
            $returnArray[] = $productVideo->toArray();
        }
        return $returnArray;
    }
}
