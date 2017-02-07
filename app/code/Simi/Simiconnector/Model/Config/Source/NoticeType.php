<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Used in creating options for Yes|No config value selection
 *
 */

namespace Simi\Simiconnector\Model\Config\Source;

class NoticeType implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => '1', 'label' => __('Product In-app')],
            ['value' => '2', 'label' => __('Category In-app')], ['value' => '3', 'label' => __('Website Page')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['1' => __('Product In-app'), '2' => __('Category In-app'), '3' => __('Website Page')];
    }
}
