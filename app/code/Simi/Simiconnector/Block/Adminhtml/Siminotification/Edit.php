<?php
namespace Simi\Simiconnector\Block\Adminhtml\Siminotification;

/**
 * Admin Connector page
 *
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize cms page edit block
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_objectId = 'notice_id';
        $this->_blockGroup = 'Simi_Simiconnector';
        $this->_controller = 'adminhtml_siminotification';

        parent::_construct();

        if ($this->_isAllowedAction('Simi_Simiconnector::save')) {
            $this->buttonList->update('save', 'label', __('Save'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Simi_Simiconnector::connector_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete'));
        } else {
            $this->buttonList->remove('delete');
        }

        $this->buttonList->update('save', 'label', __('Send'));
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('siminotification')->getId()) {
            return __("Edit Notification '%1'", $this->escapeHtml($this->_coreRegistry->registry('siminotification')->getId()));
        } else {
            return __('New Notification');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('simiconnector/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }

    /**
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        $arrow_down_img = $this->getViewFileUrl('Simi_Simiconnector::images/arrow_down.png');
        $arrow_up_img = $this->getViewFileUrl('Simi_Simiconnector::images/arrow_up.png');

        $this->_formScripts[] = "
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

                // default: hidden product grid
                document.getElementById('product_grid').style.display = 'none';
                
                // hide Device Grid
                document.getElementById('deviceGrid').style.display = 'none';

            }, false);

            document.body.addEventListener('click', function(e){
                var product_grid_trs = document.querySelectorAll('#product_grid_table tbody tr');
                var trElement;
                var radioArray = [];
                for (var i = 0, j = 0; i < product_grid_trs.length; i++) {
                    trElement = product_grid_trs.item(i);
                    trElement.addEventListener('click', function(e){
                        var rd = this.getElementsByTagName('input')[0];
                        rd.checked = true;
                        document.getElementById('product_id').value = rd.value;
                        return false;
                    });
                }

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

            function changeType(){
                var banner_type = document.getElementById('type').value;
                switch (banner_type) {
                    case '1':
                        document.querySelectorAll('.field-product_id')[0].style.display = 'block';
                        document.querySelectorAll('#product_id')[0].classList.add('required-entry');

                        document.querySelectorAll('.field-new_category_parent')[1].style.display = 'none';
                        document.querySelectorAll('.field-new_category_parent')[0].style.display = 'none';
                        document.querySelectorAll('#new_category_parent')[0].classList.remove('required-entry');
                        document.querySelectorAll('#new_category_parent')[1].classList.remove('required-entry');

                        document.querySelectorAll('.field-notice_url')[0].style.display = 'none';
                        document.querySelectorAll('#notice_url')[0].classList.remove('required-entry');
                        break;
                    case '2':
                        document.querySelectorAll('.field-product_id')[0].style.display = 'none';
                        document.querySelectorAll('#product_id')[0].classList.remove('required-entry');

                        document.querySelectorAll('.field-new_category_parent')[0].style.display = 'block';
                        document.querySelectorAll('#new_category_parent')[0].classList.add('required-entry');

                        document.querySelectorAll('.field-notice_url')[0].style.display = 'none';
                        document.querySelectorAll('#notice_url')[0].classList.remove('required-entry');
                        break;
                    case '3':
                        document.querySelectorAll('.field-product_id')[0].style.display = 'none';
                        document.querySelectorAll('#product_id')[0].classList.remove('required-entry');

                        document.querySelectorAll('.field-new_category_parent')[1].style.display = 'none';
                        document.querySelectorAll('.field-new_category_parent')[0].style.display = 'none';
                        document.querySelectorAll('#new_category_parent')[0].classList.remove('required-entry');
                        document.querySelectorAll('#new_category_parent')[1].classList.remove('required-entry');

                        document.querySelectorAll('.field-notice_url')[0].style.display = 'block';
                        document.querySelectorAll('#notice_url')[0].classList.add('required-entry');
                        break;
                    default:
                        document.querySelectorAll('.field-product_id')[0].style.display = 'block';
                        document.querySelectorAll('#product_id')[0].classList.add('required-entry');

                        document.querySelectorAll('.field-new_category_parent')[1].style.display = 'none';
                        document.querySelectorAll('.field-new_category_parent')[0].style.display = 'none';
                        document.querySelectorAll('#new_category_parent')[0].classList.remove('required-entry');
                        document.querySelectorAll('#new_category_parent')[1].classList.remove('required-entry');
                        
                        document.querySelectorAll('.field-notice_url')[0].style.display = 'none';
                        document.querySelectorAll('#notice_url')[0].classList.remove('required-entry');
                }
            }
            function toogleDevice(){
                var device_grid = document.getElementById('deviceGrid');
                var device_choose_img = document.getElementById('show_device_grid');

                if(device_grid.style.display == 'none'){
                    device_grid.style.display = 'block';
                    device_choose_img.src = '$arrow_up_img';
                } else {
                    device_grid.style.display = 'none';
                    device_choose_img.src = '$arrow_down_img';
                }
            }
        ";
        return parent::_prepareLayout();
    }
}
