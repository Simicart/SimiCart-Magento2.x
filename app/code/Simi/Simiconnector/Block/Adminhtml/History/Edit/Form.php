<?php

namespace Simi\Simiconnector\Block\Adminhtml\History\Edit;

/**
 * Adminhtml connector edit form block
 *
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * Prepare form
     *
     * @return $this
     */
    public function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'),
                'method' => 'post', 'enctype' => 'multipart/form-data']]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
