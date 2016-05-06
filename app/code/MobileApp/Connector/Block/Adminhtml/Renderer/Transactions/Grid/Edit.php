<?php
/**
 * Created by PhpStorm.
 * User: trueplus
 * Date: 4/8/16
 * Time: 08:51
 */

namespace MobileApp\Connector\Block\Adminhtml\Renderer\Transactions\Grid;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\RendererInterface;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

class Edit extends AbstractRenderer implements RendererInterface
{
    /**
     * @var https|http
     */
    protected $_request;

    /**
     * @var https|http
     */
    protected $_urlBuilder;


    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->_request = $request;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Renders grid column
     *
     * @param Object $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $url = $this->_urlBuilder->getUrl('sales/order/view', [
            'order_id' => $row->getOrderId()
        ]);
        return "<a href='$url'>Edit</a>";
    }
}