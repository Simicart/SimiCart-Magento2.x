<?php

namespace MobileApp\Connector\Controller\Checkout\Update\Paypal;

class Payment extends \MobileApp\Connector\Controller\Connector
{
    /**
     *
     * @return void
     */
    public function execute()
    {
        $params = $this->_getParams();
        $status = $params['payment_status'];
        $orderId = $params['invoice_number'];

        $order = $this->_objectManager
            ->create('Magento\Sales\Model\Order')
            ->loadByIncrementId($orderId);
        if($status == 2){
            $order->cancel();
        }else{
            /* @var $invoice \Magento\Sales\Model\Order\Invoice */
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $invoice->save();
            $transaction = $this->_objectManager
                ->create('Magento\Framework\DB\Transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transaction->save();
            $this->_objectManager
                ->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender')
                ->send($invoice);

            //send notification code
            $order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getId()))
                ->setIsCustomerNotified(true)
                ->save();
        }

        $outputData = ['status' => 'SUCCESS', 'message' => ['SUCCESS']];

        /** @param \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        return $result->setData($outputData);
    }
}
