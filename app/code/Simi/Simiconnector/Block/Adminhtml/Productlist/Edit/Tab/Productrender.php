<?php

namespace Simi\Simiconnector\Block\Adminhtml\Productlist\Edit\Tab;

class Productrender extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Checkboxes\Extended
{
    protected function _getCheckboxHtml($value, $checked)
    {
        $html = '<label class="data-grid-checkbox-cell-inner" ';
        $html .= ' for="id_' . $this->escapeHtml($value) . '">';
        $html .= '<input type="checkbox" ';
        $html .= 'name="' . $this->getColumn()->getFieldName() . '" ';
        $html .= 'value="' . $this->escapeHtml($value) . '" ';
        $html .= 'id="id_' . $this->escapeHtml($value) . '" ';
        //cody add js function
        $html .= 'onclick="selectProduct(this)"';
        //end
        $html .= 'class="' .
            ($this->getColumn()->getInlineCss() ? $this->getColumn()->getInlineCss() : 'checkbox') .
            ' admin__control-checkbox' . '"';
        $html .= $checked . $this->getDisabled() . '/>';
        $html .= '<label for="id_' . $this->escapeHtml($value) . '"></label>';
        $html .= '</label>';
        return $html;
    }
}