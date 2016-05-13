<?php
/**
 * Created by PhpStorm.
 * User: trueplus
 * Date: 4/8/16
 * Time: 08:51
 */

namespace MobileApp\Connector\Block\Adminhtml\Renderer\Connector\Grid;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\RendererInterface;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;

class Edit extends AbstractRenderer implements RendererInterface
{
    /** @var \MobileApp\Connector\Helper\Data */
    protected $_websiteHelper;

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
        \MobileApp\Connector\Helper\Website $websiteHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->_websiteHelper = $websiteHelper;
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
        $webId = $this->_websiteHelper->getWebsiteIdFromUrl();
        $url = $this->_urlBuilder->getUrl('*/*/edit', [
            'id' => $row->getId(),
            'store' => $this->_request->getParam('store'),
            'device_id' => $row->getDeviceId(),
            'website_id' => $webId,
        ]);
        return "<a href='$url'>Edit</a>";
    }
}