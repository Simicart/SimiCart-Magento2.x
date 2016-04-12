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

class Device extends AbstractRenderer implements RendererInterface
{
    /** @var \MobileApp\Connector\Helper\Data */
    protected $_dataHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \MobileApp\Connector\Helper\Data $dataHelper
    ) {
        $this->_dataHelper = $dataHelper;
    }

    /**
     * Renders grid column
     *
     * @param Object $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return $this->_dataHelper->getNameDeviceById($this->_getValue($row));
    }
}