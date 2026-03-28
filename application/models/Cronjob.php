<?php

class Cronjob extends FatModel
{
    public const DB_TBL_CONFIGURATION = 'tbl_configurations';

    public function __construct()
    {
        CommonHelper::initCommonVariables();
    }

    public static function productRecommendation()
    {
        $limit = 25;

        $srch = RecommendationActivityBrowsing::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1000);
        $srch->addCondition('rab_weightage_key', '=', 'mysql_func_' . SmartWeightageSettings::PRODUCT_ORDER_PAID, 'AND', true);
        $srch->addCondition('rab_record_type', '=', 'mysql_func_' . SmartUserActivityBrowsing::TYPE_PRODUCT, 'AND', true);
        $rs = $srch->getResultSet();

        //var_dump($row);
        while ($val = FatApp::getDb()->fetch($rs)) {
            // foreach ($row as $val) {
            $srch = RecommendationActivityBrowsing::getSearchObject();
            $srch->doNotCalculateRecords();
            $srch->setPageSize($limit);
            $srch->addMultipleFields(array('sum(rab_weightage) as weightage', 'rab_session_id', 'rab_user_id', 'rab_record_id', 'rab_record_type', 'rab_weightage_key'));
            $srch->addCondition('rab_session_id', '=', $val['rab_session_id']);
            $srch->addCondition('rab_user_id', '=', $val['rab_user_id']);
            $srch->addCondition('rab_record_type', '=', $val['rab_record_type']);
            $srch->addCondition('rab_record_id', '!=', $val['rab_record_id']);
            $srch->addCondition('rab_last_action_datetime', '<=', $val['rab_last_action_datetime']);
            $srch->addGroupBy('rab_record_id');
            $srch->addOrder('weightage', 'DESC');
            $rs = $srch->getResultSet();
            $recommendedProdRes = FatApp::getDb()->fetchAll($rs, 'rab_record_id');
            //var_dump($recommendedProdRes);

            $relatedTagProdQuery = FatApp::getDb()->query("select tptot.ptt_product_id as product_id,tptot.ptt_tag_id as tag_id from (select ptt.ptt_tag_id from tbl_product_to_tags ptt where ptt.ptt_product_id = '" . (int)$val['rab_record_id'] . "') as tptt Left outer join tbl_product_to_tags tptot on (tptot.ptt_tag_id = tptt.ptt_tag_id) where tptot.ptt_product_id != '" . (int)$val['rab_record_id'] . "' group by tptot.ptt_product_id");
            $relatedTagProdArr = FatApp::getDb()->fetchAll($relatedTagProdQuery, 'product_id');

            //var_dump($relatedTagProdArr);

            foreach ($recommendedProdRes as $prodId => $recommendedProd) {
                /*Tag Product Recommendation*/
                if (array_key_exists($prodId, $relatedTagProdArr)) {
                    $tagProd = array(
                        'tpr_tag_id' => $relatedTagProdArr[$prodId]['tag_id'],
                        'tpr_product_id' => $prodId,
                        'tpr_weightage' => $recommendedProd['weightage'],
                    );
                    $onDuplicateKeyTagProdUpdate = array_merge($tagProd, array('tpr_weightage' => 'mysql_func_tpr_weightage + ' . $recommendedProd['weightage']));
                    FatApp::getDb()->insertFromArray('tbl_tag_product_recommendation', $tagProd, true, array(), $onDuplicateKeyTagProdUpdate);
                } else {
                    /*User Product Recommendation*/
                    $userProdRecommendation = array(
                        'upr_user_id' => $val['rab_user_id'],
                        'upr_product_id' => $prodId,
                        'upr_weightage' => $recommendedProd['weightage']
                    );
                    $onDuplicateKeyUserProdRecommendationUpdate = array_merge($userProdRecommendation, array('upr_weightage' => 'mysql_func_upr_weightage + ' . $recommendedProd['weightage']));
                    FatApp::getDb()->insertFromArray('tbl_user_product_recommendation', $userProdRecommendation, true, array(), $onDuplicateKeyUserProdRecommendationUpdate);
                    //echo FatApp::getDb()->getError();
                }

                /*Product Product Recommendation*/
                $prodRecommendation = array(
                    'ppr_viewing_product_id' => $prodId,
                    'ppr_recommended_product_id' => $val['rab_record_id'],
                    'ppr_weightage' => $recommendedProd['weightage']
                );
                $onDuplicateKeyProdRecommendationUpdate = array_merge($prodRecommendation, array('ppr_weightage' => 'mysql_func_ppr_weightage + ' . $recommendedProd['weightage']));
                FatApp::getDb()->insertFromArray('tbl_product_product_recommendation', $prodRecommendation, true, array(), $onDuplicateKeyProdRecommendationUpdate);
                //echo FatApp::getDb()->getError();
            }
            FatApp::getDb()->deleteRecords(RecommendationActivityBrowsing::DB_TBL, array('smt' => 'rab_session_id = ? and rab_record_type = ?', 'vals' => array($val['rab_session_id'], SmartUserActivityBrowsing::TYPE_PRODUCT)));
            //echo FatApp::getDb()->getError();
        }

        FatApp::getDb()->query('Delete FROM `' . RecommendationActivityBrowsing::DB_TBL . '` where `rab_user_id` = 0 and `rab_last_action_datetime` < date_sub(now(), interval 2 day)');
        FatApp::getDb()->query('Delete FROM `' . RecommendationActivityBrowsing::DB_TBL . '` where `rab_last_action_datetime` < date_sub(now(), interval 4 month)');
        return Labels::getLabel('MSG_SUCCESS', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
    }

    public static function remindBuyerForPendingReviews()
    {
        /* completed orders => orders which are pending feedback */
        $resendReminderInterval = FatApp::getConfig('CONF_REVIEW_REMINDER_INTERVAL', FatUtility::VAR_INT, 15);

        $srch = new OrderProductSearch(0, true);
        $srch->addStatusCondition(unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS")));
        $srch->joinTable('tbl_seller_product_reviews', 'left outer join', 'o.order_id = spr.spreview_order_id and ((op.op_selprod_id = spr.spreview_selprod_id and op.op_is_batch = 0) || (op.op_batch_selprod_id = spr.spreview_selprod_id and op.op_is_batch = 1))', 'spr');
        $srch->addCondition('spr.spreview_id', 'is', 'mysql_func_null', 'and', true);
        $srch->addDirectCondition("(op.op_sent_review_reminder =  " . applicationConstants::NO . " or ( op.op_sent_review_reminder = " . applicationConstants::YES . " AND op.op_review_reminder_count = 1 AND Date_add(op_sent_last_reminder, INTERVAL " . $resendReminderInterval . " day) = '" . date('Y-m-d') . "'))");
        $srch->addMultipleFields(array('op_id', 'order_language_id'));

        $orderProductsNotReviewedYet = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($orderProductsNotReviewedYet)) {
            return Labels::getLabel('MSG_NO_RECORD_FOUND', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }

        $emailNotificationObj = new EmailHandler();
        foreach ($orderProductsNotReviewedYet as $orderProduct) {
            $emailNotificationObj->sendBuyerReviewNotification($orderProduct['op_id'], $orderProduct['order_language_id']);

            if (!FatApp::getDb()->updateFromArray(
                OrderProduct::DB_TBL,
                array('op_sent_review_reminder' => applicationConstants::YES, 'op_sent_last_reminder' => date('Y-m-d'), 'op_review_reminder_count' => 'mysql_func_op_review_reminder_count + 1'),
                array('smt' => 'op_id = ?', 'vals' => array($orderProduct['op_id'])),
                true
            )) {
                return FatApp::getDb()->getError();
            }
            /* $obj = new OrderProduct($orderProduct['op_id']);
            $obj->assignValues(array('op_sent_review_reminder'=>applicationConstants::YES));
            if(!$obj->save()){
            return $obj->getError();
            } */
        }
        return Labels::getLabel('MSG_SUCCESS', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
    }

    public static function firstTimeBuyerDiscount($userId, $orderId)
    {
        /* Called this function when order is paid */
        if (!FatApp::getConfig('CONF_ENABLE_FIRST_TIME_BUYER_DISCOUNT')) {
            return Labels::getLabel('MSG_First_time_buyer_discount_module_is_disabled', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }
        $userId = FatUtility::int($userId);

        $orderSrch = new OrderSearch();
        $orderSrch->joinOrderProduct();
        $orderSrch->addMultipleFields(array('order_language_id', 'order_date_added', 'op_status_id', 'op_qty', 'op_refund_qty'));
        $orderSrch->addCondition('order_id', '=', $orderId);
        $orderSrch->addCondition('order_user_id', '=', $userId);
        $orderProductsData = FatApp::getDb()->fetchAll($orderSrch->getResultSet());
        if ($orderProductsData == false) {
            return Labels::getLabel('MSG_NO_RECORD_FOUND', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }

        $completedOrder = 0;
        if ($orderProductsData) {
            foreach ($orderProductsData as $op) {
                if (in_array($op['op_status_id'], (array) Orders::getVendorOrderPaymentCreditedStatuses())) {
                    if ($op['op_status_id'] == FatApp::getConfig("CONF_RETURN_REQUEST_APPROVED_ORDER_STATUS")) {
                        if ($op['op_qty'] > $op['op_refund_qty']) {
                            $completedOrder++;
                        }
                    } else {
                        $completedOrder++;
                    }
                }
            }
        }

        if ($completedOrder != 1) {
            return;
        }
        $orderLangId = current($orderProductsData)['order_language_id'];

        $srch = new OrderSearch();
        $srch->joinOrderBuyerUser();
        $srch->addCondition('order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);
        $srch->addCondition('order_user_id', '=', $userId);
        $srch->addCondition('order_id', '!=', $orderId);
        $srch->addCondition('order_date_added', '<=', current($orderProductsData)['order_date_added']);
        $srch->addMultipleFields(array('count(order_id) as paidOrderCount'));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());

        if ($row['paidOrderCount'] > 0) {
            return;
        }

        $couponValidaity = FatApp::getConfig('CONF_FIRST_TIME_BUYER_COUPON_VALIDITY', FatUtility::VAR_INT, 0);

        $couponData = array(
            'coupon_identifier' => Labels::getLabel('LBL_DISCOUNT_ON_FIRST_PURCHASE', $orderLangId),
            'coupon_type' => DiscountCoupons::TYPE_DISCOUNT,
            'coupon_code' => uniqid() . base64_encode($userId),
            'coupon_discount_in_percent' => FatApp::getConfig('CONF_FIRST_TIME_BUYER_COUPON_IN_PERCENT'),
            'coupon_min_order_value' => FatApp::getConfig('CONF_FIRST_TIME_BUYER_COUPON_MIN_ORDER_VALUE'),
            'coupon_discount_value' => FatApp::getConfig('CONF_FIRST_TIME_BUYER_COUPON_DISCOUNT_VALUE'),
            'coupon_max_discount_value' => FatApp::getConfig('CONF_FIRST_TIME_BUYER_COUPON_MAX_DISCOUNT_VALUE'),
            'coupon_start_date' => date('Y-m-d'),
            'coupon_uses_count' => 1,
            'coupon_uses_coustomer' => 1,
            'coupon_active' => applicationConstants::ACTIVE,
        );
        if ($couponValidaity > 0) {
            $expiryDate = date('Y-m-d', strtotime(date('Y-m-d') . ' +' . $couponValidaity . 'days'));
            $couponData['coupon_end_date'] = $expiryDate;
        }

        $record = new DiscountCoupons();
        $record->assignValues($couponData);
        if ($record->save()) {
            $couponId = $record->getMainTableRecordId();

            if ($couponId > 0 && $userId > 0) {
                $record->addUpdateCouponUser($couponId, $userId);
            }

            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                $langData = array(
                    'coupon_title' => Labels::getLabel('LBL_DISCOUNT_ON_FIRST_PURCHASE', $langId),
                    'couponlang_coupon_id' => $couponId,
                    'couponlang_lang_id' => $langId
                );

                $obj = new DiscountCoupons($couponId);
                $obj->updateLangData($langId, $langData);

                $file_row = AttachedFile::getAttachment(AttachedFile::FILETYPE_FIRST_PURCHASE_DISCOUNT_IMAGE, 0, 0, $langId);
                if (!empty($file_row)) {
                    unset($file_row['afile_id']);
                    unset($file_row['afile_updated_at']);
                    $file_row['afile_record_id'] = $couponId;
                    $file_row['afile_type'] = AttachedFile::FILETYPE_DISCOUNT_COUPON_IMAGE;
                    $attachedFile = new AttachedFile();
                    $attachedFile->assignValues($file_row);
                    $attachedFile->addNew(array(), $file_row);
                }
            }
            $emailNotificationObj = new EmailHandler();
            $emailNotificationObj->sendDiscountCouponNotification($couponId, $userId, $orderLangId);
        }

        return Labels::getLabel('MSG_SUCCESS', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
    }

    public static function birthdayRewardPoints()
    {
        if (!FatApp::getConfig('CONF_ENABLE_BIRTHDAY_DISCOUNT_REWARDS')) {
            return Labels::getLabel('MSG_DISABLED_BIRTHDAY_DISCOUNT_REWARDS', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }

        $currentDay = date('d');

        if (FatApp::getConfig("CONF_CRON_BIRTHDAY_REWARD_DAY", FatUtility::VAR_INT, 0) != $currentDay) {
            $conArr = array('CONF_CRON_BIRTHDAY_REWARD_DAY' => $currentDay, 'CONF_CRON_BIRTHDAY_REWARD_LAST_EXECUTED_USERID' => 0);
            foreach ($conArr as $key => $val) {
                $assignValues = array('conf_name' => $key, 'conf_val' => $val);
                FatApp::getDb()->insertFromArray(
                    static::DB_TBL_CONFIGURATION,
                    $assignValues,
                    false,
                    array(),
                    $assignValues
                );
            }
        }

        $srch = User::getSearchObject();
        $srch->joinTable(Credential::DB_TBL, 'LEFT OUTER JOIN', 'uc.' . Credential::DB_TBL_PREFIX . 'user_id = u.user_id', 'uc');
        $srch->addCondition('uc.credential_active', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('uc.credential_verified', '=',  'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('u.user_is_buyer', '=',  'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('u.user_id', '>', FatApp::getConfig("CONF_CRON_BIRTHDAY_REWARD_LAST_EXECUTED_USERID"));
        $srch->addCondition("mysql_func_DATE_FORMAT(user_dob,'%m-%d')", '=', "mysql_func_DATE_FORMAT(NOW(),'%m-%d')", 'AND', true);
        $srch->addMultipleFields(array('u.user_id', 'u.user_dob', 'u.user_name'));
        //$srch->addCondition('mysql_func_MONTH(user_dob)','=','mysql_func_MONTH(NOW())','AND',true);
        //$srch->addCondition('mysql_func_DAY(user_dob)','=','mysql_func_DAY(NOW())','AND',true);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetchAll($rs, 'user_id');
        if (empty($row)) {
            return Labels::getLabel('MSG_NO_RECORD_FOUND', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }

        $urpComments = Labels::getLabel("MSG_EARNED_REWARD_POINTS_ON_BIRTHDAY", FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        $expiryDate = date('Y-m-d', strtotime(date('Y-m-d') . ' +' . FatApp::getConfig('CONF_BIRTHDAY_REWARD_POINTS_VALIDITY') . 'days'));
        foreach ($row as $userId => $user) {
            $rewardsRecord = new UserRewards();
            $rewardsRecord->assignValues(
                array(
                    'urp_user_id' => $userId,
                    'urp_points' => FatApp::getConfig('CONF_BIRTHDAY_REWARD_POINTS'),
                    'urp_comments' => $urpComments,
                    'urp_used' => 0,
                    'urp_date_expiry' => $expiryDate
                )
            );
            if ($rewardsRecord->save()) {
                $urpId = $rewardsRecord->getMainTableRecordId();

                $assignValues = array('conf_name' => 'CONF_CRON_BIRTHDAY_REWARD_LAST_EXECUTED_USERID', 'conf_val' => $userId);
                FatApp::getDb()->insertFromArray(
                    static::DB_TBL_CONFIGURATION,
                    $assignValues,
                    false,
                    array(),
                    $assignValues
                );

                $emailObj = new EmailHandler();
                $emailObj->sendRewardPointsNotification(FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1), $urpId);
            } else {
                return $rewardsRecord->getError();
            }
        }
        return Labels::getLabel('MSG_SUCCESS', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
    }

    public static function rewardsOnPurchase($orderId)
    {
        if (!FatApp::getConfig('CONF_ENABLE_REWARDS_ON_PURCHASE', FatUtility::VAR_INT, 0)) {
            return Labels::getLabel('MSG_REWARDS_ON_PURCHASE_MODULE_IS_DISABLED', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }

        $srch = new OrderProductSearch(0, true);
        $srch->joinPaymentMethod();
        $srch->addCondition('o.order_id', '=', $orderId);
        $cnd = $srch->addCondition('o.order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);
        $cnd->attachCondition('plugin_code', '=', 'cashondelivery');
        $cnd->attachCondition('plugin_code', '=', 'payatstore');
        $srch->addCondition('op.op_status_id', 'not in', unserialize(FatApp::getConfig("CONF_COMPLETED_ORDER_STATUS")));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $res = FatApp::getDb()->fetch($rs);

        if (!$res == false) {
            return;
        }

        $srch = new OrderSearch();
        $srch->joinOrderBuyerUser();
        $srch->joinOrderPaymentMethod();
        $cnd = $srch->addCondition('order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);
        $cnd->attachCondition('plugin_code', '=', 'cashondelivery');
        $cnd->attachCondition('plugin_code', '=', '');
        $srch->addCondition('order_id', '=', $orderId);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if ($row == false) {
            return;
        }

        $srch = RewardsOnPurchase::getSearchObject();
        $srch->addCondition('rop_purchase_upto', '<=', $row['order_net_amount']);
        $srch->addMultipleFields(array('rop_purchase_upto', 'rop_reward_point'));
        $srch->addOrder('rop_purchase_upto', 'desc');
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $rewardPoint = FatApp::getDb()->fetch($rs);


        if ($rewardPoint == false) {
            return false;
        }

        $urpComments = CommonHelper::replaceStringData(Labels::getLabel("MSG_EARNED_REWARD_POINTS_ON_PURCHASE_OF_ORDER_ID_{ORDER-ID}", FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1)), ['{ORDER-ID}' => $row['order_number']]);
        $expiryDate = date('Y-m-d', strtotime(date('Y-m-d') . ' +' . FatApp::getConfig('CONF_REWARDS_VALIDITY_ON_PURCHASE') . 'days'));

        $rewardsRecord = new UserRewards();
        $rewardsRecord->assignValues(
            array(
                'urp_user_id' => $row['order_user_id'],
                'urp_points' => $rewardPoint['rop_reward_point'],
                'urp_comments' => $urpComments,
                'urp_used' => 0,
                'urp_date_expiry' => $expiryDate
            )
        );
        if ($rewardsRecord->save()) {
            $urpId = $rewardsRecord->getMainTableRecordId();

            /* $assignValues = array('conf_name'=>'CONF_CRON_BUYING_YEAR_LAST_EXE_USERID','conf_val'=>$userId);
            FatApp::getDb()->insertFromArray(
            static::DB_TBL_CONFIGURATION,$assignValues,false,array(),$assignValues
            ); */

            $emailObj = new EmailHandler();
            $emailObj->sendRewardPointsNotification(FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1), $urpId);
            return Labels::getLabel('MSG_SUCCESS', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }
    }

    public static function buyingInAnYearRewards()
    {
        if (!FatApp::getConfig('CONF_ENABLE_BUYING_IN_AN_YEAR_REWARDS', FatUtility::VAR_INT, 0)) {
            return Labels::getLabel('MSG_MODULE_DISABLED', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }

        $prevYear = date('Y') - 1;
        $startDate = $prevYear . '-01-01';
        $endDate = $prevYear . '-12-31';

        if (FatApp::getConfig("CONF_CRON_BUYING_YEAR", FatUtility::VAR_INT, 0) != $prevYear) {
            $conArr = array('CONF_CRON_BUYING_YEAR' => $prevYear, 'CONF_CRON_BUYING_YEAR_LAST_EXE_USERID' => 0);
            foreach ($conArr as $key => $val) {
                $assignValues = array('conf_name' => $key, 'conf_val' => $val);
                FatApp::getDb()->insertFromArray(
                    static::DB_TBL_CONFIGURATION,
                    $assignValues,
                    false,
                    array(),
                    $assignValues
                );
            }
        }

        $statusArr = implode(',', unserialize(FatApp::getConfig("CONF_BUYING_YEAR_REWARD_ORDER_STATUS")));

        $srch = new OrderProductSearch();
        $srch->joinOrders();
        $srch->joinOrderUser();
        $srch->addCondition('order_payment_status', '=', 'mysql_func_' . ORDERS::ORDER_PAYMENT_PAID, 'AND', true);
        $srch->addStatusCondition(array($statusArr));
        $srch->addCondition('order_user_id', '>', FatApp::getConfig("CONF_CRON_BUYING_YEAR_LAST_EXE_USERID"));
        $srch->addCondition('op_completion_date', '>=', $startDate . ' 00:00:00');
        $srch->addCondition('op_completion_date', '<=', $endDate . ' 23:59:59');
        $srch->addMultipleFields(array('order_user_id', 'sum(((op_qty - op_refund_qty)*op_unit_price) - op_refund_amount) as buyingPrice'));
        $srch->addGroupBy('order_user_id');
        $srch->addOrder('order_user_id', 'ASC');
        $srch->setPageSize(50);
        $srch->addHaving('buyingPrice', '>=', FatApp::getConfig('CONF_BUYING_IN_AN_YEAR_MIN_VALUE'));

        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetchAll($rs, 'order_user_id');

        if (empty($row)) {
            return Labels::getLabel('MSG_NO_RECORD_FOUND', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }

        $urpComments = Labels::getLabel("MSG_EARNED_REWARD_POINTS_ON_LAST_YEAR_BUYING.", FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        $expiryDate = date('Y-m-d', strtotime(date('Y-m-d') . ' +' . FatApp::getConfig('CONF_BUYING_IN_AN_YEAR_REWARD_POINTS_VALIDITY') . 'days'));
        foreach ($row as $userId => $user) {
            $rewardsRecord = new UserRewards();
            $rewardsRecord->assignValues(
                array(
                    'urp_user_id' => $userId,
                    'urp_points' => FatApp::getConfig('CONF_BUYING_IN_AN_YEAR_REWARD_POINTS'),
                    'urp_comments' => $urpComments,
                    'urp_used' => 0,
                    'urp_date_expiry' => $expiryDate
                )
            );
            if ($rewardsRecord->save()) {
                $urpId = $rewardsRecord->getMainTableRecordId();

                $assignValues = array('conf_name' => 'CONF_CRON_BUYING_YEAR_LAST_EXE_USERID', 'conf_val' => $userId);
                FatApp::getDb()->insertFromArray(
                    static::DB_TBL_CONFIGURATION,
                    $assignValues,
                    false,
                    array(),
                    $assignValues
                );

                $emailObj = new EmailHandler();
                $emailObj->sendRewardPointsNotification(FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1), $urpId);
            }
        }
        return Labels::getLabel('MSG_SUCCESS', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
    }

    public static function chargeWalletForPromotions()
    {
        /* Promotion Charges */
        $prmSrch = new PromotionSearch();
        $prmSrch->joinPromotionCharge();
        $prmSrch->addGroupBy('promotion_id');
        $prmSrch->addLastChargeCondition();
        $prmSrch->addMultipleFields(array('promotion_id', 'promotion_user_id ', "IFNULL(MAX(pcharge_end_piclick_id),0) as end_click_id", "IFNULL(MAX(pcharge_end_date),'0000-00-00') as charge_till_date"));
        $rs = $prmSrch->getResultSet();
        $promotions = FatApp::getDb()->fetchAll($rs);

        $prmObj = new Promotion();
        foreach ($promotions as $pKey => $pVal) {
            $promotionId = $pVal['promotion_id'];
            $prChargeSummary = new SearchBase(Promotion::DB_TBL_ITEM_CHARGES, 'pci');
            $prChargeSummary->joinTable(Promotion::DB_TBL_CLICKS, 'LEFT JOIN', 'pcl.pclick_id=pci.picharge_pclick_id', 'pcl');
            $prChargeSummary->joinTable(Promotion::DB_TBL, 'LEFT JOIN', 'p.promotion_id=pcl.pclick_promotion_id', 'p');
            $prChargeSummary->addCondition('promotion_id', '=', $promotionId);
            $prChargeSummary->addCondition('picharge_id', '>', $pVal['end_click_id']);
            $prChargeSummary->addMultipleFields(
                array(
                    "sum(picharge_cost) as total_cost", "min(picharge_id) as start_click_id", "max(picharge_id) as end_click_id", "MIN(picharge_datetime) as start_click_date",
                    "MAX(picharge_datetime) as end_click_date",    "count(picharge_id) as total_clicks",
                )
            );
            $prChargeSummary->addGroupBy('pclick_promotion_id');

            $rs = $prChargeSummary->getResultSet();
            $promotionClicks = FatApp::getDb()->fetch($rs);

            if ($promotionClicks) {
                // Get User Wallet Balance
                $userId = $pVal['promotion_user_id'];
                /* $txnObj = new Transactions();
                $accountSummary = $txnObj->getTransactionSummary($userId); */
                //$balance = $accountSummary['total_earned'] - $accountSummary['total_used'];

                $balance = User::getUserBalance($userId);

                if ($balance < $promotionClicks['total_cost']) {
                    $emailObj = new EmailHandler();
                    $emailObj->sendLowBalancePromotionalNotification(FatApp::getConfig('CONF_DEFAULT_SITE_LANG'), $pVal['promotion_user_id'], $balance);
                    //continue;
                }


                if ($promotionClicks['total_cost'] > 0) {
                    $data = array(
                        'user_id' => $pVal['promotion_user_id'],
                        'promotion_id' => $promotionId,
                        'total_cost' => $promotionClicks['total_cost'],
                        'total_clicks' => $promotionClicks['total_clicks'],
                        'start_click_id' => $promotionClicks['start_click_id'],
                        'end_click_id' => $promotionClicks['end_click_id'],
                        'start_click_date' => $promotionClicks['start_click_date'],
                        'end_click_date' => $promotionClicks['end_click_date'],
                    );

                    $prmObj->addUpdatePromotionCharges($data, FatApp::getConfig('CONF_DEFAULT_SITE_LANG'));
                }
            }
        }
    }

    public static function sendReminderSubscriptionEmail()
    {
        /* Promotion Charges */
        /* [---- Reminder Subscription Email --- */
        if (!FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            return;
        }
        $currentDay = date('d');
        if (FatApp::getConfig("CONF_CRON_SUBSCRIPTION_REMINDER_DAY", FatUtility::VAR_INT, 0) != $currentDay) {
            $conArr = array('CONF_CRON_SUBSCRIPTION_REMINDER_DAY' => $currentDay, 'CONF_CRON_SUBSCRIPTION_REMINDER_LAST_EXECUTED_USERID' => 0);
            foreach ($conArr as $key => $val) {
                $assignValues = array('conf_name' => $key, 'conf_val' => $val);
                FatApp::getDb()->insertFromArray(
                    static::DB_TBL_CONFIGURATION,
                    $assignValues,
                    false,
                    array(),
                    $assignValues
                );
            }
        }

        $subscriptionList = OrderSubscription::getSubscriptionEndingList(true);

        if (!empty($subscriptionList) && count($subscriptionList) > 0) {
            foreach ($subscriptionList as $subscriber) {
                $userId = $subscriber['user_id'];
                // $ossubs_id = $subscriber['ossubs_id'];
                $emailObj = new EmailHandler();
                $emailObj->sendSubscriptionReminderEmail($subscriber['order_language_id'], $subscriber);
                $assignValues = array('conf_name' => 'CONF_CRON_SUBSCRIPTION_REMINDER_LAST_EXECUTED_USERID', 'conf_val' => $userId);
                FatApp::getDb()->insertFromArray(
                    static::DB_TBL_CONFIGURATION,
                    $assignValues,
                    false,
                    array(),
                    $assignValues
                );
            }
        }
    }

    public static function autoRenewSubscription()
    {
        /* [---- Auto Renew Subscription ---] */
        if (!FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE')) {
            return;
        }
        $statusArr = Orders::getActiveSubscriptionStatusArr();
        $endDate = date("Y-m-d");
        $srch = new OrderSubscriptionSearch();
        $srch->joinOrders();
        $srch->joinOrderUser();
        $srch->joinSubscription();
        $srch->joinPackage();
        $srch->addCondition('order_payment_status', '=', 'mysql_func_' . ORDERS::ORDER_PAYMENT_PAID, 'AND', true);
        $srch->addCondition('ossubs_status_id', 'in', $statusArr);
        $srch->addCondition('ossubs_till_date', '<=', $endDate);
        $srch->addCondition('ossubs_till_date', '!=', '0000-00-00');
        $srch->addCondition('ossubs_type', '=', 'mysql_func_' . SellerPackages::PAID_TYPE, 'AND', true);
        $srch->addCondition('user_autorenew_subscription', '=', 1);
        $srch->addMultipleFields(array('order_user_id', 'order_language_id', 'order_language_code', 'order_currency_id', 'order_id', 'order_number', 'ossubs_id', 'spackage_type', 'spplan_price', 'spplan_discount', 'spackage_images_per_product', 'spackage_products_allowed', 'spackage_inventory_allowed', 'spackage_rfq_offers_allowed', 'ossubs_plan_id', 'spplan_interval', 'spplan_frequency', 'spackage_commission_rate', 'ossubs_price'));

        /* $srch->addGroupBy('order_user_id');  */
        $srch->addOrder('ossubs_id', 'desc');

        $rs = $srch->getResultSet();
        $activeSusbscriptions = FatApp::getDb()->fetchAll($rs, 'ossubs_id');

        if (empty($activeSusbscriptions)) {
            return;
        }

        foreach ($activeSusbscriptions as $activeSub) {
            $userId = $activeSub['order_user_id'];
            $userBalance = User::getUserBalance($userId);
            $renewPayable = SellerPackagePlans::getPlanPayableAmountFromRow($activeSub);

            if ($userBalance < $renewPayable) {
                $emailObj = new EmailHandler();
                $emailObj->sendLowBalanceSubscriptionNotification($activeSub['order_language_id'], $userId, $activeSub['ossubs_price']);
                continue;
                //Send Less Balance Email
            }

            $orderData = array();
            /* add Order Data[ */
            $order_id = 0;


            $orderData['order_id'] = $order_id;
            $orderData['order_number'] = false;
            $orderData['order_user_id'] = $userId;
            /* $orderData['order_user_name'] = $userDataArr['user_name'];
            $orderData['order_user_email'] = $userDataArr['credential_email'];
            $orderData['order_user_phone'] = $userDataArr['user_phone']; */
            $orderData['order_payment_status'] = Orders::ORDER_PAYMENT_PENDING;
            $orderData['order_date_added'] = date('Y-m-d H:i:s');
            $orderData['order_type'] = Orders::ORDER_SUBSCRIPTION;



            /* order extras[ */
            $orderData['extra'] = array(
                'oextra_order_id' => $order_id,
                'order_ip_address' => $_SERVER['REMOTE_ADDR']
            );

            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $orderData['extra']['order_forwarded_ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $orderData['extra']['order_forwarded_ip'] = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $orderData['extra']['order_forwarded_ip'] = '';
            }

            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $orderData['extra']['order_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            } else {
                $orderData['extra']['order_user_agent'] = '';
            }

            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $orderData['extra']['order_accept_language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            } else {
                $orderData['extra']['order_accept_language'] = '';
            }
            /* ] */

            /* $languageRow = Language::getAttributesById($activeSub['order_language_id']); */
            $orderData['order_language_id'] = $activeSub['order_language_id'];
            $orderData['order_language_code'] = $activeSub['order_language_code'];

            $currencyRow = Currency::getAttributesById($activeSub['order_currency_id']);
            $orderData['order_currency_id'] = $activeSub['order_currency_id'];
            $orderData['order_currency_code'] = $currencyRow['currency_code'];
            $orderData['order_currency_value'] = $currencyRow['currency_value'];

            $orderData['order_user_comments'] = '';
            $orderData['order_admin_comments'] = '';

            $orderData['order_reward_point_used'] = 0;
            $orderData['order_reward_point_value'] = 0;




            $orderData['order_net_amount'] = $renewPayable;
            $orderData['order_wallet_amount_charge'] = $renewPayable;

            // Discussin Required
            $orderData['order_cart_data'] = '';

            $allLanguages = Language::getAllNames();
            //$productSelectedShippingMethodsArr = $this->cartObj->getProductShippingMethod();

            $orderLangData = array();

            $orderData['orderLangData'] = $orderLangData;
            $subscriptionLangData = array();
            foreach ($allLanguages as $lang_id => $language_name) {
                $subscriptionInfo = OrderSubscription::getAttributesByLangId($lang_id, $activeSub['ossubs_id']);


                $op_subscription_title = $subscriptionInfo['ossubs_subscription_name'];



                $subscriptionLangData[$lang_id] = array(
                    'ossubslang_lang_id' => $lang_id,
                    'ossubs_subscription_name' => $op_subscription_title,
                );
            }

            $orderData['subscriptions'][SubscriptionCart::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT . $activeSub['ossubs_plan_id']] = array(


                OrderSubscription::DB_TBL_PREFIX . 'price' => $renewPayable,
                OrderSubscription::DB_TBL_PREFIX . 'images_allowed' => $activeSub['spackage_images_per_product'],
                OrderSubscription::DB_TBL_PREFIX . 'inventory_allowed' => $activeSub['spackage_inventory_allowed'],
                OrderSubscription::DB_TBL_PREFIX . 'products_allowed' => $activeSub['spackage_products_allowed'],
                OrderSubscription::DB_TBL_PREFIX . 'rfq_offers_allowed' => $activeSub['spackage_rfq_offers_allowed'],
                OrderSubscription::DB_TBL_PREFIX . 'plan_id' => $activeSub['ossubs_plan_id'],
                OrderSubscription::DB_TBL_PREFIX . 'type' => $activeSub['spackage_type'],
                OrderSubscription::DB_TBL_PREFIX . 'interval' => $activeSub['spplan_interval'],
                OrderSubscription::DB_TBL_PREFIX . 'frequency' => $activeSub['spplan_frequency'],
                OrderSubscription::DB_TBL_PREFIX . 'commission' => $activeSub['spackage_commission_rate'],
                OrderSubscription::DB_TBL_PREFIX . 'status_id' => FatApp::getConfig("CONF_DEFAULT_ORDER_STATUS"),
                'subscriptionsLangData' => $subscriptionLangData,
            );

            $adjustAmount = 0;
            $discount = 0;
            $rewardPoints = 0;
            $usedRewardPoint = 0;

            //CommonHelper::printArray($cartSubscription); die();
            $orderData['subscrCharges'][SubscriptionCart::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT . $activeSub['ossubs_plan_id']] = array(

                OrderProduct::CHARGE_TYPE_DISCOUNT => array(
                    'amount' => 0 /*[Should be negative value]*/
                ),
                OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT => array(
                    'amount' => 0 /*[Should be negative value]*/
                ),
                OrderProduct::CHARGE_TYPE_ADJUST_SUBSCRIPTION_PRICE => array(
                    'amount' => 0 /*[Should be negative value]*/
                ),
            );
            /* [ Add order Type[ */
            $orderData['order_type'] = Orders::ORDER_SUBSCRIPTION;
            $orderData['order_renew'] = 1;
            /* ] */
            $orderObj = new Orders();

            if ($orderObj->addUpdateOrder($orderData, $activeSub['order_language_id'])) {
                $order_id = $orderObj->getOrderId();
                $orderPaymentObj = new OrderPayment($order_id);
                $orderPaymentObj->chargeUserWallet($renewPayable);
            }
        }
    }

    public static function autoDownloadProductImage()
    {
        $row = AttachedFile::getTempImages(150);
        if ($row == false) {
            return;
        }

        foreach ($row as $val) {
            $image_name = AttachedFile::getImageName($val['afile_physical_path'], $val);
            if (!$image_name || $image_name = '') {
                continue;
            }

            $imgArr = array(
                'afile_downloaded' => applicationConstants::YES
            );
            $where = array('smt' => 'afile_id = ?', 'vals' => array($val['afile_id']));
            FatApp::getDb()->updateFromArray(AttachedFile::DB_TBL_TEMP, $imgArr, $where);
        }

        return Labels::getLabel('MSG_SUCCESS', FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
    }

    public static function remindBuyerForProductsInCart()
    {
        $sentCartReminderCount = FatApp::getConfig('CONF_SENT_CART_REMINDER_COUNT', FatUtility::VAR_INT, 2);
        $buyerReminderInterval = FatApp::getConfig('CONF_REMINDER_INTERVAL_PRODUCTS_IN_CART', FatUtility::VAR_INT, 15);

        $srch = new SearchBase('tbl_user_cart', 'uc');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'u.user_id LIKE usercart_user_id', 'u');
        $srch->joinTable(Credential::DB_TBL, 'INNER JOIN', 'ucr.' . Credential::DB_TBL_PREFIX . 'user_id = u.user_id', 'ucr');
        $srch->addMultipleFields(array('uc.*', 'user_id', 'user_name', 'user_phone_dcode', 'user_phone', 'credential_email'));
        $srch->addCondition('ucr.credential_active', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('ucr.credential_verified', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('u.user_is_buyer', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('usercart_type', '=', 'mysql_func_' . Cart::TYPE_PRODUCT, 'AND', true);
        $srch->addCondition('usercart_sent_reminder', '<', $sentCartReminderCount);
        $srch->addCondition('usercart_added_date', '<=', 'mysql_func_DATE_SUB( NOW(), INTERVAL ' . $buyerReminderInterval . ' DAY )', 'AND', true);
        $srch->addCondition('usercart_reminder_date', '<=', 'mysql_func_DATE_SUB( NOW(), INTERVAL ' . $buyerReminderInterval . ' DAY )', 'AND', true);

        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetchAll($rs);
        if (empty($row)) {
            return;
        }
        $error = false;
        foreach ($row as $val) {
            $cartDetails = !empty($val["usercart_details"]) ? json_decode($val["usercart_details"], true) : [];
            if (is_array($cartDetails) && count($cartDetails) == 0) {
                continue;
            }
            $dialCode = array_key_exists('user_phone_dcode', $val) ? ValidateElement::formatDialCode($val['user_phone_dcode']) : '';
            $phone = array_key_exists('user_phone', $val) ? $val['user_phone'] : '';
            $data = array("user_id" => $val['usercart_user_id'], "user_name" => $val['user_name'], "user_email" => $val['credential_email'], "link" => UrlHelper::generateFullUrl('Checkout'), 'user_phone_dcode' => $dialCode, 'user_phone' => $phone);

            $email = new EmailHandler();
            if (!$email->remindBuyerForCartItems(CommonHelper::getLangId(), $data)) {
                $error = true;
                $msg = Labels::getLabel("MSG_ERROR_IN_SENDING_CART_REMINDER_EMAIL_TO_BUYER_{EMAIL}", CommonHelper::getLangId());
                $msg = CommonHelper::replaceStringData($msg, ['{EMAIL}' => $val['user_id'] . ' - ' . $val['credential_email']]);
                SystemLog::system($msg, CommonHelper::replaceStringData(Labels::getLabel('LBL_WISHLIST_REMINDER_ERROR_{EMAIL}'), ['{EMAIL}' => $val['credential_email']]));
                continue;
            }


            if (!FatApp::getDb()->updateFromArray('tbl_user_cart', array('usercart_sent_reminder' => 'mysql_func_usercart_sent_reminder + 1', 'usercart_reminder_date' => date('Y-m-d H:i:s')), array('smt' => 'usercart_user_id = ?', 'vals' => array($val['usercart_user_id'])), true)) {
                $error = true;
                $msg = Labels::getLabel("MSG_UNABLE_TO_UPDATE_DB_REGARDING_CART_REMINDER_FOR_BUYER_{EMAIL}", CommonHelper::getLangId());
                $msg = CommonHelper::replaceStringData($msg, ['{EMAIL}' => $val['user_id'] . ' - ' . $val['credential_email']]);
                SystemLog::system($msg, CommonHelper::replaceStringData(Labels::getLabel('LBL_WISHLIST_REMINDER_ERROR_{EMAIL}'), ['{EMAIL}' => $val['credential_email']]));
                continue;
            }
        }

        if ($error) {
            return Labels::getLabel('LBL_WISHLIST_REMINDER_ERROR_OCCURRED._PLEASE_CHECK_SYSTEM_LOG.');
        }
    }

    public static function remindBuyerForProductsInWishlist()
    {
        $sentWishListReminderCount = FatApp::getConfig('CONF_SENT_WISHLIST_REMINDER_COUNT', FatUtility::VAR_INT, 2);
        $buyerReminderInterval = FatApp::getConfig('CONF_REMINDER_INTERVAL_PRODUCTS_IN_WISHLIST', FatUtility::VAR_INT, 15);

        $srch = new UserWishListProductSearch(FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        $srch->joinWishLists();
        $srch->joinTable(User::DB_TBL, 'LEFT OUTER JOIN', 'u.user_id = uwlist_user_id', 'u');
        $srch->joinTable(Credential::DB_TBL, 'LEFT OUTER JOIN', 'ucr.' . Credential::DB_TBL_PREFIX . 'user_id = u.user_id', 'ucr');
        $srch->joinSellerProducts();
        $srch->joinProducts();
        $srch->joinSellers();
        $srch->joinShops();
        $srch->joinProductToCategory();
        $srch->joinSellerSubscription(FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1), true);
        $srch->addSubscriptionValidCondition();
        $srch->addMultipleFields(array('uwlp.*', 'u.user_id', 'u.user_name', 'u.user_phone_dcode', 'u.user_phone', 'ucr.credential_email'));
        $srch->addCondition('ucr.credential_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $srch->addCondition('ucr.credential_verified', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('u.user_is_buyer', '=', 'mysql_func_' . applicationConstants::YES, 'AND', true);
        $srch->addCondition('uwlp_sent_reminder', '<', $sentWishListReminderCount);
        $srch->addCondition('uwlp_added_on', '<=', 'mysql_func_DATE_SUB( NOW(), INTERVAL ' . $buyerReminderInterval . ' DAY )', 'AND', true);
        $srch->addCondition('uwlp_reminder_date', '<=', 'mysql_func_DATE_SUB( NOW(), INTERVAL ' . $buyerReminderInterval . ' DAY )', 'AND', true);
        $srch->addGroupBy('u.user_id');
        $row = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($row)) {
            return;
        }

        $error = false;
        foreach ($row as $val) {
            $dialCode = !empty($row['user_phone_dcode']) ? ValidateElement::formatDialCode($row['user_phone_dcode']) : '';
            $phone = !empty($row['user_phone']) ? $row['user_phone'] : '';
            $data = array("user_id" => $val['user_id'], "user_name" => $val['user_name'], "user_email" => $val['credential_email'], "link" => UrlHelper::generateFullUrl('Account', 'wishlist', [], CONF_WEBROOT_DASHBOARD, null, false, false, false), 'user_phone_dcode' => $dialCode, 'user_phone' => $phone);

            $email = new EmailHandler();
            if (!$email->remindBuyerForWishlistItems(CommonHelper::getLangId(), $data)) {
                $error = true;
                $msg = Labels::getLabel("MSG_ERROR_IN_SENDING_WISHLIST_REMINDER_EMAIL_TO_BUYER_{EMAIL}", CommonHelper::getLangId());
                $msg = CommonHelper::replaceStringData($msg, ['{EMAIL}' => $val['user_id'] . ' - ' . $val['credential_email']]);
                SystemLog::system($msg, CommonHelper::replaceStringData(Labels::getLabel('LBL_WISHLIST_REMINDER_ERROR_{EMAIL}'), ['{EMAIL}' => $val['credential_email']]));
                continue;
            }

            if (!FatApp::getDb()->query('UPDATE tbl_user_wish_list_products uwlp, tbl_user_wish_lists uwl SET uwlp.uwlp_sent_reminder = uwlp_sent_reminder + 1, uwlp.uwlp_reminder_date = NOW() WHERE uwl.uwlist_user_id = ' . $val['user_id'])) {
                $error = true;
                $msg = Labels::getLabel("MSG_UNABLE_TO_UPDATE_DB_REGARDING_WISHLIST_REMINDER_FOR_BUYER_{EMAIL}", CommonHelper::getLangId());
                $msg = CommonHelper::replaceStringData($msg, ['{EMAIL}' => $val['user_id'] . ' - ' . $val['credential_email']]);
                SystemLog::system($msg, CommonHelper::replaceStringData(Labels::getLabel('LBL_WISHLIST_REMINDER_ERROR_{EMAIL}'), ['{EMAIL}' => $val['credential_email']]));
                continue;
            }
        }

        if ($error) {
            return Labels::getLabel('LBL_WISHLIST_REMINDER_ERROR_OCCURRED._PLEASE_CHECK_SYSTEM_LOG.');
        }
    }

    public static function removeGarbageData()
    {
        SystemLog::clearOldLog();

        /* Remove older data from cart */
        FatApp::getDb()->query("Delete FROM `" . Cart::DB_TBL . "` where `usercart_last_session_id` = `usercart_user_id` and usercart_last_used_date < date_sub(now(), interval 1 day)");
        FatApp::getDb()->query("Delete FROM `" . Cart::DB_TBL . "` where `usercart_last_used_date` <= date_sub(now(), interval 1 day) and usercart_details = '[]'");
        FatApp::getDb()->query("Delete FROM `" . Cart::DB_TBL . "` where `usercart_last_used_date` <= date_sub(now(), interval 2 day) and cast(usercart_user_id as UNSIGNED) = 0");
        FatApp::getDb()->query('Delete FROM `' . Cart::DB_TBL . '` where `usercart_last_used_date` < date_sub(now(), interval 4 month)');

        /* Remove older emails from archives */
        FatApp::getDb()->query('Delete FROM `' . FatMailer::DB_TBL_ARCHIVE . '` where `earch_sent_on` IS NOT NULL and `earch_added` < date_sub(now(), interval 6 month)');
        FatApp::getDb()->query('Delete FROM `' . SmsArchive::DB_TBL . '` where `smsarchive_sent_on` < date_sub(now(), interval 6 month)');
    }

    public static function publishGoogleShoppingFeed()
    {
        $activePluginCode = (new Plugin())->getDefaultPluginKeyName(Plugin::TYPE_ADVERTISEMENT_FEED);
        if (empty($activePluginCode)) {
            return;
        }

        $srch = AdsBatch::getSearchObject();
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = adsbatch_user_id AND user_deleted =' . applicationConstants::NO . ' AND user_is_supplier = ' . applicationConstants::YES);
        $srch->joinTable(User::DB_TBL_CRED, 'INNER JOIN', 'credential_user_id = user_id and credential_active = ' . applicationConstants::ACTIVE . ' and credential_verified = ' . applicationConstants::YES);
        $srch->addMultipleFields([
            'adsbatch_id',
            'adsbatch_target_country_id',
            'adsbatch_expired_on',
            'adsbatch_next_execution_on',
            'adsbatch_user_id',
            'adsbatch_lang_id'
        ]);

        if (FatApp::getConfig('CONF_ENABLE_SELLER_SUBSCRIPTION_MODULE', FatUtility::VAR_INT, 0)) {
            $subSrch = new SearchBase(Orders::DB_TBL, 'o');
            $subSrch->joinTable(OrderSubscription::DB_TBL, 'INNER JOIN', 'o.order_id = oss.ossubs_order_id and o.order_type = ' . Orders::ORDER_SUBSCRIPTION . ' and oss.ossubs_status_id =' . FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') . ' and oss.ossubs_till_date >="' . date('Y-m-d') . '"', 'oss');
            $subSrch->addCondition('o.order_payment_status', '=', 'mysql_func_1', 'AND', true);
            $subSrch->doNotCalculateRecords();
            $subSrch->doNotLimitRecords();
            $subSrch->addFld('order_user_id');
            $srch->joinTable('(' . $subSrch->getQuery() . ')', 'INNER JOIN', 'osub.order_user_id = user_id', 'osub');
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(100);
        $srch->addCondition(AdsBatch::DB_TBL_PREFIX . 'status', '!=', AdsBatch::STATUS_DELETED);
        $srch->addOrder('adsbatch_synced_on', 'ASC');
        $srch->addOrder('adsbatch_next_execution_on', 'ASC');
        $srch->addDirectCondition(
            "(adsbatch_next_execution_on = '0000-00-00 00:00:00' 
                                        OR  
                (DATEDIFF(IF(adsbatch_expired_on = '0000-00-00 00:00:00',curdate() + INTERVAL 1 YEAR, adsbatch_expired_on), adsbatch_next_execution_on) > 0
                                                        AND
                    date(adsbatch_next_execution_on) < '" . date('Y-m-d') . "'                                                 
                )        
            )"
        );

        $rs = $srch->getResultSet();
        while ($batch = FatApp::getDb()->fetch($rs)) {
            $adsBatchobj = new AdsBatch($batch['adsbatch_id']);
            $adsBatchobj->assignValues(['adsbatch_synced_on' => date('Y-m-d H:i:s')]);
            if (!$adsBatchobj->save()) {
                continue;
            }

            $shoppingFeedObj = LibHelper::callPlugin($activePluginCode, [CommonHelper::getLangId(), $batch['adsbatch_user_id']], $error, CommonHelper::getLangId());
            if (false === $shoppingFeedObj) {
                continue;
            }

            if (false === $shoppingFeedObj->validateSettings(CommonHelper::getLangId())) {
                continue;
            }

            if (empty($shoppingFeedObj->getSettings())) {
                continue;
            }

            $productData = $adsBatchobj->getBatchDataForFeed($batch['adsbatch_user_id'], $batch['adsbatch_lang_id']);
            if (empty($productData)) {
                continue;
            }

            $expireOn = date('Y-m-d', strtotime("+" . $shoppingFeedObj->getMaxPublishDays() . " days"));
            if ($batch['adsbatch_expired_on'] != '0000-00-00 00:00:00' && FatDate::diff(date('Y-m-d'), $batch['adsbatch_expired_on']) < $shoppingFeedObj->getMaxPublishDays()) {
                $expireOn = date('Y-m-d', strtotime($batch['adsbatch_expired_on']));
            }

            $data = [
                'batchId' => $batch['adsbatch_id'],
                'currency_code' => strtoupper(Currency::getAttributesById(CommonHelper::getCurrencyId(), 'currency_code')),
                'data' => $productData,
                'expire_on' => $expireOn,
            ];

            $response = $shoppingFeedObj->publishBatch($data);
            if (false === $response['status'] || Plugin::RETURN_FALSE === $response['status']) {
                SystemLog::transaction($shoppingFeedObj->getError(), $activePluginCode);
                continue;
            }

            $dataToUpdate = [
                'adsbatch_status' => AdsBatch::STATUS_PUBLISHED,
                'adsbatch_synced_on' => date('Y-m-d H:i:s'),
                'adsbatch_next_execution_on' => $expireOn,
            ];

            $adsBatchobj->assignValues($dataToUpdate);
            if (!$adsBatchobj->save()) {
                continue;
            }
        }
    }

    public static function generateSitemap()
    {
        if ((new Sitemap())->generate()) {
            return Labels::getLabel('MSG_SITEMAP_HAS_BEEN_UPDATED_SUCCESSFULLY.');
        }
        return Labels::getLabel('MSG_UNABLE_TO_UPDATE_SITEMAP.');
    }

    public static function updateValidSubscription()
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
        $srch->joinTable(OrderSubscription::DB_TBL, 'INNER JOIN', 'o.order_id = oss.ossubs_order_id and oss.ossubs_status_id =' . FatApp::getConfig('CONF_DEFAULT_SUBSCRIPTION_PAID_ORDER_STATUS') . " and oss.ossubs_till_date < '" . date('Y-m-d') . "'", 'oss');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'o.order_user_id = u.user_id', 'u');

        $srch->addCondition('u.user_has_valid_subscription', '= ', applicationConstants::YES);
        $srch->addCondition('oss.ossubs_status_id', 'IN ', Orders::getActiveSubscriptionStatusArr());
        $srch->addCondition('o.order_type', '=', 'mysql_func_' . ORDERS::ORDER_SUBSCRIPTION, 'AND', true);
        $srch->addCondition('o.order_payment_status', '=', 'mysql_func_' . Orders::ORDER_PAYMENT_PAID, 'AND', true);

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupBy('o.order_user_id');
        $srch->addFld('o.order_user_id');
        $srch->addMultipleFields(['o.order_user_id']);

        $result = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($result as $user) {
            $assignValues = ['user_has_valid_subscription' => applicationConstants::NO];
            FatApp::getDb()->updateFromArray(User::DB_TBL, $assignValues, array('smt' => 'user_id = ? ', 'vals' => array((int) $user['order_user_id'])));
            $assignValues = ['shop_has_valid_subscription' => applicationConstants::NO];
            FatApp::getDb()->updateFromArray(Shop::DB_TBL, $assignValues, array('smt' => 'shop_user_id = ? ', 'vals' => array((int) $user['order_user_id'])));
        }
        echo 'Done';
    }
}
