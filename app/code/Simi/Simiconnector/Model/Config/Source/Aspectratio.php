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

class Aspectratio implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 1, 'label' => __('Letter Box (display wide image with blank stripes on top and bottom)')],
            ['value' => 2, 'label' => __('Pan & Scan (display full height image and got cropped by 2 sides)')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [1 => __('Letter Box (display wide image with blank stripes on top and bottom)'),
            2 => __('Pan & Scan (display full height image and got cropped by 2 sides)')];
    }
}
