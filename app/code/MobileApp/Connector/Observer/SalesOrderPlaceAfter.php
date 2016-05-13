<?php
/**
 * Created by PhpStorm.
 * User: trueplus
 * Date: 4/19/16
 * Time: 08:52
 */

namespace MobileApp\Connector\Observer;

use Magento\Framework\DataObject as Object;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderPlaceAfter implements ObserverInterface
{
    /**
     * Https request
     *
     * @var \Zend\Http\Request
     */
    protected $_request;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var app report
     */
    protected $_appreportFactory;

    /**
     * @param Item $item
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Sales\Model\OrderFactory $order,
        \MobileApp\Connector\Model\AppreportFactory $appreport
    ) {
        $this->_layout = $context->getLayout();
        $this->_request = $request;
        $this->_orderFactory = $order;
        $this->_appreportFactory = $appreport;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public
    function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $controllerModule = $this->_request->getControllerModule();
        if(strcasecmp($controllerModule,'MobileApp_Connector') == 0){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $orderId = $objectManager->create('\Magento\Checkout\Model\Session')
                ->getLastOrderId()+1;

            $app_report_model = $this->getAppReportModel();
            $app_report_model->setData('order_id',$orderId);
            $app_report_model->save();
        }

        return $this;
    }

    /**
     * @return Appreport Model
     */
    public function getAppReportModel(){
        return $this->_appreportFactory->create();
    }
}