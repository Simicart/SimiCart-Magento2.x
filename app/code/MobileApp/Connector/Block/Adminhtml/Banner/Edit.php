<?php
namespace MobileApp\Connector\Block\Adminhtml\Banner;

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
    ) {
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

        $this->_objectId = 'banner_id';
        $this->_blockGroup = 'MobileApp_Connector';
        $this->_controller = 'adminhtml_banner';

        parent::_construct();

        if ($this->_isAllowedAction('MobileApp_Connector::save')) {
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

        if ($this->_isAllowedAction('MobileApp_Connector::connector_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete'));
        } else {
            $this->buttonList->remove('delete');
        }
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('banner')->getId()) {
            return __("Edit Banner '%1'", $this->escapeHtml($this->_coreRegistry->registry('banner')->getId()));
        } else {
            return __('New Banner');
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
        return $this->getUrl('connector/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }

    /**
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('page_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'page_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'page_content');
                }
            };

            document.addEventListener('DOMContentLoaded', function(){
                changeType();
            }, false);

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

                        document.querySelectorAll('.field-banner_url')[0].style.display = 'none';
                        document.querySelectorAll('#banner_url')[0].classList.remove('required-entry');
                        break;
                    case '2':
                        document.querySelectorAll('.field-product_id')[0].style.display = 'none';
                        document.querySelectorAll('#product_id')[0].classList.remove('required-entry');

                        document.querySelectorAll('.field-new_category_parent')[0].style.display = 'block';
                        document.querySelectorAll('#new_category_parent')[0].classList.add('required-entry');

                        document.querySelectorAll('.field-banner_url')[0].style.display = 'none';
                        document.querySelectorAll('#banner_url')[0].classList.remove('required-entry');
                        break;
                    case '3':
                        document.querySelectorAll('.field-product_id')[0].style.display = 'none';
                        document.querySelectorAll('#product_id')[0].classList.remove('required-entry');

                        document.querySelectorAll('.field-new_category_parent')[1].style.display = 'none';
                        document.querySelectorAll('.field-new_category_parent')[0].style.display = 'none';
                        document.querySelectorAll('#new_category_parent')[0].classList.remove('required-entry');
                        document.querySelectorAll('#new_category_parent')[1].classList.remove('required-entry');

                        document.querySelectorAll('.field-banner_url')[0].style.display = 'block';
                        document.querySelectorAll('#banner_url')[0].classList.add('required-entry');
                        break;
                    default:
                        document.querySelectorAll('.field-product_id')[0].style.display = 'block';
                        document.querySelectorAll('#product_id')[0].classList.add('required-entry');

                        document.querySelectorAll('.field-new_category_parent')[1].style.display = 'none';
                        document.querySelectorAll('.field-new_category_parent')[0].style.display = 'none';
                        document.querySelectorAll('#new_category_parent')[0].classList.remove('required-entry');
                        document.querySelectorAll('#new_category_parent')[1].classList.remove('required-entry');

                        document.querySelectorAll('.field-banner_url')[0].style.display = 'none';
                        document.querySelectorAll('#banner_url')[0].classList.remove('required-entry');
                }
            }
        ";
        return parent::_prepareLayout();
    }
}
