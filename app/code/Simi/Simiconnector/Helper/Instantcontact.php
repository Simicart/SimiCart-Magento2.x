<?php

/**
 * Connector data helper
 */

namespace Simi\Simiconnector\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Instantcontact extends \Simi\Simiconnector\Helper\Data
{

    public function getConfig($value)
    {
        return $this->getStoreConfig("simiconnector/instant_contact/" . $value);
    }

    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    public function isEnabled()
    {
        if ($this->getConfig('enable') == 1) {
            return true;
        }
        return false;
    }

    public function getContacts()
    {
        $data = [
            'email'       => $this->_getEmails(),
            'phone'       => $this->_getPhoneNumbers(),
            'message'     => $this->_getMessageNumbers(),
            'website'     => $this->getConfig("website"),
            'style'       => $this->getConfig("style"),
            'activecolor' => $this->getConfig("icon_color")
        ];

        return $data;
    }

    public function _getPhoneNumbers()
    {
        return explode(",", str_replace(' ', '', $this->getConfig("phone")));
    }

    public function _getMessageNumbers()
    {
        return explode(",", str_replace(' ', '', $this->getConfig("message")));
    }

    public function _getEmails()
    {
        $emails = explode(",", str_replace(' ', '', $this->getConfig("email")));
        foreach ($emails as $index => $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                unset($emails[$index]);
            }
        }
        return $emails;
    }
}
