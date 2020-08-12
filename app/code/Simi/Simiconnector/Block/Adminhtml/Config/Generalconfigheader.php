<?php

namespace Simi\Simiconnector\Block\Adminhtml\Config;

class Generalconfigheader extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return <<<EOT
        <div>
<a href="https://www.simicart.com/">
    <img style="margin-bottom: 15px" src="https://www.simicart.com/media/wysiwyg/banner-linkedin.png" 
        alt="Magento PWA & Magento Mobile App | SimiCart">
</a>
    <div>&#128241; - For Native App Only</div>
    </div>
EOT;
    }
}
