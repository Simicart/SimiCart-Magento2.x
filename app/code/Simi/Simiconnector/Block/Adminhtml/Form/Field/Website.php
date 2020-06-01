<?php

namespace Simi\Simiconnector\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class Website extends AbstractFieldArray
{
    protected function _prepareToRender()
    {
        $this->addColumn('contact_website', ['label' => __('Contact Website'), 'class' => 'required-entry', 'size' => '500px']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Website');
    }
}
