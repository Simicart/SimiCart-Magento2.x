<?php

namespace MobileApp\Connector\Controller\Config\Get;

class Categories extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $collection = $this->_objectManager->create('MobileApp\Connector\Model\ResourceModel\Simicategory\Collection')
            ->addFieldToFilter('status', 1);

        $banners = [];
        foreach($collection as $item){
            $category = $this->_objectManager
                ->create('Magento\Catalog\Model\Category')
                ->load($item->getCategoryId());

            $banners[] = [
                'image_path' => $this->_getImageUrl($item->getBannerName()),
                'url' => $item->getBannerUrl(),
                'type' => $item->getType(),
                'categoryID' => $item->getCategoryId(),
                'categoryName' => $category->getName(),
                'productID' => $item->getProductId(),
                'has_child' => $category->hasChildren()
            ];
        }

        $outputData = ['data' => $banners, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];

        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
    }

    /*
     * Get image url
     *
     * @param $path string
     * @return string
     */
    protected function _getImageUrl($path){
         return $this->storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$path;
    }
}
