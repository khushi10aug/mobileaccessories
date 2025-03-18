<?php
class PatchUpdateController extends ListingBaseController
{
    public function __construct($action)
    {
        parent::__construct($action);
        $this->objPrivilege->canEditPatch($this->admin_id);
        ini_set('memory_limit', '100M');
        set_time_limit(0);
    }

    /**
     * updateShippingProfiles
     * @Description : For V9.3 to update default shipping profile for all seller and admin if not created and products are not bound.
     * @return void
     */
    public function updateShippingProfiles()
    {
        /* For Admin */
        ShippingProfile::getDefaultProfileId(0);
        /* For Admin */

        /* For All Sellers */
        $userObj = new User();
        $srch = $userObj->getUserSearchObj(['u.user_id', 'u.user_id as uid'], true, true);
        $srch->addCondition('u.' . User::DB_TBL_PREFIX . 'is_supplier', '=', applicationConstants::YES);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $users = $db->fetchAllAssoc($rs);
        foreach ($users as $userId) {
            ShippingProfile::getDefaultProfileId($userId);
        }
        /* For All Sellers */
        echo 'Done!';
    }

    /**
     * updateTaxRules
     * @Description : For V9.3 to insert tax as 0 for tax-category in which "Rest Of The World" country is not bind.
     * @return void
     */
    public function updateTaxRules()
    {
        if (0 < Tax::getActivatedServiceId()) {
            echo 'No Need as Tax Api Enabled';
        }
        $countryId = -1;
        $stateId = -1;
        $taxCatArr = Tax::getSaleTaxCatArr($this->siteLangId);
        $structureId = TaxStructure::getDefaultTaxStructureId();
        foreach ($taxCatArr as $taxCatId => $cat) {
            $srch = TaxRuleLocation::getSearchObject();
            $srch->addCondition('taxruleloc_taxcat_id', '=', $taxCatId);
            $srch->addCondition('taxruleloc_to_country_id', '=', $countryId);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $res = $srch->getResultSet();
            $locationsData = FatApp::getDb()->fetch($res);
            if ($locationsData == false) {
                $ruleId = 0;
                $states = [];
                $taxRuleObj = new TaxRule($ruleId);
                $rule['taxrule_taxcat_id'] = $taxCatId;
                $rule['taxrule_name'] = Labels::getLabel('LBL_ZERO_TAX', CommonHelper::getLangId());
                $rule['taxrule_taxstr_id'] = $structureId;
                $taxRuleObj->assignValues($rule);
                if (!$taxRuleObj->save()) {
                    LibHelper::exitWithError($taxRuleObj->getError());
                }

                $ruleId = $taxRuleObj->getMainTableRecordId();

                if (!$taxRuleObj->addUpdateRate(0)) {
                    LibHelper::exitWithError($taxRuleObj->getError());
                }

                /* [ update location data */
                $locData = array(
                    'taxruleloc_taxcat_id' => $taxCatId,
                    'taxruleloc_taxrule_id' => $ruleId,
                    'taxruleloc_from_country_id' => $countryId,
                    'taxruleloc_from_state_id' => $stateId,
                    'taxruleloc_to_country_id' => $countryId,
                    'taxruleloc_to_state_id' => $stateId,
                    'taxruleloc_type' => TaxRule::TYPE_ALL_STATES,
                    /*'taxruleloc_unique' => 1*/
                );
                $locObj = new TaxRuleLocation();
                if (!$locObj->updateLocations($locData)) {
                    LibHelper::exitWithError($locObj->getError());
                }

                $combinedTax = array(
                    'taxruledet_taxstr_id' => $structureId,
                    'taxruledet_rate' => 0,
                );
                $taxRuleObj->addUpdateCombinedTax($combinedTax, 0);
                if (!$taxRuleObj->save()) {
                    LibHelper::exitWithError($locObj->getError());
                }
            }
        }
        echo 'Done!';
    }

    public function updateTaxCategories()
    {
        $plugin = new Plugin();
        $getDefaultPlugin = $plugin->getDefaultPluginData(Plugin::TYPE_TAX_SERVICES, ['plugin_id', 'plugin_code']);
        if (!$getDefaultPlugin) {
            LibHelper::exitWithError($plugin->getError(), true);
        }
        $pluginKey = $getDefaultPlugin['plugin_code'];
        $pluginId = $getDefaultPlugin['plugin_id'];
        if (false === $taxPluginObj = LibHelper::callPlugin($pluginKey, [$this->siteLangId], $error, $this->siteLangId)) {
            LibHelper::exitWithError($error, true);
        }

        if (false === $taxPluginObj->init()) {
            LibHelper::exitWithError($taxPluginObj->getError(), true);
        }

        $codesArr = $taxPluginObj->getCodes(null, null, null, array(), true);
        if (array_key_exists('status', $codesArr) && false === $codesArr['status']) {
            LibHelper::exitWithError($codesArr['msg'], true);
        }

        $db = FatApp::getDb();
        $parentArr = [];
        $parentId = 0;
        $categories = $codesArr['data'];

        foreach ($categories as $category) {
            if (isset($category['parentTaxCode']) &&  $category['parentTaxCode'] != '') {
                if (array_key_exists($category['parentTaxCode'], $parentArr)) {
                    $parentId = $parentArr[$category['parentTaxCode']];
                } else {
                    $taxRow = Tax::getAttributesByCode($category['parentTaxCode'], ['taxcat_id'], $pluginId);
                    if ($taxRow) {
                        $parentId = $taxRow['taxcat_id'];
                    }
                    $parentArr[$category['parentTaxCode']] = $parentId;
                }
            }

            $identifier = ($category['name'] != '') ? $category['name'] : $category['taxCode'];

            $arr = [
                'taxcat_identifier' => $identifier,
                'taxcat_code' => $category['taxCode'],
                'taxcat_parent' => $parentId,
                'taxcat_plugin_id' => $pluginId,
                'taxcat_active' => applicationConstants::ACTIVE,
                'taxcat_deleted' => applicationConstants::NO,
                'taxcat_last_updated' => date('Y-m-d H:i:s')
            ];

            $db->insertFromArray(Tax::DB_TBL, $arr, false, array(), $arr);
            $taxCatId = $db->getInsertId();

            $data = array(
                'taxcatlang_taxcat_id' => $taxCatId,
                'taxcatlang_lang_id' => $this->siteLangId,
                'taxcat_name' => $identifier,
            );

            $taxObj = new Tax($taxCatId);
            $taxObj->updateLangData($this->siteLangId, $data);
        }

        $this->set('msg', Labels::getLabel('MSG_RECORD_ADDED_SUCCESSFULLY', $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function resetFullTextSearchData()
    {
        $date = date('Y-m-d H:i:s');
        $srch = new ProductSearch();
        $srch->addMultipleFields(array('product_id', '0 as subrecord_id', UpdatedRecordLog::TYPE_PRODUCT . ' as record_type', "'" . $date . "' as updated_time"));
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        //$srch->addGroupBy('product_id');
        $tmpQry = $srch->getQuery();

        $qry = "INSERT INTO " . UpdatedRecordLog::DB_TBL . " (" . UpdatedRecordLog::DB_TBL_PREFIX . "record_id, " . UpdatedRecordLog::DB_TBL_PREFIX . "subrecord_id, " . UpdatedRecordLog::DB_TBL_PREFIX . "record_type, " . UpdatedRecordLog::DB_TBL_PREFIX . "added_on ) SELECT * FROM (" . $tmpQry . ") AS t ON DUPLICATE KEY UPDATE " . UpdatedRecordLog::DB_TBL_PREFIX . "record_id = t.product_id, " . UpdatedRecordLog::DB_TBL_PREFIX . "subrecord_id = t.subrecord_id, " . UpdatedRecordLog::DB_TBL_PREFIX . "record_type = t.record_type, " . UpdatedRecordLog::DB_TBL_PREFIX . "added_on = t.updated_time";

        FatApp::getDb()->query($qry);
        echo "Done";
    }

    public function fullTextSearch()
    {
        $languages = Language::getAllNames();

        if (empty($languages)) {
            echo "Language not found";
            die;
        }

        foreach ($languages as $langId => $language) {
            /* [ Reset Product data for full text search*/
            $srch = new ProductSearch();
            $srch->addMultipleFields(array('product_id', '0', $langId));
            $srch->doNotLimitRecords();
            $srch->doNotCalculateRecords();
            //$srch->addGroupBy('product_id');
            $tmpQry = $srch->getQuery();

            $qry = "INSERT INTO " . Product::DB_PRODUCT_EXTERNAL_RELATIONS . " (" . Product::DB_PRODUCT_EXTERNAL_RELATIONS_PREFIX . "product_id, " . Product::DB_PRODUCT_EXTERNAL_RELATIONS_PREFIX . "indexed_for_search, " . Product::DB_PRODUCT_EXTERNAL_RELATIONS_PREFIX . "lang_id ) SELECT * FROM (" . $tmpQry . ") AS t ON DUPLICATE KEY UPDATE " . Product::DB_PRODUCT_EXTERNAL_RELATIONS_PREFIX . "product_id = t.product_id, " . Product::DB_PRODUCT_EXTERNAL_RELATIONS_PREFIX . "indexed_for_search = 0, " . Product::DB_PRODUCT_EXTERNAL_RELATIONS_PREFIX . "lang_id = t." . $langId;

            FatApp::getDb()->query($qry);
            /* ]*/

            /* [ Reset Seller Product data for full text search*/
            /*$srch = new ProductSearch();
            $srch->joinTable(SellerProduct::DB_TBL, 'INNER JOIN', 'p.product_id = sprods.selprod_product_id and selprod_active = ' . applicationConstants::ACTIVE .' and selprod_deleted = ' . applicationConstants::NO, 'sprods');
            $srch->addMultipleFields(array('selprod_id',$langId,'selprod_product_id','0',));
            $srch->addCondition('selprod_deleted', '=', applicationConstants::NO);
            $srch->doNotLimitRecords();
            $srch->doNotCalculateRecords();
            $tmpQry = $srch->getQuery();

            $qry = "INSERT INTO " . SellerProduct::DB_TBL_EXTERNAL_RELATIONS . " (" . SellerProduct::DB_TBL_EXTERNAL_RELATIONS_PREFIX . "selprod_id, " . SellerProduct::DB_TBL_EXTERNAL_RELATIONS_PREFIX . "lang_id," . SellerProduct::DB_TBL_EXTERNAL_RELATIONS_PREFIX . "product_id," . SellerProduct::DB_TBL_EXTERNAL_RELATIONS_PREFIX . "indexed_for_search ) SELECT * FROM (" . $tmpQry . ") AS t ON DUPLICATE KEY UPDATE ". SellerProduct::DB_TBL_EXTERNAL_RELATIONS_PREFIX . "selprod_id = t.selprod_id, " . SellerProduct::DB_TBL_EXTERNAL_RELATIONS_PREFIX . " lang_id = t. " . $langId . "," . SellerProduct::DB_TBL_EXTERNAL_RELATIONS_PREFIX . "product_id = t.selprod_product_id," . SellerProduct::DB_TBL_EXTERNAL_RELATIONS_PREFIX . "indexed_for_search = 0";

            FatApp::getDb()->query($qry);*/
            /* ]*/
        }
        echo "Done";
    }

    public function updateCategoryTable()
    {
        $srch = ProductCategory::getSearchObject();
        $srch->addOrder('m.prodcat_active', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('prodcat_parent', '=', 0);
        $rs = $srch->getResultSet();
        $result = FatApp::getDb()->fetchAll($rs);
        foreach ($result as $row) {
            $productCategory = new ProductCategory($row['prodcat_id']);
            $productCategory->updateCatCode();
        }
        echo "Done";
    }

    public function updateCatOrderCode()
    {
        ProductCategory::updateCatOrderCode();
    }

    public function updateOrderProdSetting()
    {
        $srch = new SearchBase(OrderProduct::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addMultipleFields(array('op_id', 'op_tax_collected_by_seller'));
        $rs = $srch->getResultSet();
        $urlRows = FatApp::getDb()->fetchAll($rs);
        $db = FatApp::getDb();
        foreach ($urlRows as $row) {
            $data = array(
                'opsetting_op_id' => $row['op_id'],
                'op_tax_collected_by_seller' => $row['op_tax_collected_by_seller'],
                'op_commission_include_tax' => FatApp::getConfig('CONF_COMMISSION_INCLUDING_SHIPPING', FatUtility::VAR_INT, 0),
                'op_commission_include_shipping' => FatApp::getConfig('CONF_COMMISSION_INCLUDING_TAX', FatUtility::VAR_INT, 0),
            );

            if (!$db->insertFromArray(OrderProduct::DB_TBL_SETTINGS, $data, false, array(), $data)) {
                echo "Error with " . $row['op_id'] . ':' . $db->getError() . '<br>';
            }
        }
        echo "Done";
    }

    public function changeCustomUrl()
    {
        $urlSrch = UrlRewrite::getSearchObject();
        $urlSrch->doNotCalculateRecords();
        $urlSrch->doNotLimitRecords();
        $urlSrch->addMultipleFields(array('urlrewrite_id', 'urlrewrite_original', 'urlrewrite_custom'));
        $rs = $urlSrch->getResultSet();
        $urlRows = FatApp::getDb()->fetchAll($rs);
        $db = FatApp::getDb();
        foreach ($urlRows as $row) {
            $url = str_replace("/", "-", $row['urlrewrite_custom']);
            if ($db->updateFromArray(UrlRewrite::DB_TBL, array('urlrewrite_custom' => $url), array('smt' => 'urlrewrite_id = ?', 'vals' => array($row['urlrewrite_id'])))) {
                echo $row['urlrewrite_id'] . "<br>";
            }
        }
    }

    public function updateCharset()
    {
        echo $database = CONF_DB_NAME;
        FatApp::getDb()->query("ALTER DATABASE " . $database . " CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $qry = FatApp::getDb()->query("show tables");
        $res = FatApp::getDb()->fetchAll($qry);
        foreach ($res as $val) {
            // echo "ALTER TABLE " . $val['Tables_in_' . $database] . " CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
            FatApp::getDb()->query("ALTER TABLE " . $val['Tables_in_' . $database] . " CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

            echo 'Done:- ' . $val['Tables_in_' . $database] . '<br>';
        }
        // ALTER TABLE tbl_affiliate_commission_settings MODIFY COLUMN afcommsetting_fees decimal(12,4)
    }

    public function setKitData()
    {
        $confKeys = [
            'CONF_ENABLE_GEO_LOCATION' => 0,
            'CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE' => 0,
            'CONF_SITE_TRACKER_CODE' => '',
            'CONF_TWITTER_USERNAME' => '',
            'CONF_SITE_ROBOTS_TXT' => '',
            'CONF_GOOGLE_PUSH_NOTIFICATION_API_KEY' => '',
            'CONF_FACEBOOK_PIXEL_ID' => '',
            'CONF_ENGAGESPOT_API_KEY' => '',
            'CONF_ENGAGESPOT_SECRET_KEY' => '',
            'CONF_ENGAGESPOT_PUSH_NOTIFICATION_CODE' => '',
            'CONF_GOOGLEMAP_API_KEY' => '',
            'CONF_MAILCHIMP_KEY' => '',
            'CONF_MAILCHIMP_LIST_ID' => '',
            'CONF_ANALYTICS_ID' => '',
            'CONF_ANALYTICS_SECRET_KEY' => '',
            'CONF_ANALYTICS_CLIENT_ID' => '',
            'CONF_RECAPTCHA_SECRETKEY' => '',
            'CONF_RECAPTCHA_SITEKEY' => '',
            'CONF_TRANSLATOR_SUBSCRIPTION_KEY' => '',
            'CONF_USE_SSL' => 0,
        ];
        foreach ($confKeys as $key => $val) {
            FatApp::getDb()->query("UPDATE `tbl_configurations` SET `conf_val` = '" . $val . "' WHERE `tbl_configurations`.`conf_name` = '" . $key . "'");
        }
        FatApp::getDb()->query("TRUNCATE tbl_plugin_settings");
        FatApp::getDb()->query("UPDATE tbl_plugins SET plugin_active = 0 where plugin_code not in ('CashOnDelivery','PayAtStore')");
        FatApp::getDb()->query("INSERT INTO `tbl_plugin_settings` (`pluginsetting_plugin_id`, `pluginsetting_key`, `pluginsetting_value`) VALUES (21, 'otp_verification', '0'), (37, 'otp_verification', '0')");
        FatApp::getDb()->query("TRUNCATE tbl_commission_settings");
        FatApp::getDb()->query("TRUNCATE tbl_commission_setting_history");
        FatApp::getDb()->query("INSERT INTO `tbl_commission_settings` (`commsetting_id`, `commsetting_product_id`, `commsetting_user_id`, `commsetting_prodcat_id`, `commsetting_fees`, `commsetting_is_mandatory`, `commsetting_by_package`) VALUES (NULL, '', '', '', '2', '1','0')");
        echo "Done";
    }

    public function truncateTables($type = 'orders')
    {
        // die('You need to comment this line before execution.');

        if (1 != $this->admin_id || false == CONF_DEVELOPMENT_MODE) {
            die('Invalid Access! You are not authorized.');
        }

        if ($type == 'orders') {
            $tables = array(
                'tbl_orders', 'tbl_orders_lang', 'tbl_orders_status_history', 'tbl_order_cancel_requests', 'tbl_order_extras', 'tbl_order_payments',
                'tbl_order_products', 'tbl_order_products_lang', 'tbl_order_product_charges', 'tbl_order_product_charges_lang', 'tbl_order_product_digital_download_links',
                'tbl_order_product_shipping', 'tbl_order_product_shipping_lang', 'tbl_order_product_to_shipping_users', 'tbl_order_return_requests', 'tbl_order_return_request_messages',
                'tbl_order_seller_subscriptions', 'tbl_order_seller_subscriptions_lang', 'tbl_order_user_address', 'tbl_user_reward_points', 'tbl_user_reward_point_breakup',
                'tbl_rewards_on_purchase', 'tbl_coupons_history', 'tbl_coupons_hold', 'tbl_user_cart', 'tbl_order_product_settings', 'tbl_order_product_shipment',
                'tbl_order_prod_charges_logs', 'tbl_order_prod_charges_logs_lang', 'tbl_orders_to_plugin_order', 'tbl_order_product_shipment_pickup', 'tbl_order_product_plugin_specifics', 'tbl_order_product_responses', 'tbl_user_withdrawal_requests',
                'tbl_user_withdrawal_requests_specifics', 'tbl_seller_product_reviews', 'tbl_seller_product_reviews_abuse', 'tbl_seller_product_reviews_helpful', 'tbl_seller_product_rating', 'tbl_order_product_specifics','tbl_order_gift_cards','tbl_calculative_data', 'tbl_coupons_hold_pending_order', 'tbl_shop_stats', 'tbl_rfq', 'tbl_rfq_to_sellers', 'tbl_rfq_offers', 'tbl_rfq_latest_offers', 'tbl_rfq_offer_messages'
            );
            FatApp::getDb()->query('UPDATE `tbl_seller_products` SET `selprod_sold_count` = 0 WHERE 1');

            FatApp::getDb()->query("DELETE FROM `tbl_attached_files` WHERE `afile_type` in (60)");
        } else if ($type == 'products') {
            $tables = array('tbl_ads_batch_products', 'tbl_abandoned_cart', 'tbl_attribute_group_attributes', 'tbl_attribute_group_attributes_lang', 'tbl_attribute_groups', 'tbl_brands', 'tbl_brands_lang', 'tbl_catalog_request_messages',  'tbl_content_block_to_category', 'tbl_coupon_to_category', 'tbl_coupon_to_plan', 'tbl_coupon_to_products', 'tbl_coupon_to_category', 'tbl_coupon_to_plan', 'tbl_coupon_to_seller', 'tbl_extra_attribute_groups', 'tbl_extra_attribute_groups_lang', 'tbl_extra_attributes', 'tbl_extra_attributes_lang',  'tbl_filter_groups', 'tbl_filter_groups_lang', 'tbl_filters', 'tbl_filters_lang',  'tbl_product_category_relations', 'tbl_coupon_to_brands', 'tbl_coupon_to_shops', 'tbl_meta_tags', 'tbl_order_product_settings', 'tbl_seller_products', 'tbl_seller_products_lang', 'tbl_seller_products_temp_ids', 'tbl_seller_product_options', 'tbl_seller_product_policies', 'tbl_seller_product_rating', 'tbl_seller_product_reviews', 'tbl_seller_product_reviews_abuse', 'tbl_seller_product_reviews_helpful', 'tbl_recommendation_activity_browsing', 'tbl_related_products', 'tbl_rewards_on_purchase', 'tbl_search_items', 'tbl_seller_brand_requests', 'tbl_seller_brand_requests_lang', 'tbl_seller_catalog_requests', 'tbl_polling_to_category', 'tbl_polling_to_products', 'tbl_product_categories', 'tbl_product_categories_lang', 'tbl_product_groups', 'tbl_product_groups_lang', 'tbl_product_numeric_attributes', 'tbl_product_product_recommendation', 'tbl_product_shipping_rates', 'tbl_product_special_prices', 'tbl_product_specifications', 'tbl_product_specifications_lang', 'tbl_product_stock_hold', 'tbl_product_text_attributes', 'tbl_product_to_category', 'tbl_product_to_groups', 'tbl_product_to_options', 'tbl_product_to_tags', 'tbl_product_to_tax', 'tbl_product_volume_discount', 'tbl_products', 'tbl_products_browsing_history', 'tbl_products_lang', 'tbl_products_shipped_by_seller', 'tbl_products_shipping', 'tbl_products_temp_ids', 'tbl_collection_to_records','tbl_product_requests','tbl_product_requests_lang', 'tbl_product_saved_search', 'tbl_product_specifics', 'tbl_shop_collection_products', 'tbl_products_to_plugin_product', 'tbl_products_min_price', 'tbl_seller_product_specifics', 'tbl_shipping_profile_products', 'tbl_seller_products_to_plugin_selprod','tbl_product_digital_data_relation','tbl_product_digital_links', 'tbl_prodcat_rating_types', 'tbl_smart_products_weightage', 'tbl_smart_remommended_products', 'tbl_tag_product_recommendation', 'tbl_upc_codes', 'tbl_upsell_products', 'tbl_user_favourite_products', 'tbl_user_product_recommendation', 'tbl_user_wish_list_products', 'tbl_calculative_data');
            FatApp::getDb()->query("INSERT INTO `tbl_meta_tags`(`meta_controller`, `meta_action`, `meta_record_id`, `meta_subrecord_id`, `meta_default`, `meta_advanced`) VALUES 
            ('', '', 0, 0, 1, 0),
            ('Brands', 'index', 0, 0, 1, 0),
            ('Shops', 'index', 0, 0, 1, 0),
            ('Products', 'index', 0, 0, 1, 0)");
            FatApp::getDb()->query("DELETE FROM `tbl_attached_files` WHERE `afile_type` in (1,2,3,8,11,12,13,24,25,26,27,43,42,52,63,64,65,66)");
            FatApp::getDb()->query("DELETE FROM `tbl_affiliate_commission_settings` WHERE `afcommsetting_id` != 1 OR `afcommsetting_prodcat_id` > 0");
            FatApp::getDb()->query("DELETE FROM `tbl_affiliate_commission_setting_history` WHERE `acsh_afcommsetting_id` != 1 OR `acsh_afcommsetting_prodcat_id` > 0");
            FatApp::getDb()->query("DELETE FROM `tbl_commission_settings` WHERE `commsetting_id` != 1 OR `commsetting_product_id` > 0 OR `commsetting_prodcat_id` > 0");
            FatApp::getDb()->query("DELETE FROM `tbl_commission_setting_history` WHERE `csh_commsetting_id` != 1 OR `csh_commsetting_product_id` > 0 OR `csh_commsetting_prodcat_id` > 0");
        } elseif ($type == 'all') {
            $tables = array(
                'tbl_abusive_words', 'tbl_admin_auth_token', 'tbl_admin_password_reset_requests', 'tbl_admin_permissions', 'tbl_affiliate_commission_setting_history',
                'tbl_affiliate_commission_settings', 'tbl_attached_files_temp', 'tbl_attribute_group_attributes', 'tbl_attribute_group_attributes_lang', 'tbl_attribute_groups',
                'tbl_banner_locations_lang', 'tbl_banners', 'tbl_banners_clicks', 'tbl_banners_lang', 'tbl_banners_logs', 'tbl_blog_contributions', 'tbl_blog_post',
                'tbl_blog_post_categories', 'tbl_blog_post_categories_lang', 'tbl_blog_post_comments', 'tbl_blog_post_lang', 'tbl_blog_post_to_category',
                'tbl_brands', 'tbl_brands_lang', 'tbl_catalog_request_messages', 'tbl_collection_to_product_categories', 'tbl_collection_to_seller_products',
                'tbl_collection_to_shops', 'tbl_collections', 'tbl_collections_lang', 'tbl_commission_setting_history', 'tbl_content_block_to_category', 'tbl_coupon_to_category',
                'tbl_coupon_to_plan', 'tbl_coupon_to_products', 'tbl_coupon_to_seller', 'tbl_coupon_to_users', 'tbl_coupons', 'tbl_coupons_history', 'tbl_coupons_hold',
                'tbl_coupons_lang', 'tbl_cron_log', 'tbl_email_archives', 'tbl_extra_attribute_groups', 'tbl_extra_attribute_groups_lang', 'tbl_extra_attributes',
                'tbl_extra_attributes_lang', 'tbl_failed_login_attempts', 'tbl_faq_categories', 'tbl_faq_categories_lang', 'tbl_faqs', 'tbl_faqs_lang', 'tbl_filter_groups',
                'tbl_filter_groups_lang', 'tbl_filters', 'tbl_filters_lang', 'tbl_import_export_settings', 'tbl_manual_shipping_api', 'tbl_manual_shipping_api_lang',
                'tbl_meta_tags_lang', 'tbl_notifications', 'tbl_option_values', 'tbl_option_values_lang', 'tbl_options', 'tbl_options_lang', 'tbl_order_cancel_reasons_lang',
                'tbl_order_cancel_requests', 'tbl_order_extras', 'tbl_order_payments', 'tbl_order_product_charges', 'tbl_order_product_charges_lang', 'tbl_order_product_shipping',
                'tbl_order_product_shipping_lang', 'tbl_order_product_to_shipping_users', 'tbl_order_products', 'tbl_order_products_lang', 'tbl_order_return_request_messages',
                'tbl_order_return_requests', 'tbl_order_seller_subscriptions', 'tbl_order_seller_subscriptions_lang', 'tbl_order_seller_subscriptions_lang_old',
                'tbl_order_user_address', 'tbl_orders', 'tbl_orders_lang', 'tbl_orders_status_history', 'tbl_orders_status_lang', 'tbl_policy_points', 'tbl_policy_points_lang',
                'tbl_polling', 'tbl_polling_feedback', 'tbl_polling_lang', 'tbl_polling_to_category', 'tbl_polling_to_products', 'tbl_product_categories', 'tbl_product_categories_lang',
                'tbl_product_groups', 'tbl_product_groups_lang', 'tbl_product_numeric_attributes', 'tbl_product_product_recommendation', 'tbl_product_shipping_rates',
                'tbl_product_special_prices', 'tbl_product_specifications', 'tbl_product_specifications_lang', 'tbl_product_stock_hold', 'tbl_product_text_attributes',
                'tbl_product_to_category', 'tbl_product_to_groups', 'tbl_product_to_options', 'tbl_product_to_tags', 'tbl_product_to_tax', 'tbl_product_volume_discount',
                'tbl_products', 'tbl_products_browsing_history', 'tbl_products_lang', 'tbl_products_shipped_by_seller', 'tbl_products_shipping', 'tbl_products_temp_ids',
                'tbl_promotion_item_charges', 'tbl_promotions', 'tbl_promotions_charges', 'tbl_promotions_clicks', 'tbl_promotions_lang', 'tbl_promotions_logs', 'tbl_promotions_old',
                'tbl_question_banks', 'tbl_question_banks_lang', 'tbl_question_to_answers', 'tbl_questionnaire_feedback', 'tbl_questionnaires', 'tbl_questionnaires_lang',
                'tbl_questionnaires_to_question', 'tbl_questions', 'tbl_questions_lang', 'tbl_recommendation_activity_browsing', 'tbl_related_products', 'tbl_rewards_on_purchase', 'tbl_rfq', 'tbl_rfq_to_sellers', 'tbl_rfq_offers', 'tbl_rfq_latest_offers', 'tbl_rfq_offer_messages', 
                'tbl_search_items', 'tbl_seller_brand_requests', 'tbl_seller_brand_requests_lang', 'tbl_seller_catalog_requests', 'tbl_seller_packages', 'tbl_seller_packages_lang',
                'tbl_seller_packages_plan', 'tbl_seller_product_options', 'tbl_seller_product_policies', 'tbl_seller_product_rating', 'tbl_seller_product_reviews',
                'tbl_seller_product_reviews_abuse', 'tbl_seller_product_reviews_helpful', 'tbl_seller_products', 'tbl_seller_products_lang', 'tbl_seller_products_temp_ids',
                'tbl_shipping_company', 'tbl_shipping_company_lang', 'tbl_shipping_durations', 'tbl_shipping_durations_lang', 'tbl_shippingapi_settings', 'tbl_shop_collection_products',
                'tbl_shop_collections', 'tbl_shop_collections_lang', 'tbl_shop_reports', 'tbl_shops', 'tbl_shops_lang', 'tbl_shops_to_theme', 'tbl_smart_log_actions',
                'tbl_smart_products_weightage', 'tbl_smart_remommended_products', 'tbl_smart_user_activity_browsing', 'tbl_smart_weightage_settings', 'tbl_social_platforms',
                'tbl_social_platforms_lang', 'tbl_success_stories', 'tbl_success_stories_lang', 'tbl_tag_product_recommendation', 'tbl_tags', 'tbl_tags_lang', 'tbl_tax_categories',
                'tbl_tax_categories_lang', 'tbl_tax_values', 'tbl_testimonials', 'tbl_testimonials_lang', 'tbl_thread_messages', 'tbl_threads', 'tbl_tool_tips', 'tbl_tool_tips_lang',
                'tbl_upsell_products', 'tbl_url_rewrite', 'tbl_user_address', 'tbl_user_auth_token', 'tbl_user_bank_details', 'tbl_user_cart', 'tbl_user_credentials',
                'tbl_user_email_verification', 'tbl_user_extras', 'tbl_user_favourite_products', 'tbl_user_favourite_shops', 'tbl_user_password_reset_requests',
                'tbl_user_product_recommendation', 'tbl_user_return_address', 'tbl_user_return_address_lang', 'tbl_user_reward_point_breakup', 'tbl_user_reward_points',
                'tbl_user_supplier_request_values', 'tbl_user_supplier_request_values_lang', 'tbl_user_supplier_requests', 'tbl_user_transactions', 'tbl_user_wish_list_products',
                'tbl_user_wish_lists', 'tbl_user_withdrawal_requests', 'tbl_users', 'tbl_order_product_settings', 'tbl_user_requests_history', 'tbl_meta_tags', 'tbl_coupon_to_brands',
                'tbl_coupon_to_shops', 'tbl_transactions_failure_log', 'tbl_product_category_relations', 'tbl_tax_structure', 'tbl_tax_structure_lang', 'tbl_collection_to_records',
                'tbl_tracking_courier_code_relation', 'tbl_time_slots', 'tbl_user_collections', 'tbl_addresses', 'tbl_order_product_shipment', 'tbl_order_prod_charges_logs', 'tbl_order_prod_charges_logs_lang',
                'tbl_tax_rule_locations', 'tbl_tax_rule_details_lang', 'tbl_tax_rule_details', 'tbl_tax_rules', 'tbl_tax_rule_rates', 'tbl_shipping_locations', 'tbl_shipping_zone', 'tbl_shipping_rates_lang',
                'tbl_shipping_rates', 'tbl_shipping_profile_zones', 'tbl_shipping_profile_products', 'tbl_shipping_profile', 'tbl_shipping_packages', 'tbl_abandoned_cart',
                'tbl_products_min_price', 'tbl_product_requests', 'tbl_product_requests_lang', 'tbl_product_saved_search', 'tbl_product_specifics', 'tbl_products_to_plugin_product',
                'tbl_seller_products_to_plugin_selprod', 'tbl_orders_to_plugin_order', 'tbl_plugin_to_user', 'tbl_product_digital_data_relation', 'tbl_product_digital_links', 'tbl_badges',
                'tbl_badges_lang', 'tbl_badge_link_conditions', 'tbl_badge_links', 'tbl_badge_requests', 'tbl_order_product_shipment_pickup', 'tbl_system_logs', 'tbl_order_product_plugin_specifics',
                'tbl_order_product_responses', 'tbl_unique_check_failed_attempt', 'tbl_upc_codes', 'tbl_user_cookies_preferences', 'tbl_user_meta', 'tbl_user_notifications', 'tbl_user_withdrawal_requests_specifics', 'tbl_order_product_specifics', 'tbl_shop_specifics', 'tbl_shop_stats','tbl_order_gift_cards','tbl_calculative_data'
            );
            FatApp::getDb()->query("DELETE FROM `tbl_attached_files` WHERE `afile_type` in (1,2,3,4,5,7,8,9,10,11,12,13,14,22,23,24,25,26,27,28,29,30,32,33,41,42,43,48,50,52,53,60,63)");

            /*
            Delete FROM `tbl_navigation_links` where nlink_nav_id != 1
            */

            /* nlink_type : 3 (Product Category Page) */
            FatApp::getDb()->query("DELETE FROM `tbl_navigation_links_lang` WHERE `nlinklang_nlink_id` IN (SELECT nlink_id FROM `tbl_navigation_links` WHERE `nlink_type` = 3)");
            FatApp::getDb()->query("DELETE FROM `tbl_navigation_links` WHERE `nlink_type` = 3");
        }

        foreach ($tables as $table) {
            $result = FatApp::getDb()->query("TRUNCATE TABLE `" . $table . "`");

            if ($result) {
                echo 'Done: ' . $table . ' <br>';
            } else {
                echo 'Error in: ' . $table . ' <br>';
            }
        }

        if ($type == 'all') {
            FatApp::getDb()->query("INSERT INTO `tbl_meta_tags`(`meta_controller`, `meta_action`, `meta_record_id`, `meta_subrecord_id`, `meta_default`, `meta_advanced`) VALUES 
            ('', '', 0, 0, 1, 0),
            ('Brands', 'index', 0, 0, 1, 0),
            ('Shops', 'index', 0, 0, 1, 0),
            ('Blog', 'index', 0, 0, 1, 0),
            ('Products', 'index', 0, 0, 1, 0)");
            FatApp::getDb()->query("INSERT INTO `tbl_shipping_profile`(`shipprofile_user_id`, `shipprofile_identifier`, `shipprofile_active`, `shipprofile_default`) VALUES (0, 'Order Leval Shipping', 1, 1)");
        }
    }

    public function changeCustomUrl1()
    {
        $urlSrch = UrlRewrite::getSearchObject();
        $urlSrch->doNotCalculateRecords();
        $urlSrch->addMultipleFields(array('urlrewrite_id', 'urlrewrite_original', 'urlrewrite_custom'));
        $rs = $urlSrch->getResultSet();
        $urlRows = FatApp::getDb()->fetchAll($rs);
        $db = FatApp::getDb();
        foreach ($urlRows as $row) {
            $url = str_replace("/", "-", $row['urlrewrite_custom']);
            if ($db->updateFromArray(UrlRewrite::DB_TBL, array('urlrewrite_custom' => $url), array('smt' => 'urlrewrite_id = ?', 'vals' => array($row['urlrewrite_id'])))) {
                echo $row['urlrewrite_id'] . "<br>";
            }
        }
    }

    public function changeSelprodCode()
    {
        $srch = SellerProduct::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(array('selprod_id', 'selprod_code'));
        $rs = $srch->getResultSet();
        $rows = FatApp::getDb()->fetchAll($rs);
        $db = FatApp::getDb();
        foreach ($rows as $row) {
            $codeArr = explode("_", $row['selprod_code']);
            sort($codeArr);
            $selProdCode = implode("_", $codeArr);
            if ($db->updateFromArray(SellerProduct::DB_TBL, array('selprod_code' => $selProdCode), array('smt' => 'selprod_id = ?', 'vals' => array($row['selprod_id'])))) {
                echo $row['selprod_id'] . "<br>";
            }
        }
    }

    public function updateCategoryRelations(int $prodCatId = 0)
    {
        ProductCategory::updateCategoryRelations($prodCatId);
        echo 'Done';
    }

    public static function updateShopsAvgRating()
    {
        $srch = new SelProdReviewSearch();
        $srch->joinSeller();
        $srch->joinSellerProducts();
        $srch->joinSelProdRating();
        $srch->joinOrderProduct();
        $srch->joinOrderProductShipping();
        $srch->addMultipleFields(array('ROUND(AVG(sprating_rating),2) as avg_rating', 'spreview_seller_user_id'));
        $srch->addDirectCondition("(CASE WHEN 0 < opshipping_by_seller_user_id THEN `ratingtype_type` IN('" . RatingType::TYPE_SHOP . "', '" . RatingType::TYPE_DELIVERY . "') ELSE `ratingtype_type` = '" . RatingType::TYPE_SHOP . "' END)");
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('spr.spreview_status', '=', 'mysql_func_' . SelProdReview::STATUS_APPROVED, 'AND', true);
        $srch->addGroupBy('spreview_seller_user_id');
        // $srch->addHaving('avg_rating', '>', 0);
        $rs = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $rsrch = SelProdRating::getAvgShopReviewsRatingObj($row['spreview_seller_user_id']);
            $rsrch->joinUser();
            $rsrch->joinSeller(0, $row['spreview_seller_user_id']);
            $rsrch->joinSellerProducts();
            $rsrch->joinProducts();
            $rsrch->addMultipleFields(array('count(distinct(spreview_id)) as numOfReviews'));
            $rsrch->doNotCalculateRecords();
            $rsrch->doNotLimitRecords();
            $rsrch->addGroupBy('spreview_seller_user_id');
            $record = FatApp::getDb()->fetch($rsrch->getResultSet());
            $numOfReviews = $record['numOfReviews'] ?? 0;

            FatApp::getDb()->query("Update tbl_shops set `shop_avg_rating` = '" . $row['avg_rating'] . "' and `shop_total_reviews` = '" . $numOfReviews . "' where shop_user_id = '" . $row['spreview_seller_user_id'] . "'");
        }
        echo 'Done';
    }

    public function updateValidSubscription()
    {
        if (1 > FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            echo 'Subscription settings not enabled.';
            return;
        }

        $sSrch = new SearchBase(Orders::DB_TBL, 'o');
        $sSrch->addMultipleFields(['max(o.order_id) as currentOrderId']);
        $sSrch->addCondition('o.order_type', '=', 'mysql_func_' . Orders::ORDER_SUBSCRIPTION, 'AND', true);
        $sSrch->addCondition('o.order_payment_status', '=',  'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);
        $sSrch->addGroupBy('o.order_id');
        $sSrch->doNotCalculateRecords();
        $sSrch->doNotLimitRecords();

        $srch = new searchBase(Orders::DB_TBL, 'o');
        $srch->joinTable('(' . $sSrch->getQuery() . ')', 'INNER JOIN', 'otemp.currentOrderId=o.order_id', 'otemp');
        $srch->joinTable(OrderSubscription::DB_TBL, 'INNER JOIN', 'o.order_id = oss.ossubs_order_id and oss.ossubs_status_id =' . FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') . " and oss.ossubs_till_date >= '" . date('Y-m-d') . "'", 'oss');

        $srch->addCondition('oss.ossubs_status_id', 'IN ', Orders::getActiveSubscriptionStatusArr());
        $srch->addCondition('o.order_type', '=', 'mysql_func_' . ORDERS::ORDER_SUBSCRIPTION, 'AND', true);
        $srch->addCondition('o.order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('o.order_user_id');
        $srch->addFld('o.order_user_id');
        $srch->addMultipleFields(['o.order_user_id']);

        $uSrch = new searchBase(User::DB_TBL, 'u');
        $uSrch->addCondition('u.user_is_supplier', '=', applicationConstants::YES);
        $uSrch->addCondition('u.user_deleted', '=', applicationConstants::NO);
        $uSrch->joinTable('(' . $srch->getQuery() . ')', 'LEFT JOIN', 'os.order_user_id=u.user_id', 'os');
        $uSrch->addMultipleFields(['u.user_id as seller_user_id', 'CASE WHEN os.order_user_id IS NULL THEN 0 ELSE 1 END as hasValidSubscription']);
        $result = FatApp::getDb()->fetchAll($uSrch->getResultSet());
        foreach ($result as $user) {
            $assignValues = ['user_has_valid_subscription' => $user['hasValidSubscription']];
            FatApp::getDb()->updateFromArray(User::DB_TBL, $assignValues, array('smt' => 'user_id = ? ', 'vals' => array((int) $user['seller_user_id'])));
            $assignValues = ['shop_has_valid_subscription' => $user['hasValidSubscription']];
            FatApp::getDb()->updateFromArray(Shop::DB_TBL, $assignValues, array('smt' => 'shop_user_id = ? ', 'vals' => array((int) $user['seller_user_id'])));
        }
        echo 'Done';
    }

    public function updateProductRating()
    {
        $selProdReviewObj = new SelProdReviewSearch();
        $selProdReviewObj->joinSellerProducts();
        $selProdReviewObj->joinSelProdRating();
        $selProdReviewObj->addCondition('ratingtype_type', '=', 'mysql_func_' . RatingType::TYPE_PRODUCT, 'AND', true);
        $selProdReviewObj->doNotCalculateRecords();
        $selProdReviewObj->doNotLimitRecords();
        $selProdReviewObj->addGroupBy('spr.spreview_product_id');
        $selProdReviewObj->addCondition('spr.spreview_status', '=', 'mysql_func_' . SelProdReview::STATUS_APPROVED, 'AND', true);
        $selProdReviewObj->addMultipleFields(array('spreview_product_id', "ROUND(AVG(sprating_rating),2) as prod_rating", "count(spreview_id) as totReviews"));
        $result = FatApp::getDb()->fetchAll($selProdReviewObj->getResultSet());
        foreach ($result as $data) {
            $assignValues = ['product_total_reviews' => $data['totReviews'], 'product_rating' => $data['prod_rating']];
            FatApp::getDb()->updateFromArray(Product::DB_TBL, $assignValues, array('smt' => 'product_id = ? ', 'vals' => array((int) $data['spreview_product_id'])));
        }
        echo 'Done';
    }

    public function removeForeignKeyConstraint()
    {
        $qry = FatApp::getDb()->query("SELECT CONSTRAINT_NAME,TABLE_NAME,COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = '" . CONF_DB_NAME . "' AND REFERENCED_TABLE_NAME IS NOT NULL");
        $res = FatApp::getDb()->fetchAll($qry);
        foreach ($res as $record) {
            $qry = "ALTER TABLE " . $record['TABLE_NAME'] . " DROP FOREIGN KEY " . $record['COLUMN_NAME'];
            FatApp::getDb()->query($qry);
            echo $qry . '<br>';
        }
        echo "Done";
    }

    public function updateProductMeta(){
        $qry = "UPDATE tbl_meta_tags_lang AS mtl
        JOIN tbl_meta_tags AS mt ON mtl.metalang_meta_id = mt.meta_id
        JOIN tbl_seller_products AS sp ON mt.meta_record_id = sp.selprod_id
        JOIN tbl_seller_products_lang AS spl ON sp.selprod_id = spl.selprodlang_selprod_id
        SET mtl.meta_title = spl.selprod_title
        WHERE mt.meta_controller = 'products' 
        AND mt.meta_action = 'view'
        AND mtl.metalang_lang_id = spl.selprodlang_lang_id;";
        FatApp::getDb()->query($qry);
        echo "done";
    }
    
}
