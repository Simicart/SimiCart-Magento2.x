<?php
namespace MobileApp\Connector\Block\Adminhtml\Banner\Edit\Tab;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Productgrid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    protected
    $_coreRegistry = null;


    protected
    $_productFactory;

    public
    function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
//        \Company\Module\Model\yourFactory $yourFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    )
    {

        $this->_productFactory = $productFactory;
//        $this->_yourFactory = $yourFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }


    protected
    function _construct()
    {
        parent::_construct();
        $this->setId('product_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);

    }


    protected
    function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }


    protected
    function _prepareCollection()
    {
        $collection = $this->_productFactory->create()->getCollection()->addAttributeToSelect(
            '*'
        );
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected
    function _prepareColumns()
    {

        $this->addColumn(
            'in_products',
            [
                'type' => 'radio',
                'html_name' => 'products_id',
                'required' => true,
                'values' => $this->_getSelectedProducts(),
                'align' => 'center',
                'index' => 'entity_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );


        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );


        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'col-sku'
            ]
        );


        return parent::_prepareColumns();
    }


    public
    function getGridUrl()
    {
        return $this->_getData(
            'grid_url'
        ) ? $this->_getData(
            'grid_url'
        ) : $this->getUrl(
            'connector/*/productgrid',
            ['_current' => true]
        );
    }


    protected
    function _getSelectedProducts()
    {

        $products = array_keys($this->getSelectedProducts());

        return $products;
    }


    public
    function getSelectedProducts()
    {
        $tm_id = $this->getRequest()->getParam('id');
        if (!isset($tm_id)) {
            $tm_id = 0;
        }

        // if you save product id in your custom table

//        $collection = $this->_yourFactory->create()->load($tm_id);
//        $data = $collection->getProductId();
//        $products = explode(',', $data);

        $products = array(3);

        $proIds = array();

        foreach ($products as $product) {
            $proIds[$product] = array('id' => $product);
        }

        return $proIds;
    }


}