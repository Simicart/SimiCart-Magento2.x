<?php

/**
 * Adminhtml simiconnector list block
 *
 */

namespace Simi\Simiconnector\Block\Adminhtml;

class Simibarcode extends \Magento\Backend\Block\Widget\Grid\Container
{

    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller     = 'adminhtml_simibarcode';
        $this->_blockGroup     = 'Simi_Simiconnector';
        $this->_headerText     = __('barcode');
        $this->_addButtonLabel = __('Add New Custom QR & Barcode');
        parent::_construct();
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    public function _isAllowedAction($resourceId)
    {
        return true;
    }
}
