<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

class Simiproductlabel extends \Simi\Simiconnector\Helper\Data
{

    public function getPositionId()
    {
        return [
            1 => __('Top-left'),
            2 => __('Top-center'),
            3 => __('Top-right'),
            4 => __('Middle-left'),
            5 => __('Middle-center'),
            6 => __('Middle-right'),
            7 => __('Bottom-left'),
            8 => __('Bottom-center'),
            9 => __('Bottom-right'),
        ];
    }

    public function getPositionOptions()
    {
        return [
            ['value' => 1, 'label' => __('Top-left')],
            ['value' => 2, 'label' => __('Top-center')],
            ['value' => 3, 'label' => __('Top-right')],
            ['value' => 4, 'label' => __('Middle-left')],
            ['value' => 5, 'label' => __('Middle-center')],
            ['value' => 6, 'label' => __('Middle-right')],
            ['value' => 7, 'label' => __('Bottom-left')],
            ['value' => 8, 'label' => __('Bottom-center')],
            ['value' => 9, 'label' => __('Bottom-right')],
        ];
    }

    public function getProductLabel($product)
    {
        if ($this->getStoreConfig('simiconnector/productlabel/enable') != '1') {
            return;
        }

        foreach ($this->simiObjectManager->get('Simi\Simiconnector\Model\Simiproductlabel')
                ->getCollection()->setOrder('priority', 'DESC') as $productLabel) {
            if ($productLabel->getData('status') != 1) {
                continue;
            }
            if ($productLabel->getData('storeview_id') != $this->storeManager->getStore()->getId()) {
                continue;
            }
            foreach (explode(',', str_replace(' ', '', $productLabel->getData('product_ids'))) as $productId) {
                if ($product->getId() == $productId) {
                    return [
                        'name'        => $productLabel->getData('name'),
                        'label_id'    => $productLabel->getData('label_id'),
                        'description' => $productLabel->getData('description'),
                        'text'        => $productLabel->getData('text'),
                        'image'       => $this->storeManager->getStore()
                            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                            . $productLabel->getData('image'),
                        'position'    => $productLabel->getData('position'),
                    ];
                }
            }
        }
    }
}
