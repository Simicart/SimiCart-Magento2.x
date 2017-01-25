<?php

/**
 * Connector data helper
 */
namespace Simi\Simiconnector\Helper;


class Simivideo extends \Simi\Simiconnector\Helper\Data
{
    public function getProductVideo($product) {
        $videoCollection = $reviews = $this->_objectManager->create('Simi\Simiconnector\Model\Simivideo')->getCollection();
        if ($videoCollection->count() == 0)
            return;
        $productId = $product->getId();
        if (!$productId)
            return;
        $videoArray = array();
        foreach ($videoCollection as $video) {
            if (in_array($productId, explode(",", $video->getData('product_ids')))) {
                $videoArray[] = $video->getData('video_id');
            }
        }
        $collection = $this->_objectManager->create('Simi\Simiconnector\Model\Simivideo')->getCollection()->addFieldToFilter('status', '1')->addFieldToFilter('video_id', array('in' => $videoArray));
        $returnArray = array();
        foreach ($collection as $productVideo) {
            $returnArray[] = $productVideo->toArray();
        }
        return $returnArray;
    }
}