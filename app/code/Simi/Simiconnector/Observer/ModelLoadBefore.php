<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/29/18
 * Time: 9:28 PM
 */

namespace Simi\Simiconnector\Observer;

use Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;

class ModelLoadBefore implements ObserverInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface as ObjectManager
     */
    private $simiObjectManager;

    public function __construct(
        ObjectManager $simiObjectManager,
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->request = $request;
        $this->simiObjectManager = $simiObjectManager;
    }

    public function execute(Observer $observer)
    {
        //only un-command while form_key adding at Simi\Simiconnector\Controller\Rest\Action:31 is not working
        /*
        try {
            $request = $this->request;
            if ($request && $request->isPost() && !$request->getParam('form_key')) {
                $uri = $request->getRequestUri();
                if ((strpos($uri, 'simi') === false) || (strpos($uri, '/rest/v2/') === false))
                    return;
                $formKey = $this->simiObjectManager->get('\Magento\Framework\Data\Form\FormKey')->getFormKey();
                $request->setParam('form_key', $formKey);
            }
        } catch (\Exception $e) {

        }
        */
    }

}
