<?php

namespace MobileApp\Connector\Controller\Config\Get\Cms;

class Pages extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $collection = $this->_objectManager->create('MobileApp\Connector\Model\ResourceModel\Cms\Collection')
            ->addFieldToFilter('cms_status', 1);

        $pages = [];
        foreach($collection as $item){
            $pages[] = [
                'title' => $item->getCmsTitle(),
                'content' => $item->getCmsContent(),
                'icon' => $this->_getImageUrl($item->getCmsImage()),

            ];
        }

        $outputData = ['data' => $pages, 'status' => 'SUCCESS', 'message' => ['SUCCESS']];

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
