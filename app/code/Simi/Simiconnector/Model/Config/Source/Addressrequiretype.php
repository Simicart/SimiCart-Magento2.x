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

class Addressrequiretype implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 1, 'label' => __('Required')],
            ['value' => 2, 'label' => __('Optional')], ['value' => 3, 'label' => __('Hide')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [ 1, 'label' => __('Required'), 2 => __('Optional'), 3 => __('Hide')];
    }
}
