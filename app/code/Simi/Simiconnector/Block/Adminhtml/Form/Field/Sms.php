<?php

namespace Simi\Simiconnector\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class Sms extends AbstractFieldArray
{
    protected function _prepareToRender()
    {
        $this->addColumn('contact_sms', ['label' => __('Phonenumber receive SMS'), 'class' => 'required-entry', 'size' => '500px']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Phone Number');
    }
}
