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

class Platform implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => '0', 'label' => __('All')],
            ['value' => '1', 'label' => __('IOS')], ['value' => '2', 'label' => __('Android')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['0' => __('All'), '1' => __('IOS'), '2' => __('Android')];
    }
}
