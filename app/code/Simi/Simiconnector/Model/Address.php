<?php

namespace Simi\Simiconnector\Model;

/**
 * Simiconnector Model
 *
 * @method \Simi\Simiconnector\Model\Resource\Page _getResource()
 * @method \Simi\Simiconnector\Model\Resource\Page getResource()
 */
class Address extends \Magento\Framework\Model\AbstractModel
{
    
    protected $_objectManager;
    protected $_storeManager;



    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
    
    protected function _getSession()
    {
        return $this->_objectManager->get('Magento\Customer\Model\Session');
    }

    protected function _helperAddress()
    {
        return $this->_objectManager->get('Simi\Simiconnector\Helper\Address');
    }

    /*
     * Save Customer Address
     */
    public function saveAddress($data)
    {
        $data = $data['contents'];
        $address = $this->_helperAddress()->convertDataAddress($data);
        $address['id'] = isset($data->entity_id) == true ? $data->entity_id : null;
        return $this->saveAddressCustomer($address);
    }

    public function saveAddressCustomer($data)
    {
        $errors = false;
        $customer = $this->_getSession()->getCustomer();
        $address = $this->_objectManager->create('Magento\Customer\Model\Address');
        $addressId = $data['id'];
        $address->setData($data);

        if ($addressId && $addressId != '') {
            $existsAddress = $customer->getAddressById($addressId);
            if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
                $address->setId($existsAddress->getId());
            }
        } else {
            $address->setId(null);
        }

        $addressForm = $this->_objectManager->get('Magento\Customer\Model\Form');
        $addressForm->setFormCode('customer_address_edit')
                ->setEntity($address);
        $addressForm->compactData($data);
        $address->setCustomerId($customer->getId());
        $addressErrors = $address->validate();
        if ($addressErrors !== true) {
            $errors = true;
        }
        if (!$errors) {
            $address->save();
            return $address;
        } else {
            if (is_array($addressErrors)) {
                throw new \Exception($addressErrors[0], 7);
            }
            throw new \Exception(__('Can not save address customer'), 7);
        }
    }
}
