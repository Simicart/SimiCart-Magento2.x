<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 5/28/18
 * Time: 5:39 PM
 */

namespace Simi\Simiconnector\Model\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;

class Contacts extends Apiabstract
{
    public function setBuilderQuery()
    {
        // TODO: Implement setBuilderQuery() method.
    }

    public function store()
    {
        $email = $this->simiObjectManager->get('Magento\Contact\Model\MailInterface');
        $DataPersistor = $this->simiObjectManager->get('Magento\Framework\App\Request\DataPersistorInterface');
        $data = $this->getData();
        $params = $data['contents_array'];
        $dataMail = $this->validatedParams($params);
        try {
            $this->sendEmail($email, $dataMail);
            $DataPersistor->clear('contact_us');
            return [
                'success' => '1',
                'message' => __('Thanks for contacting us with your comments and questions. We\'ll respond to you very soon.')
            ];
        } catch (LocalizedException $e) {
            $DataPersistor->set('contact_us', $params);
            throw new \Exception($e->getMessage(), 4);
        } catch (\Exception $exception) {
            $DataPersistor->set('contact_us', $params);
            throw new \Exception(__('An error occurred while processing your form. Please try again later.'), 4);
        }
    }

    private function sendEmail($email, $data)
    {
        $email->send(
            $data['email'],
            ['data' => new DataObject($data)]
        );
    }

    private function validatedParams($data)
    {
        if (!isset($data['name']) || $data['name'] === '') {
            throw new LocalizedException(__('Name is missing'));
        }
        if (!isset($data['message']) || $data['message'] === '') {
            throw new LocalizedException(__('Comment is missing'));
        }
        if (false === \strpos($data['email'], '@')) {
            throw new LocalizedException(__('Invalid email address'));
        }
        return $data;
    }
}