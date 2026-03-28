<?php

class SubscriptionCart extends FatModel
{
    private $subscriptions = array();
    private $SYSTEM_ARR = array();
    private $warning;

    public const SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT = 'SSP_'; /* SPP stands for Seller Package Plan */
    /* SPP stands for Seller Package Plan */


    public function __construct($user_id = 0, $langId = 0)
    {
        parent::__construct();
        $user_id = FatUtility::int($user_id);
        $langId = FatUtility::int($langId);

        $this->scart_lang_id = $langId;
        if (1 > $langId) {
            $this->scart_lang_id = CommonHelper::getLangId();
        }

        $this->scart_user_id = session_id();
        if (UserAuthentication::isUserLogged() || ($user_id > 0)) {
            if ($user_id > 0) {
                $this->scart_user_id = $user_id;
            } else {
                $this->scart_user_id = User::getUserParentId(UserAuthentication::getLoggedUserId(true));
            }
        }

        $srch = new SearchBase('tbl_user_cart');
        $srch->addCondition('usercart_user_id', '=', $this->scart_user_id);
        $srch->addCondition('usercart_type', '=', 'mysql_func_' . Cart::TYPE_SUBSCRIPTION, 'AND', true);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        if ($row = FatApp::getDb()->fetch($rs)) {
            $this->SYSTEM_ARR['subscription_cart'] = json_decode($row["usercart_details"], true);
            if (isset($this->SYSTEM_ARR['subscription_cart']['subscription_shopping_cart'])) {
                $this->SYSTEM_ARR['subscription_shopping_cart'] = $this->SYSTEM_ARR['subscription_cart']['subscription_shopping_cart'];
                unset($this->SYSTEM_ARR['subscription_cart']['subscription_shopping_cart']);
            }
        }

        if (!isset($this->SYSTEM_ARR['subscription_cart']) || !is_array($this->SYSTEM_ARR['subscription_cart'])) {
            $this->SYSTEM_ARR['subscription_cart'] = array();
        }
        if (!isset($this->SYSTEM_ARR['subscription_shopping_cart']) || !is_array($this->SYSTEM_ARR['subscription_shopping_cart'])) {
            $this->SYSTEM_ARR['subscription_shopping_cart'] = array();
        }
    }

    public static function getCartKeyPrefixArr()
    {
        return array(
            static::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT => static::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT,

        );
    }

    public static function getSubscriptionCartUserId()
    {
        $scart_user_id = session_id();
        if (UserAuthentication::isUserLogged()) {
            $scart_user_id = UserAuthentication::getLoggedUserId();
        }
        return $scart_user_id;
    }

    public static function getSubscriptionCartData()
    {
        $user_id = User::getUserParentId(UserAuthentication::getLoggedUserId(true));
        $srch = new SearchBase('tbl_user_cart');
        $srch->addCondition('usercart_user_id', '=', $user_id);
        $srch->addCondition('usercart_type', '=', 'mysql_func_' . Cart::TYPE_SUBSCRIPTION, 'AND', true);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        if ($row = FatApp::getDb()->fetch($rs)) {
            return $row["usercart_details"];
        }
        return;
    }

    public function add($spplan_id = 0)
    {
        /* $spplan_id = FatApp::getPostedData('spplan_id');
        $spackageId = FatApp::getPostedData('spackage_id'); */
        $this->subscription = array();
        $spplan_id = FatUtility::int($spplan_id);

        $this->SYSTEM_ARR['subscription_cart'] = array();
        $this->SYSTEM_ARR['subscription_shopping_cart'] = array();
        $key = static::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT . $spplan_id;
        $key = base64_encode(json_encode($key));
        $this->SYSTEM_ARR['subscription_cart'][$key] = 1;
        $this->updateUserSubscriptionCart();


        return true;
    }

    public function adjustPreviousPlan($langId)
    {
        $adjustedAmount = 0;
        $userId = User::getUserParentId(UserAuthentication::getLoggedUserId(true));
        if (FatApp::getConfig('CONF_ENABLE_ADJUST_AMOUNT_CHANGE_PLAN')) {
            $currentActivePlanDetails = OrderSubscription::getUserCurrentActivePlanDetails(
                $langId,
                $userId,
                array(
                    OrderSubscription::DB_TBL_PREFIX . 'plan_id',
                    OrderSubscription::DB_TBL_PREFIX . 'till_date',
                    OrderSubscription::DB_TBL_PREFIX . 'from_date',
                    OrderSubscription::DB_TBL_PREFIX . 'price',
                    SellerPackages::DB_TBL_PREFIX . 'type'
                )
            );
            $adjustedAmount = 0;
            if ($currentActivePlanDetails) {
                $adjustedAmount = OrderSubscription::getAdjustedAmount($currentActivePlanDetails, $userId);
            }

            $this->updateAdjustableAmount($adjustedAmount);
        } else {
            $this->updateAdjustableAmount($adjustedAmount);
        }
    }

    public function countProducts()
    {
        return count($this->SYSTEM_ARR['subscription_cart']);
    }

    public function hasSusbscription()
    {
        return count($this->SYSTEM_ARR['subscription_cart']);
    }

    public function getSellerSubscriptionData($spplan_id, $siteLangId)
    {
    }

    public function remove($key)
    {
        $this->subscription = array();
        $cartSubscription = $this->getSubscription($this->scart_lang_id);

        if (is_array($cartSubscription)) {
            foreach ($cartSubscription as $sCartKey => $subscription) {
                if (md5($subscription['key']) == $key) {
                    unset($this->SYSTEM_ARR['subscription_cart'][$sCartKey]);


                    break;
                }
            }
        }
        $this->updateUserSubscriptionCart();
        return true;
    }

    public function removeCartKey($key)
    {
        unset($this->SYSTEM_ARR['subscription_cart'][$key]);
        $this->updateUserSubscriptionCart();
        return true;
    }

    public function getSubTotal()
    {
        $cartTotal = 0;
        $susbscriptions = $this->getSubscription($this->scart_lang_id);

        foreach ($susbscriptions as $subscription) {
            $cartTotal += isset($subscription['spplan_payable_price']) ? $subscription['spplan_payable_price'] : (isset($subscription[SellerPackagePlans::DB_TBL_PREFIX . 'price']) ? $subscription[SellerPackagePlans::DB_TBL_PREFIX . 'price'] : 0);
        }
        return $cartTotal;
    }

    public function getSubTotalAfterAdjustment()
    {
        return $this->getSubTotal() - $this->getAdjustableAmount();
    }

    public function getSubscriptionCartFinancialSummary($langId)
    {
        //CommonHelper::printArray($this->SYSTEM_ARR['subscription_cart']); die();
        $susbscriptions = $this->getSubscription($langId);

        $cartTotal = 0;

        //$netTotalWithoutDiscount = 0;
        //$netTotalAfterDiscount = 0;
        $orderPaymentGatewayCharges = 0;

        $cartDiscounts = $this->getCouponDiscounts();

        $cartRewardPoints = $this->getCartRewardPoint();
        $cartAdjustableAmount = $this->getAdjustableAmount();
        $orderNetAmount = 0;
        if (is_array($susbscriptions) && count($susbscriptions)) {
            foreach ($susbscriptions as $susbscription) {
                $cartTotal += isset($susbscription['spplan_payable_price']) ? $susbscription['spplan_payable_price'] : (isset($susbscription[SellerPackagePlans::DB_TBL_PREFIX . 'price']) ? $susbscription[SellerPackagePlans::DB_TBL_PREFIX . 'price'] : 0);
            }
        }

        $userWalletBalance = User::getUserBalance($this->scart_user_id);

        $totalDiscountAmount = (isset($cartDiscounts['coupon_discount_total'])) ? $cartDiscounts['coupon_discount_total'] : 0;
        $orderNetAmount = ($cartTotal) - $totalDiscountAmount - $cartAdjustableAmount;

        $orderNetAmount = $orderNetAmount - CommonHelper::rewardPointDiscount($orderNetAmount, $cartRewardPoints);

        $WalletAmountCharge = ($this->isCartUserWalletSelected()) ? min($orderNetAmount, $userWalletBalance) : 0;
        $orderPaymentGatewayCharges = $orderNetAmount - $WalletAmountCharge;
        $orderPaymentGatewayCharges = round($orderPaymentGatewayCharges, 2);

        $cartSummary = array(
            'cartTotal' => $cartTotal,

            'cartDiscounts' => $cartDiscounts,
            'cartWalletSelected' => $this->isCartUserWalletSelected(),
            'cartRewardPoints' => $cartRewardPoints,
            'cartAdjustableAmount' => $cartAdjustableAmount,
            'orderNetAmount' => round($orderNetAmount, 2),
            'WalletAmountCharge' => $WalletAmountCharge,
            'orderPaymentGatewayCharges' => $orderPaymentGatewayCharges,
        );



        return $cartSummary;
    }

    public function getCouponDiscounts()
    {
        if (!$this->getSubscriptionCartDiscountCoupon()) {
            return false;
        }

        $orderId = isset($_SESSION['subscription_shopping_cart']["order_id"]) ? $_SESSION['subscription_shopping_cart']["order_id"] : false;       
        $couponInfo = DiscountCoupons:: getValidSubscriptionCoupons($this->scart_user_id, $this->scart_lang_id, $this->getSubscriptionCartDiscountCoupon(), $orderId);
     
        $cartSubTotal = $this->getSubTotal();

        if (!empty($couponInfo)) {
            $discountTotal = 0;

            if (empty($couponInfo['products'])) {
                $subTotal = $cartSubTotal;
            } else {
                $subTotal = 0;
                foreach ($this->getSubscription($this->scart_lang_id) as $product) {
                    if (in_array($product[SellerPackagePlans::DB_TBL_PREFIX . 'id'], $couponInfo['products'])) {
                        $line = isset($product['spplan_payable_price']) ? $product['spplan_payable_price'] : $product['spplan_price'];
                        $subTotal += $line;
                    }
                }
            }

            $cartSubTotalAfterAdjustment = $subTotal - $this->getAdjustableAmount();

            if ($couponInfo['coupon_discount_in_percent'] == applicationConstants::FLAT) {
                $couponInfo['coupon_discount_value'] = min($couponInfo['coupon_discount_value'], $subTotal);
            }
            foreach ($this->getSubscription($this->scart_lang_id) as $product) {
                $discount = 0;

                if (empty($couponInfo['products'])) {
                    $status = true;
                } else {
                    if (in_array($product[SellerPackagePlans::DB_TBL_PREFIX . 'id'], $couponInfo['products'])) {
                        $status = true;
                    } else {
                        $status = false;
                    }
                }


                if ($status) {
                    $planLine = isset($product['spplan_payable_price']) ? $product['spplan_payable_price'] : $product['spplan_price'];
                    if ($couponInfo['coupon_discount_in_percent'] == applicationConstants::FLAT) {
                        $discount = $couponInfo['coupon_discount_value'] * ($planLine / $subTotal);
                    } else {
                        $discount = ($planLine / 100) * $couponInfo['coupon_discount_value'];
                    }
                }
                $discountTotal += $discount;
            }
            $discountTotal = min($discountTotal, $cartSubTotalAfterAdjustment);

            // If discount greater than total
            /*if ($discountTotal > $couponInfo['coupon_max_discount_value']) {
                $discountTotal = $couponInfo['coupon_max_discount_value'];
            }*/

            if ($discountTotal > $couponInfo['coupon_max_discount_value'] && $couponInfo['coupon_discount_in_percent'] == applicationConstants::PERCENTAGE) {
                $discountTotal = $couponInfo['coupon_max_discount_value'];
            }
            $selProdDiscountTotal = 0;
            $discountTypeArr = DiscountCoupons::getTypeArr($this->scart_lang_id);

            /*[ Calculate discounts for each Seller Products*/
            $discountedSelProdIds = array();
            $discountedProdGroupIds = array();
            if (empty($couponInfo['products'])) {
                foreach ($this->getSubscription($this->scart_lang_id) as $product) {
                    $planLine = isset($product['spplan_payable_price']) ? $product['spplan_payable_price'] : $product['spplan_price'];
                    $totalSelProdDiscount = round(($discountTotal * $planLine) / $subTotal, 2);
                    $selProdDiscountTotal += $totalSelProdDiscount;
                    $discountedSelProdIds[$product[SellerPackagePlans::DB_TBL_PREFIX . 'id']] = round(($totalSelProdDiscount), 2);
                }
            } else {
                foreach ($this->getSubscription($this->scart_lang_id) as $product) {
                    if (in_array($product[SellerPackagePlans::DB_TBL_PREFIX . 'id'], $couponInfo['products'])) {
                        $planLine = isset($product['spplan_payable_price']) ? $product['spplan_payable_price'] : $product['spplan_price'];
                        $totalSelProdDiscount = round(($discountTotal * $planLine) / $subTotal, 2);
                        $selProdDiscountTotal += $totalSelProdDiscount;
                        $discountedSelProdIds[$product[SellerPackagePlans::DB_TBL_PREFIX . 'id']] = round(($totalSelProdDiscount), 2);
                    }
                }
            }
            /*]*/

            $labelArr = array(
                'coupon_label' => $couponInfo["coupon_title"],
                'coupon_discount_in_percent' => $couponInfo["coupon_discount_in_percent"],
                'max_discount_value' => $couponInfo["coupon_max_discount_value"],
                'coupon_id' => $couponInfo["coupon_id"],
            );

            // If discount greater than total
            /*if ($selProdDiscountTotal > $couponInfo['coupon_max_discount_value']) {
                $selProdDiscountTotal = $couponInfo['coupon_max_discount_value'];
            }*/
            if ($couponInfo['coupon_discount_in_percent'] == applicationConstants::PERCENTAGE) {
                if ($selProdDiscountTotal > $couponInfo['coupon_max_discount_value']) {
                    $selProdDiscountTotal = $couponInfo['coupon_max_discount_value'];
                }
            } elseif ($couponInfo['coupon_discount_in_percent'] == applicationConstants::FLAT) {
                if ($selProdDiscountTotal > $couponInfo["coupon_discount_value"]) {
                    $selProdDiscountTotal = $couponInfo["coupon_discount_value"];
                }
            }

            $couponData = array(
                'coupon_discount_type' => $couponInfo["coupon_type"],
                'coupon_code' => $couponInfo["coupon_code"],
                'coupon_discount_value' => $couponInfo["coupon_discount_value"],
                'coupon_discount_total' => $selProdDiscountTotal,
                'coupon_info' => json_encode($labelArr),
                'discountedSelProdIds' => $discountedSelProdIds,
                'discountedProdGroupIds' => $discountedProdGroupIds,
            );
        }

        if (empty($couponData)) {
            return false;
        }
        return $couponData;
    }

    public function updateCartWalletOption($val)
    {
        $this->SYSTEM_ARR['subscription_shopping_cart']['Pay_from_wallet'] = $val;
        $this->updateUserSubscriptionCart();
        return true;
    }

    public function updateCartUseRewardPoints($val)
    {
        $this->SYSTEM_ARR['subscription_shopping_cart']['reward_points'] = $val;
        $this->updateUserSubscriptionCart();
        return true;
    }

    public function removeUsedRewardPoints()
    {
        unset($this->SYSTEM_ARR['subscription_shopping_cart']['reward_points']);
        $this->updateUserSubscriptionCart();
        return true;
    }

    public function getCartRewardPoint()
    {
        return isset($this->SYSTEM_ARR['subscription_shopping_cart']['reward_points']) ? $this->SYSTEM_ARR['subscription_shopping_cart']['reward_points'] : 0;
    }

    public function updateCartDiscountCoupon($val)
    {
        $this->SYSTEM_ARR['subscription_shopping_cart']['discount_coupon'] = $val;
        $this->updateUserSubscriptionCart();
        return true;
    }

    public function getAdjustableAmount()
    {
        return isset($this->SYSTEM_ARR['subscription_shopping_cart']['adjusted_amount']) ? $this->SYSTEM_ARR['subscription_shopping_cart']['adjusted_amount'] : 0;
    }

    public function updateAdjustableAmount($adjustableAmount)
    {
        $subTotal = 0;
        foreach ($this->getSubscription($this->scart_lang_id) as $product) {
            $subTotal += isset($product['spplan_payable_price']) ? $product['spplan_payable_price'] : (isset($product['spplan_price']) ? $product['spplan_price'] : 0);
        }
        $maxAdjustableAmount = $subTotal;
        if ($maxAdjustableAmount < $adjustableAmount) {
            $adjustableAmount = $maxAdjustableAmount;
        }

        $this->SYSTEM_ARR['subscription_shopping_cart']['adjusted_amount'] = $adjustableAmount;
        $this->updateUserSubscriptionCart();
        return true;
    }

    public function removeCartDiscountCoupon()
    {
        unset($this->SYSTEM_ARR['subscription_shopping_cart']['discount_coupon']);
        $this->updateUserSubscriptionCart();
        return true;
    }

    public function getSubscriptionCartDiscountCoupon()
    {
        return isset($this->SYSTEM_ARR['subscription_shopping_cart']['discount_coupon']) ? $this->SYSTEM_ARR['subscription_shopping_cart']['discount_coupon'] : '';
    }

    public function isDiscountCouponSet()
    {
        return !empty($this->SYSTEM_ARR['subscription_shopping_cart']['discount_coupon']);
    }

    public function isCartUserWalletSelected()
    {
        return (isset($this->SYSTEM_ARR['subscription_shopping_cart']['Pay_from_wallet']) && intval($this->SYSTEM_ARR['subscription_shopping_cart']['Pay_from_wallet']) == 1) ? 1 : 0;
    }

    public function updateUserSubscriptionCart()
    {
        if (isset($this->scart_user_id)) {
            $record = new TableRecord('tbl_user_cart');

            $cart_arr = $this->SYSTEM_ARR['subscription_cart'];

            if (isset($this->SYSTEM_ARR['subscription_shopping_cart']) && is_array($this->SYSTEM_ARR['subscription_shopping_cart']) && (!empty($this->SYSTEM_ARR['subscription_shopping_cart']))) {
                $cart_arr["subscription_shopping_cart"] = $this->SYSTEM_ARR['subscription_shopping_cart'];
            }
            $cart_arr = json_encode($cart_arr);
            $record->assignValues(array("usercart_user_id" => $this->scart_user_id, 'usercart_type' => Cart::TYPE_SUBSCRIPTION, "usercart_details" => $cart_arr, "usercart_added_date" => date('Y-m-d H:i:s')));
            if (!$record->addNew(array(), array('usercart_details' => $cart_arr))) {
                Message::addErrorMessage($record->getError());
                throw new Exception('');
            }
        }
    }

    public function clear()
    {
        $this->subscription = array();

        $this->SYSTEM_ARR['subscription_shopping_cart'] = array();
        unset($_SESSION['subscription_shopping_cart']["order_id"]);
    }

    public static function setCartAttributes($userId = 0)
    {
        $db = FatApp::getDb();


        $userId = FatUtility::int($userId);
        if ($userId == 0) {
            return false;
        }

        $srch = new SearchBase('tbl_user_cart');
        $srch->addCondition('usercart_user_id', '=', session_id());
        $srch->addCondition('usercart_type', '=', 'mysql_func_' . Cart::TYPE_SUBSCRIPTION, 'AND', true);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();

        if (!$row = FatApp::getDb()->fetch($rs)) {
            return false;
        }

        $cartInfo = json_decode($row["usercart_details"], true);

        $cartObj = new SubscriptionCart($userId);

        foreach ($cartInfo as $key => $quantity) {
            $keyDecoded = json_decode(base64_decode($key), true);

            $spplan_id = 0;
            $prodgroup_id = 0;

            if (strpos($keyDecoded, static::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT) !== false) {
                $spplan_id = FatUtility::int(str_replace(static::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT, '', $keyDecoded));
            }


            $cartObj->add($spplan_id);

            $db->deleteRecords('tbl_user_cart', array('smt' => '`usercart_user_id`=? and usercart_type=?', 'vals' => array(session_id(), CART::TYPE_SUBSCRIPTION)));
        }
        $cartObj->updateUserSubscriptionCart();
    }

    public function getSubscription($siteLangId = 0)
    {
        /* if( !$siteLangId ){
        trigger_error(Labels::getLabel('MSG_Language_Id_not_specified.',$this->commonLangId), E_USER_ERROR);
        } */
        if (!$this->subscriptions) {
            $db = FatApp::getDb();
            foreach ($this->SYSTEM_ARR['subscription_cart'] as $key => $quantity) {
                $spplan_id = 0;
                $sellerPlanRow = array();
                // echo $key;die;
                $keyDecoded = json_decode(base64_decode($key), true);
                if (strpos($keyDecoded, static::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT) !== false) {
                    $spplan_id = str_replace(static::SUBSCRIPTION_CART_KEY_PREFIX_PRODUCT, '', $keyDecoded);
                }
                /* Subscription Plan[ */
                if ($spplan_id) {
                    $sellerPlanRow = SellerPackagePlans::getSubscriptionPlanDataByPlanId($spplan_id, $siteLangId);
                    if (!$sellerPlanRow) {
                        $this->removeCartKey($key);
                        continue;
                    }
                    $sellerPlanRow['spplan_payable_price'] = SellerPackagePlans::getPlanPayableAmountFromRow($sellerPlanRow);
                    $this->subscriptions[$key] = $sellerPlanRow;
                }
                /* ] */
                $this->subscriptions[$key]['key'] = $key;
                $this->subscriptions[$key]['spplan_id'] = $spplan_id;
            }
        }
        return $this->subscriptions;
    }
}
