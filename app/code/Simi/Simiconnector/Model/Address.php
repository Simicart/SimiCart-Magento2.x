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

    public $simiObjectManager;
    public $storeManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $simiObjectManager,
        array $data = [],
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->storeManager     = $this->simiObjectManager->get('Magento\Store\Model\StoreManagerInterface');
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function _getSession()
    {
        return $this->simiObjectManager->get('Magento\Customer\Model\Session');
    }

    public function _helperAddress()
    {
        return $this->simiObjectManager->get('Simi\Simiconnector\Helper\Address');
    }

    /*
     * Save Customer Address
     */

    public function saveAddress($data)
    {
        $data          = $data['contents'];
        $address       = $this->_helperAddress()->convertDataAddress($data);
        $address['id'] = isset($data->entity_id) == true ? $data->entity_id : null;
        return $this->saveAddressCustomer($address);
    }

    public function saveAddressCustomer($data)
    {
        $errors    = false;
        $customer  = $this->_getSession()->getCustomer();
        $address   = $this->simiObjectManager->create('Magento\Customer\Model\Address');
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

        $addressForm   = $this->simiObjectManager->get('Magento\Customer\Model\Form');
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
                throw new \Simi\Simiconnector\Helper\SimiException($addressErrors[0], 7);
            }
            throw new \Simi\Simiconnector\Helper\SimiException(__('Can not save address customer'), 7);
        }
    }
}
