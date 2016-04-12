<?php
namespace MobileApp\Connector\Block\Adminhtml\Connector\Edit\Tab;


use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;

/**
 * connector edit form main tab
 */
class Pluginlist extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Webkul\Hello\Model\GridFactory
     */
    protected $productFactory;

    /**
     * @var \MobileApp\Connector\Model\ResourceModel\Plugin\CollectionFactory
     */
    protected $_pluginCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Registry $coreRegistry,
        \MobileApp\Connector\Model\ResourceModel\Plugin\CollectionFactory $pluginCollectionFactory,
        array $data = []
    ) {

        $this->productFactory = $productFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_pluginCollectionFactory = $pluginCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('plugingrid_tab_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    /**
     * @return Grid
     */
    protected function _prepareCollection()
    {
        $device_id = $this->getRequest()->getParam('device_id');
        $website_id = $this->getRequest()->getParam('website');

        $collection = $this->_pluginCollectionFactory->create();
        $collection->addFieldToFilter('website_id', array('eq' => $website_id));
        $collection->addFieldToFilter('device_id', array('eq' => $device_id));

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'plugin_name',
            [
                'header' => __('Plugin Name'),
                'sortable' => true,
                'index' => 'plugin_name',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'expired_time',
            [
                'header' => __('Expiration Date'),
                'index' => 'expired_time',
                'type' => 'datetime',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );


        $this->addColumn(
            'plugin_status',
            [
                'header' => __('Status'),
                'type' => 'options',
                'options' => array(
                                0 => 'Expired',
                                1 => 'Enabled',
                                2 => 'Disabled',
                                3 => 'Trial'
                            ),
                'sortable' => true,
                'index' => 'plugin_status',
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
//        return $this->getUrl('connector/*/product', ['_current' => true]);
    }

}
