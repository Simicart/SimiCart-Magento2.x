<?php

/**
 * Copyright © 2016 Simi. All rights reserved.
 */

namespace Simi\Simiconnector\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installSql($setup, $context);
    }
    
    public function installSql(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $context;
        $installer = $setup;
        $installer->startSetup();

        /**
         * Creating table simicategory
         */
        $table_key_name = $installer->getTable('simiconnector_simicategory');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_simicategory');
        $table_key = $installer->getConnection()->newTable(
            $table_key_name
        )->addColumn(
            'simicategory_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Cat Id'
        )->addColumn(
            'simicategory_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Cat Name'
        )->addColumn(
            'simicategory_filename',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'File Name'
        )->addColumn(
            'simicategory_filename_tablet',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'File name tablet'
        )->addColumn(
            'category_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true, 'unsigned' => true],
            'Category Id'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true, 'unsigned' => true],
            'status'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true, 'unsigned' => true],
            'Web Id'
        )->addColumn(
            'storeview_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Storeview Id'
        )->addColumn(
            'sort_order',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Sort Order'
        )->addColumn(
            'matrix_width_percent',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Width Percent'
        )->addColumn(
            'matrix_height_percent',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Height Percent'
        )->addColumn(
            'matrix_width_percent_tablet',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Width Percent Tab'
        )->addColumn(
            'matrix_height_percent_tablet',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Height Percent Tab'
        )->addColumn(
            'matrix_row',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Rownum'
        );
        $installer->getConnection()->createTable($table_key);
        // end create table simicategory

        /**
         * Creating table simiconnector_banner
         */
        $table_key_name = $installer->getTable('simiconnector_banner');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_banner');
        $table_key = $installer->getConnection()->newTable(
            $table_key_name
        )->addColumn(
            'banner_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Banner Id'
        )->addColumn(
            'banner_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Name'
        )->addColumn(
            'banner_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Banner URL'
        )->addColumn(
            'banner_name_tablet',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'image tablet'
        )->addColumn(
            'banner_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Title'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Status'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Web ID'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Type'
        )->addColumn(
            'category_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Category Id'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Product Id'
        )->addColumn(
            'sort_order',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Sort Order'
        );
        $installer->getConnection()->createTable($table_key);
        // end create table simiconnector_banner

        /**
         * Creating table simiconnector_cms
         */
        $table_key_name = $installer->getTable('simiconnector_cms');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_cms');
        $table_key = $installer->getConnection()->newTable(
            $table_key_name
        )->addColumn(
            'cms_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'CMS Id'
        )->addColumn(
            'cms_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'CMS title'
        )->addColumn(
            'cms_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'CMS image'
        )->addColumn(
            'cms_content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true],
            'CMS content'
        )->addColumn(
            'cms_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Status'
        )->addColumn(
            'website_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Web Id'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Type'
        )->addColumn(
            'category_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Cat id'
        )->addColumn(
            'sort_order',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Sort Order'
        );
        $installer->getConnection()->createTable($table_key);
        // end create table simiconnector_cms

        /**
         * Creating table simiconnector_product_list
         */
        $table_key_name = $installer->getTable('simiconnector_product_list');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_product_list');
        $table_key = $installer->getConnection()->newTable(
            $table_key_name
        )->addColumn(
            'productlist_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Product List Id'
        )->addColumn(
            'list_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Title'
        )->addColumn(
            'list_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'List Image'
        )->addColumn(
            'list_image_tablet',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'List Image Tab'
        )->addColumn(
            'list_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Type'
        )->addColumn(
            'list_products',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true],
            'List Products'
        )->addColumn(
            'list_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'status'
        )->addColumn(
            'sort_order',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Sort Order'
        )->addColumn(
            'matrix_width_percent',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Width Percent'
        )->addColumn(
            'matrix_height_percent',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Height Percent'
        )->addColumn(
            'matrix_width_percent_tablet',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Width tab'
        )->addColumn(
            'matrix_height_percent_tablet',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Height Tab'
        )->addColumn(
            'matrix_row',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Rownum'
        );
        $installer->getConnection()->createTable($table_key);
        // end create table simiconnector_product_list

        /**
         * Creating table simiconnector_visibility
         */
        $table_key_name = $installer->getTable('simiconnector_visibility');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_visibility');
        $table_key = $installer->getConnection()->newTable(
            $table_key_name
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'content_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Type'
        )->addColumn(
            'item_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Item Id'
        )->addColumn(
            'store_view_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Storeview Id'
        );
        $installer->getConnection()->createTable($table_key);
        // end create table simiconnector_visibility

        /**
         * Creating table simiconnector device
         */
        $table_device_name = $installer->getTable('simiconnector_device');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_device');
        $table_device = $installer->getConnection()->newTable(
            $table_device_name
        )->addColumn(
            'device_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Device Id'
        )->addColumn(
            'device_token',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Device Token'
        )->addColumn(
            'plaform_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Platform'
        )->addColumn(
            'storeview_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Storevew Id'
        )->addColumn(
            'latitude',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            30,
            ['nullable' => true],
            'Latitude'
        )->addColumn(
            'longitude',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            30,
            ['nullable' => true],
            'Longitude'
        )->addColumn(
            'address',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Address'
        )->addColumn(
            'city',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'City'
        )->addColumn(
            'country',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Country'
        )->addColumn(
            'zipcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Zipcode'
        )->addColumn(
            'state',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'State'
        )->addColumn(
            'created_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => true],
            'Created Time'
        )->addColumn(
            'is_demo',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            25,
            ['nullable' => true],
            'Is demo'
        )->addColumn(
            'user_email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'User Email'
        )->addColumn(
            'app_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'App Id'
        )->addColumn(
            'build_version',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Build Version'
        )->addColumn(
            'device_ip',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Device IP Address'
        )->addColumn(
            'device_user_agent',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Device User Agent'
        )->addColumn(
            'unseen_count',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Unseen Count'
        );
        $installer->getConnection()->createTable($table_device);
        // end create table simiconnector device

        /**
         * Creating table simiconnector notice
         */
        $table_notice_name = $installer->getTable('simiconnector_notice');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_notice');
        $table_notice = $installer->getConnection()->newTable(
            $table_notice_name
        )->addColumn(
            'notice_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Notice Id'
        )->addColumn(
            'notice_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Notice Title'
        )->addColumn(
            'notice_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Notice Url'
        )->addColumn(
            'notice_content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true, 'default' => null],
            'Notice Content'
        )->addColumn(
            'notice_sanbox',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => true],
            'Notice Sanbox'
        )->addColumn(
            'storeview_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Storeview Id'
        )->addColumn(
            'device_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Device Id'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            5,
            ['nullable' => true],
            'Type'
        )->addColumn(
            'category_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Category Id'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Product Id'
        )->addColumn(
            'image_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Image Url'
        )->addColumn(
            'location',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Location'
        )->addColumn(
            'distance',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Distance'
        )->addColumn(
            'address',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Address'
        )->addColumn(
            'city',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'City'
        )->addColumn(
            'country',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Country'
        )->addColumn(
            'state',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'State'
        )->addColumn(
            'zipcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Zipcode'
        )->addColumn(
            'show_popup',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => true, 'default' => null],
            'Show Popup'
        )->addColumn(
            'devices_pushed',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true, 'default' => null],
            'Devices Pushed'
        )->addColumn(
            'created_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => true],
            'Created Time'
        );
        $installer->getConnection()->createTable($table_notice);
        // end create table simiconnector notice

        /**
         * Creating table simiconnector notice history
         */
        $table_notice_name = $installer->getTable('simiconnector_notice_history');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_notice_history');
        $table_notice = $installer->getConnection()->newTable(
            $table_notice_name
        )->addColumn(
            'history_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Notice Id'
        )->addColumn(
            'notice_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Notice Title'
        )->addColumn(
            'notice_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Notice Url'
        )->addColumn(
            'notice_content',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true, 'default' => null],
            'Notice Content'
        )->addColumn(
            'notice_sanbox',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => true],
            'Notice Sanbox'
        )->addColumn(
            'storeview_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Storeview Id'
        )->addColumn(
            'device_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Device Id'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            5,
            ['nullable' => true],
            'Type'
        )->addColumn(
            'category_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Category Id'
        )->addColumn(
            'product_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Product Id'
        )->addColumn(
            'image_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Image Url'
        )->addColumn(
            'location',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Location'
        )->addColumn(
            'distance',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Distance'
        )->addColumn(
            'address',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Address'
        )->addColumn(
            'city',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'City'
        )->addColumn(
            'country',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Country'
        )->addColumn(
            'state',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'State'
        )->addColumn(
            'zipcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Zipcode'
        )->addColumn(
            'show_popup',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true, 'default' => null],
            'Show Popup'
        )->addColumn(
            'created_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['nullable' => true],
            'Created Time'
        )->addColumn(
            'notice_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            255,
            ['nullable' => true],
            'Notice Type'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            255,
            ['nullable' => true],
            'Status'
        )->addColumn(
            'devices_pushed',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true, 'default' => null],
            'Devices Pushed'
        )->addColumn(
            'notice_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Notice Id'
        );
        $installer->getConnection()->createTable($table_notice);
        // end create table simiconnector notice history

        /**
         * Creating table simiconnector barcode
         * Remember to update function getAllColumOfTable() after changed this table
         */
        $table_barcode_name = $installer->getTable('simiconnector_simibarcode');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_simibarcode');
        $table_barcode = $installer->getConnection()->newTable(
            $table_barcode_name
        )->addColumn(
            'barcode_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Barcode Id'
        )->addColumn(
            'barcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Barcode'
        )->addColumn(
            'qrcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'QR code'
        )->addColumn(
            'barcode_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => true],
            'Status'
        )->addColumn(
            'product_entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Product Id'
        )->addColumn(
            'product_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Product Name'
        )->addColumn(
            'product_sku',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Sku'
        )->addColumn(
            'created_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            255,
            ['nullable' => true, 'default' => null],
            'Created Time'
        );
        $installer->getConnection()->createTable($table_barcode);
        // end create table simiconnector barcode

        /**
         * Creating table simiconnector Videos
         */
        $table_video_name = $installer->getTable('simiconnector_videos');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_videos');
        $table_video = $installer->getConnection()->newTable(
            $table_video_name
        )->addColumn(
            'video_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Video Id'
        )->addColumn(
            'video_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Video URL'
        )->addColumn(
            'video_key',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Video Key'
        )->addColumn(
            'video_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Video Title'
        )->addColumn(
            'product_ids',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true, 'default' => null],
            'Product ids'
        )->addColumn(
            'storeview_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Storeview Id'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Status'
        );
        $installer->getConnection()->createTable($table_video);
        // end create table simiconnector Video

        /**
         * Creating table simiconnector Transactions
         */
        $table_transaction_name = $installer->getTable('simiconnector_transactions');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_transactions');
        $table_transaction = $installer->getConnection()->newTable(
            $table_transaction_name
        )->addColumn(
            'transaction_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Transaction Id'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Order Id'
        );
        $installer->getConnection()->createTable($table_transaction);
        // end create table simiconnector Transactions

        /**
         * Creating table simiconnector Productlabels
         */
        $table_label_name = $installer->getTable('simiconnector_productlabels');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_productlabels');
        $table_label = $installer->getConnection()->newTable(
            $table_label_name
        )->addColumn(
            'label_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Label Id'
        )->addColumn(
            'storeview_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Storeview Id'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Name'
        )->addColumn(
            'description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true, 'default' => null],
            'Description'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Status'
        )->addColumn(
            'product_ids',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true, 'default' => null],
            'Product ids'
        )->addColumn(
            'from_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            255,
            ['nullable' => true, 'default' => null],
            'From Time'
        )->addColumn(
            'to_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            255,
            ['nullable' => true, 'default' => null],
            'To Time'
        )->addColumn(
            'priority',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Priority'
        )->addColumn(
            'conditions_serialized',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            ['nullable' => true, 'default' => null],
            'Conditions Serialized'
        )->addColumn(
            'text',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Text'
        )->addColumn(
            'image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Image'
        )->addColumn(
            'position',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'position'
        )->addColumn(
            'display',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'display'
        )->addColumn(
            'category_text',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Category Text'
        )->addColumn(
            'category_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Category Image'
        )->addColumn(
            'category_position',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Category Position'
        )->addColumn(
            'category_display',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Category Display'
        )->addColumn(
            'is_auto_fill',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Is Autofill'
        )->addColumn(
            'created_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            255,
            ['nullable' => true, 'default' => null],
            'Created Time'
        )->addColumn(
            'update_time',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            255,
            ['nullable' => true, 'default' => null],
            'Updated Time'
        )->addColumn(
            'condition_selected',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Condition Selected'
        )->addColumn(
            'threshold',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'threshold'
        );
        $installer->getConnection()->createTable($table_label);
        // end create table simiconnector Productlabels

        /**
         * Creating table simiconnector Taskbar
         */
        $table_taskbar_name = $installer->getTable('simiconnector_taskbar');
        $this->checkTableExist($installer, $table_key_name, 'simiconnector_taskbar');
        $table_taskbar = $installer->getConnection()->newTable(
            $table_taskbar_name
        )->addColumn(
            'taskbar_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false],
            'Taskbar Id'
        )->addColumn(
            'storeview_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Storeview Id'
        )->addColumn(
            'taskbar_color',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Color'
        )->addColumn(
            'icon_color',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon Color'
        )->addColumn(
            'item1_text',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Item 1 Text'
        )->addColumn(
            'item1_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 1 Image'
        )->addColumn(
            'item1_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 1 Type'
        )->addColumn(
            'item2_text',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Item 2 Text'
        )->addColumn(
            'item2_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 2 Image'
        )->addColumn(
            'item2_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 2 Type'
        )->addColumn(
            'item3_text',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Item 3 Text'
        )->addColumn(
            'item3_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 3 Image'
        )->addColumn(
            'item3_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 3 Type'
        )->addColumn(
            'item4_text',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Item 4 Text'
        )->addColumn(
            'item4_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 4 Image'
        )->addColumn(
            'item4_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 4 Type'
        )->addColumn(
            'item5_text',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Item 5 Text'
        )->addColumn(
            'item5_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 5 Image'
        )->addColumn(
            'item5_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 5 Type'
        )->addColumn(
            'item6_text',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Item 6 Text'
        )->addColumn(
            'item6_image',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 6 Image'
        )->addColumn(
            'item6_type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Icon 6 Type'
        )->addIndex(
            'idx_primary',
            ['taskbar_id'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_PRIMARY]
        );
        $installer->getConnection()->createTable($table_taskbar);
        // end create table simiconnector Taskbar
        $installer->endSetup();
    }
    
    public function checkTableExist($installer, $table_key_name, $table_name)
    {
        if ($installer->getConnection()->isTableExists($table_key_name) == true) {
            $installer->getConnection()
                    ->dropTable($installer->getConnection()->getTableName($table_name));
        }
    }
}
