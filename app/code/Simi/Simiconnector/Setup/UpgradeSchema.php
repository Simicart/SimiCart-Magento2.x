<?php

/**
 * Copyright © 2018 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        //handle all possible upgrade versions

        if(!$context->getVersion()) {
            //no previous version found, installation, InstallSchema was just executed
            //be careful, since everything below is true for installation !
        }

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            //code to upgrade to 1.0.1
        }

        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $tableName = $setup->getTable('simiconnector_transactions');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                $connection = $setup->getConnection();
                $connection->addColumn(
                    $tableName,
                    'platform',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,'nullable' => false,
                        'default' => '0',
                        'COMMENT' => 'Order made from']
                );
            }
        }

        $setup->endSetup();
    }
}