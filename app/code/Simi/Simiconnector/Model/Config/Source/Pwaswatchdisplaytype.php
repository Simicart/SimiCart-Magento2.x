<?php

namespace Simi\Simiconnector\Model\Config\Source;

class Pwaswatchdisplaytype implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 1, 'label' => __('Color swatch')], ['value' => 0, 'label' => __('Text')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('Text'), 1 => __('Color swatch')];
    }
}
