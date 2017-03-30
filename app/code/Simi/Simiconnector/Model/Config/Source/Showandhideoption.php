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

class Showandhideoption implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 4, 'label' => __('Required')],
            ['value' => 3, 'label' => __('Optional')], ['value' => 0, 'label' => __('Hide')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [4 => __('Required'), 3 => __('Optional'), 0 => __('Hide')];
    }
}
