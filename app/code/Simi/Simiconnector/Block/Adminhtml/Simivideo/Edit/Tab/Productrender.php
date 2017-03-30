<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simivideo\Edit\Tab;

class Productrender extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Checkboxes\Extended
{

    public function _getCheckboxHtml($value, $checked)
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

    /**
     * Renders header of the column
     *
     * @return string
     */
    public function renderHeader()
    {
        if ($this->getColumn()->getHeader()) {
            return parent::renderHeader();
        }

        $checked = '';
        if ($filter  = $this->getColumn()->getFilter()) {
            $checked = $filter->getValue() ? ' checked="checked"' : '';
        }

        $disabled = '';
        if ($this->getColumn()->getDisabled()) {
            $disabled = ' disabled="disabled"';
        }
        $html = '<th class="data-grid-th data-grid-actions-cell"><input type="checkbox" ';
        $html .= 'id="checkall_simivideo" ';
        $html .= 'name="' . $this->getColumn()->getFieldName() . '" ';
        $html .= 'onclick="checkboxAllChecked(this); toogleCheckAllProduct();"';
        $html .= 'class="admin__control-checkbox"' . $checked . $disabled . ' ';
        $html .= 'title="' . __('Select All') . '"/><label></label></th>';
        return $html;
    }
}
