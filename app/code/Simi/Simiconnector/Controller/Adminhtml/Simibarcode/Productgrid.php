<?php

namespace Simi\Simiconnector\Controller\Adminhtml\Simibarcode;

class Productgrid extends \Magento\Catalog\Controller\Adminhtml\Product
{

    public $resultLayoutFactory;

    /**
     * @var Product\Builder
     */
    public $productBuilder;

    /**
     * @param Action\Context $context
     * @param Product\Builder $productBuilder
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
    ) {
   
        parent::__construct($context, $productBuilder);
        $this->resultLayoutFactory = $resultLayoutFactory;
    }

    public function execute()
    {
        $this->productBuilder->build($this->getRequest());
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->getLayout()->getBlock('simiconnector.simibarcode.edit.tab.productgrid')
                ->setProducts($this->getRequest()->getPost('products', null));
        return $resultLayout;
    }
}
