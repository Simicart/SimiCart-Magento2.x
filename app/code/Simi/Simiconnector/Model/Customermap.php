<?php

namespace Simi\Simiconnector\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Simi\Simiconnector\Model\ResourceModel\Customermap as CustomermapRM;
use Simi\Simiconnector\Model\ResourceModel\Customermap\Collection;

class Customermap extends AbstractModel
{
    public $simiObjectManager;

    /**
     * /**
     * Device constructor.
     * @param Context $context
     * @param ObjectManagerInterface $simiObjectManager
     * @param Registry $registry
     * @param DeviceRM $resource
     * @param Collection $resourceCollection
     * @param Website $websiteHelper
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $simiObjectManager,
        Registry $registry,
        CustomermapRM $resource,
        Collection $resourceCollection
    ) {


        $this->simiObjectManager = $simiObjectManager;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection
        );
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Simi\Simiconnector\Model\ResourceModel\Customermap');
    }


    public function createCustomer($params)
    {
        $email = isset($params['email']) ? $params['email'] : $params['uid'] . $params['providerId'] . '@simisocial.com';
        $firstName = isset($params['firstname']) ? $params['firstname'] : ' ';
        $lastName = isset($params['lastname']) ? $params['lastname'] : ' ';
        $existedCustomer = $this->simiObjectManager->create('Magento\Customer\Model\Customer')->getCollection()
            ->addFieldToFilter('email', $email)
            ->getFirstItem();
        if ($existedCustomer->getId())
            throw new \Simi\Simiconnector\Helper\SimiException(__('Cannot create new customer account'), 4);

        $customer = $this->simiObjectManager->create('Magento\Customer\Model\Customer')
            ->setFirstname($firstName)
            ->setLastname($lastName)
            ->setEmail($email);

        $encodeMethod = 'md5';
        $password = 'simipassword'
            . rand(pow(10, 9), pow(10, 10)) . substr($encodeMethod(microtime()), rand(0, 26), 5);

        if (isset($params['hash']) && $params['hash'] !== '') {
            $password = $params['hash'];
        }

        $customer->setPassword($password);
        $customer->save();

        $dataMap = array(
            'customer_id' => $customer->getId(),
            'social_user_id' => $params['uid'],
            'provider_id' => $params['providerId']
        );

        $this->setData($dataMap)->save();
        return $customer;
    }

    public function checkOldCustomer($params)
    {
        $encodeMethod = 'md5';

        //create pass from email
        $createPassFromEmail = '';
        $email = $params->email;
        $secretKey = $this->simiObjectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('simiconnector/general/secret_key');

        $createPassFromEmail = 'Simi123a@' . $encodeMethod($secretKey . $email);

        //so sanh pass
        if ($createPassFromEmail == 'Simi123a@' . $encodeMethod($this->simiObjectManager
            ->get('Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue('simiconnector/general/secret_key') . $email)) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * @params - array [providerId, uid, email (opt.), firstname (opt.), lastname (opt.), hash (opt.)]
     */
    public function getCustomer($params)
    {
        $providerId = $params['providerId'];
        $uid = $params['uid'];
        $customerMap = $this->getCollection()
            ->addFieldToFilter('provider_id', array('eq' => $providerId))
            ->addFieldToFilter('social_user_id', array('eq' => $uid))
            ->getFirstItem();

        if ($customerMap->getId()) {
            return $this->simiObjectManager->create('Magento\Customer\Model\Customer')->load($customerMap->getCustomerId());
        } else {
            return $this->createCustomer($params);
        }
    }
}
