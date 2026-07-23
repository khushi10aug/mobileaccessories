<?php

class SelProdReview extends MyAppModel
{
    public const DB_TBL = 'tbl_seller_product_reviews';
    public const DB_TBL_PREFIX = 'spreview_';

    public const DB_TBL_ABUSE = 'tbl_seller_product_reviews_abuse';
    public const DB_TBL_ABUSE_PREFIX = 'spra_';

    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_CANCELLED = 2;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
    }

    public function addSelProdReviewAbuse($data = array(), $onDuplicateUpdateData = array())
    {
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_ABUSE, $data, false, array(), $onDuplicateUpdateData)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public static function getReviewStatusArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId == 0) {
            trigger_error(Labels::getLabel('MSG_Language_Id_not_specified.', $langId), E_USER_ERROR);
        }
        $arr = array(
            static::STATUS_PENDING => Labels::getLabel('LBL_PENDING', $langId),
            static::STATUS_APPROVED => Labels::getLabel('LBL_APPROVED', $langId),
            static::STATUS_CANCELLED => Labels::getLabel('LBL_CANCELLED', $langId),
        );
        return $arr;
    }

    public static function getBuyerAllowedOrderReviewStatuses()
    {
        return unserialize(FatApp::getConfig("CONF_REVIEW_READY_ORDER_STATUS"));
    }

    public static function getSellerTotalReviews(int $userId, bool $getFromLog = false)
    {
        if (true == $getFromLog) {
            $reviews =  Shop::getAttributesByUserId($userId, 'shop_total_reviews');

            if ($reviews == false || 1 > $reviews) {
                return 0;
            }
            return $reviews;
        }

        $srch = SelProdRating::getAvgShopReviewsRatingObj($userId);
        $srch->joinUser();
        $srch->joinSeller(0, $userId);
        $srch->joinSellerProducts();
        $srch->joinProducts();
        $srch->addMultipleFields(array('count(distinct(spreview_id)) as numOfReviews'));
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addGroupby('spreview_seller_user_id');
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        return $sellerRating[$userId]['numOfReviews'] = $record['numOfReviews'] ?? 0;
    }

    public static function updateSellerTotalReviews($userId)
    {
        $totalReviews = self::getSellerTotalReviews($userId);
        FatApp::getDb()->query("Update tbl_shops set `shop_total_reviews` = '" . $totalReviews . "' where shop_user_id = '" . $userId . "'");
    }

    public static function getProductRating($productId)
    {
        $selProdReviewObj = new SelProdReviewSearch();
        $selProdReviewObj->joinSellerProducts();
        $selProdReviewObj->joinSelProdRating();
        $selProdReviewObj->addCondition('ratingtype_type', '=', 'mysql_func_' . RatingType::TYPE_PRODUCT, 'AND', true);
        $selProdReviewObj->addCondition('spreview_product_id', '=', $productId);
        $selProdReviewObj->doNotCalculateRecords();
        $selProdReviewObj->doNotLimitRecords();
        $selProdReviewObj->addGroupBy('spr.spreview_product_id');
        $selProdReviewObj->addCondition('spr.spreview_status', '=', 'mysql_func_' . SelProdReview::STATUS_APPROVED, 'AND', true);
        $selProdReviewObj->addMultipleFields(array('spreview_product_id', "ROUND(AVG(sprating_rating),2) as prod_rating", "count(spreview_id) as totReviews"));
        $record = FatApp::getDb()->fetch($selProdReviewObj->getResultSet());
        return [
            'prod_rating' => $record['prod_rating'] ?? 0,
            'totReviews' => $record['totReviews'] ?? 0
        ];
    }

    public static function updateProductRating($productId)
    {
        $totalReviews = self::getProductRating($productId);
        FatApp::getDb()->query("Update " . Product::DB_TBL . " set `product_total_reviews` = '" . $totalReviews['totReviews'] . "' , `product_rating` = '" . $totalReviews['prod_rating'] . "' where product_id = '" . $productId . "'");
    }

    public static function getProductOrderId($product_id, $loggedUserId)
    {
        $product_id = FatUtility::int($product_id);
        $selProdSrch = SellerProduct::getSearchObject(0);
        $selProdSrch->addCondition('selprod_product_id', '= ', 'mysql_func_' . $product_id, 'AND', true);
        $selProdSrch->addCondition('selprod_active', '= ', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
        $selProdSrch->addCondition('selprod_deleted', '= ', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $selProdSrch->addMultipleFields(array('selprod_id'));
        $selProdSrch->doNotCalculateRecords();
        $rs = $selProdSrch->getResultSet();
        $selprodListing = FatApp::getDb()->fetchAll($rs);
        $selProdList = array();
        foreach ($selprodListing as $key => $val) {
            $selProdList[$key] = $val['selprod_id'];
        }
        $srch = new OrderProductSearch(0, true);
        $allowedReviewStatus = implode(",", SelProdReview::getBuyerAllowedOrderReviewStatuses());
        $allowedSelProdId = implode(",", $selProdList);
        $srch->addDirectCondition('order_user_id =' . $loggedUserId . ' and ( FIND_IN_SET(op_selprod_id,(\'' . $allowedSelProdId . '\')) and op_is_batch = 0) and  FIND_IN_SET(op_status_id,(\'' . $allowedReviewStatus . '\')) ');
        $srch->doNotCalculateRecords();
        /* $srch->addOrder('order_date_added'); */
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return (is_array($row) ? $row : []);
    }

    public static function userHasReviewedProduct(int $userId, int $productId): bool
    {
        $userId = FatUtility::int($userId);
        $productId = FatUtility::int($productId);
        if (1 > $userId || 1 > $productId) {
            return false;
        }
        $srch = new SelProdReviewSearch();
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->setPageSize(1);
        $srch->addCondition('spreview_postedby_user_id', '=', $userId);
        $srch->addCondition('spreview_product_id', '=', $productId);
        $srch->addCondition('spreview_status', '!=', static::STATUS_CANCELLED);
        return !empty(FatApp::getDb()->fetch($srch->getResultSet()));
    }

    public static function canUserSubmitProductReview(int $userId, int $productId): bool
    {
        if (!FatApp::getConfig('CONF_ALLOW_REVIEWS', FatUtility::VAR_INT, 0)) {
            return false;
        }
        $userId = FatUtility::int($userId);
        $productId = FatUtility::int($productId);
        if (1 > $userId || 1 > $productId) {
            return false;
        }
        return !static::userHasReviewedProduct($userId, $productId);
    }


    public static function getStatusClassArr()
    {
        return array(
            static::STATUS_PENDING => applicationConstants::CLASS_INFO,
            static::STATUS_APPROVED => applicationConstants::CLASS_SUCCESS,
            static::STATUS_CANCELLED => applicationConstants::CLASS_DANGER,
        );
    }


    public static function getStatusHtml(int $langId, int $status): string
    {
        $arr = self::getReviewStatusArr($langId);
        $msg = $arr[$status] ?? Labels::getLabel('LBL_N/A', $langId);
        switch ($status) {
            case static::STATUS_PENDING:
                $status = HtmlHelper::INFO;
                break;
            case static::STATUS_APPROVED:
                $status = HtmlHelper::SUCCESS;
                break;
            case static::STATUS_CANCELLED:
                $status = HtmlHelper::DANGER;
                break;
            default:
                $status = HtmlHelper::PRIMARY;
                break;
        }
        return HtmlHelper::getStatusHtml($status, rtrim($msg));
    }
}
