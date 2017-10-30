<?php

namespace Simi\Simiconnector\Block\Adminhtml\Simibarcode;

/**
 * Admin Simiconnector page
 *
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
   
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize cms page edit block
     *
     * @return void
     */
    public function _construct()
    {
        $this->_objectId   = 'barcode_id';
        $this->_blockGroup = 'Simi_Simiconnector';
        $this->_controller = 'adminhtml_simibarcode';

        parent::_construct();

        if ($this->_isAllowedAction('Simi_Simiconnector::save')) {
            if ($this->getRequest()->getParam('barcode_id')) {
                $this->buttonList->update('save', 'label', __('Save'));
                $this->buttonList->add(
                    'saveandcontinue',
                    [
                    'label'          => __('Save and Continue Edit'),
                    'class'          => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                        ],
                    -100
                );
            } else {
                $this->buttonList->update('save', 'label', __('Create Codes With Selected Products'));
            }
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Simi_Simiconnector::simiconnector_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete'));
        } else {
            $this->buttonList->remove('delete');
        }
        $this->buttonList->remove('reset');
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($this->coreRegistry->registry('simibarcode')->getId()) {
            return __("Edit Code '%1'", $this->escapeHtml($this->coreRegistry->registry('simibarcode')->getId()));
        } else {
            return __('New Code');
        }
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

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    public function _getSaveAndContinueUrl()
    {
        return $this->getUrl('simiconnector/*/save', ['_current' => true,
            'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }

    /**
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function _prepareLayout()
    {
        $arrow_down_img = $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png');
        $arrow_up_img   = $this->getViewFileUrl('Simi_Simiconnector::images/arrow_up.png');

        $productJsUpdateFunction = '
                    function selectProduct(e) {
                        var vl = e.value;
                        if(e.checked == true){
                            if($("product_ids").value == "")
                                $("product_ids").value = e.value;
                            else {
                                var selectedProducts = $("product_ids").value.replace(/ /g,"").split(",");
                                if (!selectedProducts.includes(e.value)) {
                                    selectedProducts.push(e.value);
                                    $("product_ids").value = selectedProducts.join(",");
                                }
                            }
                        }else{
                            var selectedProducts = $("product_ids").value.replace(/ /g,"").split(",");
                                index = selectedProducts.indexOf(e.value);
                                if (index > -1) {
                                    selectedProducts.splice(index, 1);
                                    $("product_ids").value = selectedProducts.join(",");
                                }
                        }
                    }
                   

                    function checkboxAllChecked(el){
                        var product_grid_trs = document.querySelectorAll(".admin__control-checkbox");
                        for (var i=1; i< product_grid_trs.length; i++) {
                            var e = product_grid_trs[i];
                            if (e.id != "checkall_simibarcode")
                                e.checked = el.checked;
                        }
                    }
                   
                    function toogleCheckAllProduct(){
                        var product_grid_trs = document.querySelectorAll(".admin__control-checkbox");
                        var el = product_grid_trs[0];
                        if(el.checked == true){
                            for (var i=1; i< product_grid_trs.length; i++) {
                                var e = product_grid_trs[i];
                                selectProduct(e);
                            }
                        }else{
                            for (var i=1; i< product_grid_trs.length; i++) {
                                var e = product_grid_trs[i];
                                selectProduct(e);
                            }
                        }
                    }
                   
                    var usingCodeType;
                   
                    function updateBarcodeValue() {
                        usingCodeType = $("barcode_type").value;
                    }
                   
                    function updateBarcodePresent() {
                        var currentSRC = $("simi-barcode-present").src;
                        var newsrc = currentSRC.replace(usingCodeType, $("barcode_type").value);
                        $("simi-barcode-present").src = newsrc;
                    }
                   
        ';

        $this->_formScripts[] = $productJsUpdateFunction . "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('page_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'page_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'page_content');
                }
            };

            document.addEventListener('DOMContentLoaded', function(){
                // event change Type
                changeType();

                // default: hide product grid
                document.getElementById('product_grid').style.display = 'none';
               
            }, false);
           

            function toogleProduct(){
                var product_grid = document.getElementById('product_grid');
                var product_choose_img = document.getElementById('show_product_grid');

                if(product_grid.style.display == 'none'){
                    product_grid.style.display = 'block';
                    product_choose_img.src = '$arrow_up_img';
                } else {
                    product_grid.style.display = 'none';
                    product_choose_img.src = '$arrow_down_img';
                }
            }

        ";
        return parent::_prepareLayout();
    }
}
