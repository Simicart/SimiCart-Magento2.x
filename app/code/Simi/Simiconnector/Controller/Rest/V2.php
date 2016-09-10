<?php
/**
 *
 * Copyright © 2016 Simicommerce. All rights reserved.
 */
namespace Simi\Simiconnector\Controller\Rest;

class V2 extends Action
{

	/**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    protected $_cacheState;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    protected $_cacheFrontendPool;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
    }
	
    /**
     * Flush cache storage
     *
     */
    public function execute()
    {
        parent::execute();
        ob_start();
        try{
            $result = $this->_getServer()
                ->init($this)->run();
            $this->_printData($result);
        }catch (\Exception $e){
            $result = array();
            $result['error'] = array(
                'code' => $e->getCode(),
                'message'=> $e->getMessage(),
            );
            $this->_printData($result);
        }
        exit();
        ob_end_flush();
        
    }
    /*
    public function execute()
    {
        $this->resultPage = $this->resultPageFactory->create();  
		return $this->resultPage;
        
    }
    */
}
