<?php

namespace Simi\Simiconnector\Model\ResourceModel;

/**
 * Connector Resource Model
 */
class Simivideo extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('simiconnector_videos', 'video_id');
    }
}
