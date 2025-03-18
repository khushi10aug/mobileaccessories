<?php
class CommonHelper extends FatUtility
{
    private static $_lang_id;
    private static $_currency_id;
    private static $_langData = [];
    private static $_currencyData = [];

    public static function initCommonVariables($isAdmin = false)
    {
        self::setClassVariables($isAdmin);

        $currencyNdLangData = CacheHelper::get('currencyNdLangData' .  self::$_currency_id . '-' . self::$_lang_id, CONF_DEF_CACHE_TIME, '.txt');
        if ($currencyNdLangData) {
            $arr = json_decode($currencyNdLangData, true);
            $currencyData = $arr['currencyData'];
            $langData = $arr['langData'];
        } else {
            $currencyData = Currency::getAttributesById(
                self::$_currency_id,
                array('currency_code', 'currency_symbol_left', 'currency_symbol_right', 'currency_value')
            );

            $langData = Language::getAttributesById(
                self::$_lang_id,
                ['language_country_code', 'language_code', 'language_layout_direction']
            );

            $arr = [
                'currencyData' => $currencyData,
                'langData' => $langData
            ];
            CacheHelper::create('currencyNdLangData' . self::$_currency_id . '-' . self::$_lang_id, FatUtility::convertToJson($arr), CacheHelper::TYPE_CURRENCY);
        }

        self::$_langData = $langData;
        self::$_currencyData = $currencyData;
    }

    private static function setClassVariables($isAdmin = false)
    {
        self::$_currency_id = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);

        $langKey = (!$isAdmin) ? 'CONF_DEFAULT_SITE_LANG' : 'CONF_ADMIN_DEFAULT_LANG';
        self::$_lang_id = FatApp::getConfig($langKey, FatUtility::VAR_INT, 1);

        $queryStringData = FatApp::getQueryStringData();
        if (array_key_exists('appuser', $queryStringData) && $queryStringData['appuser'] == 1) {
            self::setAppUser();
            self::$_currency_id = $queryStringData['currency_id'] ?? self::$_currency_id;
            setcookie('defaultSiteCurrency', self::$_currency_id, time() + 3600 * 24 * 10, CONF_WEBROOT_URL);
        }

        if (true === MOBILE_APP_API_CALL) {
            if (!empty($_SERVER['HTTP_X_LANGUAGE_ID'])) {
                self::$_lang_id = FatUtility::int($_SERVER['HTTP_X_LANGUAGE_ID']);
            }

            if (!empty($_SERVER['HTTP_X_CURRENCY_ID']) && Currency::getAttributesById(self::$_currency_id, 'currency_active')) {
                $currencyId = FatUtility::int($_SERVER['HTTP_X_CURRENCY_ID']);
                if (Currency::getAttributesById($currencyId, 'currency_active')) {
                    self::$_currency_id = $currencyId;
                }
            }

            $post = FatApp::getPostedData();

            if (array_key_exists('language', $post)) {
                self::$_lang_id = FatUtility::int($post['language']);
            }

            if (array_key_exists('currency', $post)) {
                self::$_currency_id = FatUtility::int($post['currency']);
            }

            return;
        }

        if ($isAdmin && isset($_COOKIE['defaultAdminSiteLang'])) {
            $languages = Language::getAllNames();
            if (array_key_exists($_COOKIE['defaultAdminSiteLang'], $languages)) {
                self::$_lang_id = FatUtility::int(trim($_COOKIE['defaultAdminSiteLang']));
            }
            return;
        }

        if (!$isAdmin && isset($_COOKIE['defaultSiteLang'])) {
            $languages = Language::getAllNames();
            if (array_key_exists($_COOKIE['defaultSiteLang'], $languages)) {
                self::$_lang_id = FatUtility::int(trim($_COOKIE['defaultSiteLang']));
            }
        }

        if (SYSTEM_LANG_ID > 0 && count(LANG_CODES_ARR) > 1 && 0 < FatApp::getConfig('CONF_LANG_SPECIFIC_URL', FatUtility::VAR_INT, 0) && defined('SYSTEM_FRONT') && SYSTEM_FRONT === true) {
            self::$_lang_id = SYSTEM_LANG_ID;
        }

        if (isset($_COOKIE['defaultSiteCurrency'])) {
            $currencies = Currency::getCurrencyAssoc(self::$_lang_id);
            if (array_key_exists($_COOKIE['defaultSiteCurrency'], $currencies)) {
                self::$_currency_id = FatUtility::int(trim($_COOKIE['defaultSiteCurrency']));
            }
        }
    }

    public static function getAppToken()
    {
        if (array_key_exists('HTTP_X_TOKEN', $_SERVER) && !empty($_SERVER['HTTP_X_TOKEN'])) {
            return ($_SERVER['HTTP_X_TOKEN'] != '') ? $_SERVER['HTTP_X_TOKEN'] : '';
        }
        return '';
    }

    public static function getLangId(): int
    {
        if (1 > self::$_lang_id) {
            $queryStringData = FatApp::getQueryStringData();
            if (array_key_exists('appuser', $queryStringData) && $queryStringData['appuser'] == 1) {
                $langId = $queryStringData['lang_id'] ?? 0;
                if (0 < $langId) {
                    $languages = Language::getAllNames();
                    if (array_key_exists($langId, $languages)) {
                        setcookie('defaultSiteLang', $langId, time() + 3600 * 24 * 10, CONF_WEBROOT_URL);
                        return $langId;
                    }
                }
            }
            return FatUtility::int(FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
        }
        return self::$_lang_id;
    }

    public static function setLangId(int $langId)
    {
        if (1 > $langId) {
            self::$_lang_id =  FatUtility::int(FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1));
            return;
        }
        self::$_lang_id = $langId;
    }

    public static function getLangCode()
    {
        if (isset(self::$_langData['language_code'])) {
            return self::$_langData['language_code'];
        }
        return self::$_langData['language_code'] = Language::getAttributesById(self::$_lang_id, 'language_code');
    }

    public static function getLangCountryCode()
    {
        if (isset(self::$_langData['language_country_code'])) {
            return self::$_langData['language_country_code'];
        }
        return self::$_langData['language_country_code'] = Language::getAttributesById(self::$_lang_id, 'language_country_code');
    }

    public static function getLayoutDirection()
    {
        if (isset(self::$_langData['language_layout_direction'])) {
            return self::$_langData['language_layout_direction'];
        }
        return self::$_langData['language_layout_direction'] = Language::getAttributesById(self::$_lang_id, 'language_layout_direction');
    }

    public static function getCurrencyId()
    {
        return self::$_currency_id;
    }

    public static function getCurrencySymbolLeft()
    {
        if (isset(self::$_currencyData['currency_symbol_left'])) {
            return self::$_currencyData['currency_symbol_left'];
        }
        return self::$_currencyData['currency_symbol_left'] = Currency::getAttributesById(self::$_currency_id, 'currency_symbol_left');
    }

    public static function getCurrencySymbolRight()
    {
        if (isset(self::$_currencyData['currency_symbol_right'])) {
            return self::$_currencyData['currency_symbol_right'];
        }
        return self::$_currencyData['currency_symbol_right'] = Currency::getAttributesById(self::$_currency_id, 'currency_symbol_right');
    }

    public static function getCurrencyCode()
    {
        if (isset(self::$_currencyData['currency_code'])) {
            return self::$_currencyData['currency_code'];
        }

        if (empty(self::$_currency_id)) {
            self::setClassVariables();
        }

        return self::$_currencyData['currency_code'] = Currency::getAttributesById(self::$_currency_id, 'currency_code');
    }

    public static function getCurrencyValue()
    {
        if (isset(self::$_currencyData['currency_value'])) {
            return self::$_currencyData['currency_value'];
        }

        if (empty(self::$_currency_id)) {
            self::setClassVariables();
        }

        return self::$_currencyData['currency_value'] = Currency::getAttributesById(self::$_currency_id, 'currency_value');
    }

    public static function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public static function getAppScreenType()
    {
        $appScreen = applicationConstants::SCREEN_MOBILE;
        if (!empty($_SERVER['HTTP_X_SCREEN_TYPE'])) {
            $appScreen = FatUtility::int($_SERVER['HTTP_X_SCREEN_TYPE']);
        }
        return $appScreen;
    }

    public static function getClientIp()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }

    public static function getUserIdFromCookies()
    {
        $userId = 0;
        if (isset($_COOKIE['uc_id'])) {
            $userId = $_COOKIE['uc_id'];
        }
        return $userId;
    }
    /* public static function encryptPassword($pwd) {
        return md5(PASSWORD_SALT . $pwd . PASSWORD_SALT);
    } */

    public static function canAvailShippingChargesBySeller($opSellerId = 0, $shippedByUserId = 0)
    {
        $opSellerId = FatUtility::int($opSellerId);
        $shippedByUserId = FatUtility::int($shippedByUserId);
        return ($opSellerId > 0 && $opSellerId == $shippedByUserId);
    }

    public static function underMyDevelopment($sessionId = false)
    {
        return !($sessionId && $sessionId != session_id());
    }

    public static function printArray($attr, $exit = false, $sessionId = false)
    {
        if ($sessionId && $sessionId != session_id()) {
            return;
        }

        echo 'IN PRINT Function: <pre>';
        print_r($attr);
        echo '</pre>';

        if ($exit) {
            exit;
        }
    }

    public static function combinationOfElementsOfArr($arr = array(), $useKey = '', $separater = '|', $sortKeys = true)
    {
        $tempArr = array();
        $loopCount = count($arr);

        for ($i = 0; $i < $loopCount; $i++) {
            $count = 0;
            foreach ($arr as $key => $val) {
                if ($count != $i) {
                    continue;
                }
                asort($val[$useKey]);

                if (!empty($tempArr)) {
                    foreach ($tempArr as $tempKey => $tempVal) {
                        foreach ($val[$useKey] as $k => $v) {
                            $tempArr[$tempKey . $separater . $k] = $tempVal . $separater . $v;
                            unset($tempArr[$tempKey]);
                        }
                    }
                } else {
                    foreach ($val[$useKey] as $k => $v) {
                        $tempArr[$k] = $v;
                    }
                }
            }
            $count++;
        }

        if (!$sortKeys) {
            return $tempArr;
        }

        // Custom sorting to maintain the order of the input array
        $sortedTempArr = array();
        foreach ($tempArr as $key => $val) {
            $codeArr = explode($separater, $key);
            // Sort the codeArr based on the order of the input array
            usort($codeArr, function($a, $b) use ($arr, $useKey) {
                $aIndex = $bIndex = -1;
                foreach ($arr as $index => $item) {
                    if (array_key_exists($a, $item[$useKey])) {
                        $aIndex = $index;
                    }
                    if (array_key_exists($b, $item[$useKey])) {
                        $bIndex = $index;
                    }
                }
                return $aIndex - $bIndex;
            });
            $selProdCode = implode($separater, $codeArr);
            $sortedTempArr[$selProdCode] = $val;
        }
        return $sortedTempArr;
    }

    public static function rewardPointDiscount($orderNetAmount, $rewardPoints)
    {
        return min(static::convertRewardPointToCurrency($rewardPoints), $orderNetAmount);
        //return $rewardPoints = static::convertCurrencyToRewardPoint($rewardPointValues);
    }

    public static function orderProductAmount($opArr = array(), $amountType = 'NETAMOUNT', $pricePerItem = false, $userType = false)
    {
        $amount = 0;

        if (empty($opArr)) {
            trigger_error('Order Product Array should not be empty', E_USER_ERROR);
        }


        $cartTotal = $opArr['op_qty'] * $opArr['op_unit_price'];

        switch (strtoupper($amountType)) {
            case 'NET_VENDOR_AMOUNT':
                $amount = $cartTotal + $opArr['op_other_charges'];
                if ($userType == User::USER_TYPE_SELLER) {
                    if ($opArr['op_tax_collected_by_seller'] == 0) {
                        $tax = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount'] : 0;
                        $amount = $amount - $tax;
                    }

                    if (!CommonHelper::canAvailShippingChargesBySeller($opArr['op_selprod_user_id'], $opArr['opshipping_by_seller_user_id'])) {
                        $shippingCharges = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount'] : 0;
                        $amount = $amount - $shippingCharges;
                    }

                    $discount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount']) ? abs($opArr['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount']) : 0;
                    $amount = $amount + abs($discount);

                    $rewardDiscount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT]['opcharge_amount']) ? abs($opArr['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT]['opcharge_amount']) : 0;
                    $amount = $amount + abs($rewardDiscount);
                }
                break;
            case 'NETAMOUNT':
                $amount = $cartTotal + $opArr['op_other_charges'];
                if ($userType == User::USER_TYPE_SELLER) {
                    if ($opArr['op_tax_collected_by_seller'] == 0) {
                        $tax = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount'] : 0;
                        $amount = $amount - $tax;
                    }

                    if (!CommonHelper::canAvailShippingChargesBySeller($opArr['op_selprod_user_id'], $opArr['opshipping_by_seller_user_id'])) {
                        $shippingCharges = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount'] : 0;
                        $amount = $amount - $shippingCharges;
                    }

                    $discount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount']) ? abs($opArr['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount']) : 0;
                    $amount = $amount + $discount;

                    $rewardDiscount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT]['opcharge_amount']) ? abs($opArr['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT]['opcharge_amount']) : 0;
                    $amount = $amount + $rewardDiscount;
                }
                $amount += $opArr['op_rounding_off'];
                break;
            case 'SHIPPING':
                $shippingAmount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount'] : 0;
                if ($userType == User::USER_TYPE_SELLER && !CommonHelper::canAvailShippingChargesBySeller($opArr['op_selprod_user_id'], $opArr['opshipping_by_seller_user_id'])) {
                    $shippingAmount = 0;
                }
                $amount = $shippingAmount;
                break;
            case 'SHIPPING_API':
                /* Charges If seller using admin api to ship*/
                $amount = 0;
                if (CommonHelper::canAvailShippingChargesBySeller($opArr['op_selprod_user_id'], $opArr['opshipping_by_seller_user_id']) && 0 < $opArr['opshipping_plugin_id'] && 1 > $opArr['opshipping_is_seller_plugin'] && 0 < $opArr['opshipping_plugin_charges']) {
                    $shippingResp = OrderProduct::getShippingResponse($opArr['op_id'], OrderProduct::RESPONSE_TYPE_SHIPMENT);
                    if (!empty($shippingResp) && !empty($shippingResp['opr_response'])) {
                        $amount = $opArr['opshipping_plugin_charges'];
                    }
                }
                break;
            case 'REWARDPOINT':
                $amount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT]['opcharge_amount'] : 0;
                break;
            case 'DISCOUNT':
                $amount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount'] : 0;
                break;
            case 'CART_TOTAL':
                $amount = $cartTotal;
                break;
            case 'TAX':
                $amount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount'] : 0;
                if ($userType == User::USER_TYPE_SELLER && $opArr['op_tax_collected_by_seller'] == 0) {
                    $amount = 0;
                }
                break;
            case 'TAXABLE_AMOUNT':
                $amount = $cartTotal + self::orderProductAmount($opArr, 'VOLUME_DISCOUNT');
                $tax = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount'] : 0;
                if ($tax <= 0) {
                    $amount = 0;
                } elseif (($userType == User::USER_TYPE_SELLER && $opArr['op_tax_collected_by_seller'] == 0)) {
                    $amount = 0;
                } elseif ($opArr['op_tax_after_discount']) {
                    $amount += self::orderProductAmount($opArr, 'DISCOUNT');
                }
                break;
            case 'VOLUME_DISCOUNT':
                $amount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT]['opcharge_amount'] : 0;
                break;
        }

        if ($pricePerItem) {
            $amount = round($amount / $opArr['op_qty'], 2);
        }
        return (float) $amount;
    }

    public static function getOrderProductRefundAmtArr($requestRow = array())
    {
        $volumeDiscount = isset($requestRow['charges'][OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT]['opcharge_amount']) ? abs($requestRow['charges'][OrderProduct::CHARGE_TYPE_VOLUME_DISCOUNT]['opcharge_amount']) : 0;
        $shipCharges = isset($requestRow['charges'][OrderProduct::CHARGE_TYPE_SHIPPING][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount']) ? $requestRow['charges'][OrderProduct::CHARGE_TYPE_SHIPPING][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'] : 0;
        $rewardAmountUsed = isset($requestRow['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount']) ? $requestRow['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT][OrderProduct::DB_TBL_CHARGES_PREFIX . 'amount'] : 0;

        $productAvaliedFreeShip = false;
        if (0 < $requestRow["op_free_ship_upto"] && array_key_exists(OrderProduct::CHARGE_TYPE_SHIPPING, $requestRow['charges']) && $requestRow["op_actual_shipping_charges"] != $requestRow['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount']) {
            $productAvaliedFreeShip = true;
            $shipCharges = $requestRow['op_actual_shipping_charges'];
        }

        $perUnitShippingCost = $shipCharges / $requestRow["op_qty"];

        $couponDiscount = isset($requestRow['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount']) ? abs($requestRow['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount']) : 0;

        $taxCharges = isset($requestRow['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount']) ? $requestRow['charges'][OrderProduct::CHARGE_TYPE_TAX]['opcharge_amount'] : 0;
        $taxPerQty = ($taxCharges / $requestRow['op_qty']);

        $cartAmount = $requestRow["op_unit_price"] * $requestRow["orrequest_qty"];

        $commissionCostValue = $requestRow["op_unit_price"];
        if ($requestRow['op_commission_include_tax'] && $taxPerQty /* && FatApp::getConfig('CONF_COMMISSION_INCLUDING_TAX', FatUtility::VAR_INT, 0) */) {
            $commissionCostValue = $commissionCostValue + $taxPerQty;
        }

        if ($requestRow['op_commission_include_shipping'] && $perUnitShippingCost && FatApp::getConfig('CONF_RETURN_SHIPPING_CHARGES_TO_CUSTOMER', FatUtility::VAR_INT, 0) && CommonHelper::canAvailShippingChargesBySeller($requestRow['op_selprod_user_id'], $requestRow['opshipping_by_seller_user_id'])) {
            $commissionCostValue = $commissionCostValue + $perUnitShippingCost;
        }

        $op_refund_commission = round((($commissionCostValue * $requestRow['op_qty']) * $requestRow['op_commission_percentage']) / 100, 2);

        $op_refund_commission = min($op_refund_commission, FatApp::getConfig("CONF_MAX_COMMISSION"));

        $perProdRefundCommission = round($op_refund_commission / $requestRow['op_qty'], 2);
        $op_refund_commission = $perProdRefundCommission * $requestRow["orrequest_qty"];
        //$op_refund_commission = min( $op_refund_commission, (FatApp::getConfig("CONF_MAX_COMMISSION")*$requestRow["orrequest_qty"] ));

        /* $op_refund_commission = round( ($cartAmount * $requestRow['op_commission_percentage'])/100, 2 );
        $op_refund_commission = min( $op_refund_commission, (FatApp::getConfig("CONF_MAX_COMMISSION")*$requestRow["orrequest_qty"] )); */

        $op_refund_affiliate_commission = round(($cartAmount * $requestRow['op_affiliate_commission_percentage']) / 100, 2);


        $taxToRefund = 0;
        if ($taxCharges > 0) {
            $taxToRefund = ($taxPerQty * ($requestRow['orrequest_qty']));
            $taxToRefund = min($taxToRefund, $taxCharges);
        }

        $volumeDiscountPerQty = 0;
        $deductVolumeDiscountFromRefund = 0;
        if ($volumeDiscount > 0) {
            $volumeDiscountPerQty = ($volumeDiscount / $requestRow['op_qty']);
            $deductVolumeDiscountFromRefund = ($volumeDiscountPerQty * $requestRow['orrequest_qty']);
        }

        $couponDiscountPerQty = 0;
        $deductCouponDiscountFromRefund = 0;
        if ($couponDiscount > 0) {
            $couponDiscountPerQty = ($couponDiscount / $requestRow['op_qty']);
            $deductCouponDiscountFromRefund = ($couponDiscountPerQty * $requestRow['orrequest_qty']);
        }

        $rewardAmountPerQty = 0;
        if (abs($rewardAmountUsed) > 0) {
            $rewardAmountPerQty = abs($rewardAmountUsed) / $requestRow['op_qty'];
        }

        $totalPaidAmtBuyer = ($requestRow["op_unit_price"] * $requestRow['op_qty']) + $requestRow["op_other_charges"];
        if (!$productAvaliedFreeShip) {
            $totalPaidAmtBuyer = $totalPaidAmtBuyer - $shipCharges;
        }

        if ($requestRow['op_qty'] == $requestRow['orrequest_qty']) {
            $op_refund_amount = $totalPaidAmtBuyer;
            $op_refund_amount += $requestRow["op_rounding_off"];
            // $deductedRewardAmount = $rewardAmountUsed;          
        } else {
            //$deductedRewardAmount = $rewardAmountPerQty * $requestRow['orrequest_qty'];
            $op_refund_amount = $cartAmount - ($rewardAmountPerQty * $requestRow['orrequest_qty']) + $taxToRefund - $deductVolumeDiscountFromRefund - $deductCouponDiscountFromRefund;
            if (0 > $requestRow["op_rounding_off"]) {
                $op_refund_amount += $requestRow["op_rounding_off"];
            }
        }

        $op_refund_shipping = 0;
        /*
        if(0 < $requestRow["op_free_ship_upto"] && array_key_exists(OrderProduct::CHARGE_TYPE_SHIPPING,$requestRow['charges']) && $requestRow["op_actual_shipping_charges"] != $requestRow['charges'][OrderProduct::CHARGE_TYPE_SHIPPING]['opcharge_amount']){
            $unitShipCharges = round(($requestRow['op_actual_shipping_charges'] / $requestRow['op_qty']),2);
            $op_refund_amount = $op_refund_amount - $requestRow['op_actual_shipping_charges'];
            $shipCharges = $requestRow['op_actual_shipping_charges'];
        }else{
            $unitShipCharges = round(($shipCharges / $requestRow['op_qty']),2);
        } */

        if (FatApp::getConfig('CONF_RETURN_SHIPPING_CHARGES_TO_CUSTOMER', FatUtility::VAR_INT, 0)) {
            $unitShipCharges = round(($shipCharges / $requestRow['op_qty']), 2);
            if (!$productAvaliedFreeShip) {
                $op_refund_shipping = round(($unitShipCharges * $requestRow["orrequest_qty"]), 2);
                $op_refund_shipping = min($op_refund_shipping, $shipCharges);
            }
            $op_refund_amount = $op_refund_amount + $op_refund_shipping;
        }

        $opDataToUpdate = array(
            'op_refund_qty' => $requestRow['orrequest_qty'],
            'op_cart_amount' => $cartAmount,
            'op_prod_price' => $cartAmount - $deductVolumeDiscountFromRefund - $deductCouponDiscountFromRefund,
            'op_refund_amount' => round($op_refund_amount, 2),
            'op_refund_shipping' => $op_refund_shipping,
            'op_refund_commission' => $op_refund_commission,
            'op_refund_affiliate_commission' => $op_refund_affiliate_commission,
            'op_refund_tax' => $taxToRefund,
            /*
            'deductCouponDiscountAmount'=> $deductCouponDiscountFromRefund,
            'deductRewardAmount'=> $deductedRewardAmount,
            */
        );

        return $opDataToUpdate;
    }

    public static function orderSubscriptionAmount($opArr = array(), $amountType = 'netamount', $pricePerItem = false)
    {
        $amount = 0;

        if (empty($opArr)) {
            trigger_error('Order Product Array should not be empty', E_USER_ERROR);
        }

        switch (strtoupper($amountType)) {
            case 'NETAMOUNT':
                $cartTotal = $opArr['ossubs_price'];
                $amount = $cartTotal + $opArr['op_other_charges'];
                break;
            case 'REWARDPOINT':
                $amount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_REWARD_POINT_DISCOUNT]['opcharge_amount'] : 0;
                break;
            case 'DISCOUNT':
                $amount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_DISCOUNT]['opcharge_amount'] : 0;
                break;
            case 'ADJUSTEDAMOUNT':
                $amount = isset($opArr['charges'][OrderProduct::CHARGE_TYPE_ADJUST_SUBSCRIPTION_PRICE]['opcharge_amount']) ? $opArr['charges'][OrderProduct::CHARGE_TYPE_ADJUST_SUBSCRIPTION_PRICE]['opcharge_amount'] : 0;
                break;
            case 'CART_TOTAL':
                $cartTotal = $opArr['ossubs_price'];
                $amount = $cartTotal;
                break;
        }


        return $amount;
    }

    public static function renderHtml($content = '', $stripJs = false)
    {
        if (empty($content)) {
            return '';
        }
        $str = html_entity_decode($content);
        $str = ($stripJs == true) ? static::stripJavascript($str) : $str;

        return $str;
    }

    public static function displayTaxFormat($isPercent, $val, $position = 'R')
    {
        if (!$isPercent) {
            return self::displayMoneyFormat($val);
        }

        if ($position == 'L') {
            return '% ' . $val;
        }

        return $val . ' %';
    }

    public static function getDefaultCurrencyValue($val, $format = true, $displaySymbol = true)
    {
        //$currency_id = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
        $currencyValue = self::getCurrencyValue();
        $defaultCurrencyValue = ((float) $val) / $currencyValue;
        return static::displayMoneyFormat($defaultCurrencyValue, $format, true, $displaySymbol);
    }

    public static function displayComissionPercentage($value = 0)
    {
        if (round($value, 0) == $value) {
            return  round($value, 0);
        } else {
            return $value;
        }
    }

    public static function getCurrencySymbol($showDefaultSiteCurrenySymbol = false)
    {
        if ($showDefaultSiteCurrenySymbol) {
            $currency_id = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
            $currencyData = Currency::getAttributesById(
                $currency_id,
                array('currency_symbol_left', 'currency_symbol_right')
            );
            $symbolLeft = $currencyData['currency_symbol_left'];
            $symbolRight = $currencyData['currency_symbol_right'];
        } else {
            $symbolLeft = self::getCurrencySymbolLeft();
            $symbolRight = self::getCurrencySymbolRight();
        }
        return   $symbolLeft . $symbolRight;
    }

    public static function numberStringFormat($number)
    {
        $prefixes = 'KMGTPEZY';
        if ($number >= 1000) {
            for ($i = -1; $number >= 1000; ++$i) {
                $number = $number / 1000;
            }
            return floor($number) . $prefixes[$i];
        }
        return $number;
    }

    public static function convertExistingToOtherCurrency($currCurrencyId, $val, $otherCurrencyId, $numberFormat = true)
    {
        $currencyData = Currency::getAttributesById(
            $currCurrencyId,
            array('currency_value')
        );
        $currencyValue = $currencyData['currency_value'];
        $val = $val / $currencyValue;

        $currencyData = Currency::getAttributesById(
            $otherCurrencyId,
            array('currency_value')
        );
        $currencyValue = $currencyData['currency_value'];
        $val = $val * $currencyValue;

        if ($numberFormat) {
            $val = number_format($val, 2);
        }

        return $val;
    }

    public static function displayMoneyFormat($val, $numberFormat = true, $showInConfiguredDefaultCurrency = false, $displaySymbol = true, $stringFormat = false, $withHtml = false, $includeZeroDecimal = false)
    {
        $val = FatUtility::convertToType($val, FatUtility::VAR_FLOAT, 0);
        $currencyValue = self::getCurrencyValue();
        $symbolLeft = self::getCurrencySymbolLeft();
        $symbolRight = self::getCurrencySymbolRight();

        if ($showInConfiguredDefaultCurrency) {
            $currency_id = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
            $currencyData = Currency::getAttributesById(
                $currency_id,
                array('currency_code', 'currency_symbol_left', 'currency_symbol_right', 'currency_value')
            );
            $currencyValue = $currencyData['currency_value'];
            $symbolLeft = $currencyData['currency_symbol_left'];
            $symbolRight = $currencyData['currency_symbol_right'];
        }

        $val = $val * $currencyValue;

        $sign = '';
        if ($val < 0) {
            $val = abs($val);
            $sign = '-';
        }

        $decimals = 2;
        if (true == $includeZeroDecimal) {
            if (is_numeric($val) && floor($val) == $val) {
                $decimals = 0;
            }
        }

        if ($numberFormat) {
            $val = self::numberFormat($val, $numberFormat, $stringFormat, $decimals);
        } else {
            $val = round($val, 2);
        }

        if ($stringFormat) {
            $val = static::numberStringFormat($val);
        }


        if ($displaySymbol) {
            $sign .= ' ';

            if (true === MOBILE_APP_API_CALL || false === $withHtml) {
                return trim($sign . $symbolLeft . $val . $symbolRight);
            }

            $symbolLeft = !empty($symbolLeft) ? "<span class='currency-symbol'>" . $symbolLeft . "</span>" : $symbolLeft;
            $symbolRight = !empty($symbolRight) ? "<span class='currency-symbol'>" . $symbolRight . "</span>" : $symbolRight;
            return "<span class='currency-value' dir='ltr'>" . trim($sign . $symbolLeft . $val . $symbolRight) . "</span>";
        }

        return trim($sign . $val);
    }

    public static function numberFormat($val, $numberFormat = true, $stringFormat = false, $decimals = 2)
    {
        $decimalpoint =  FatApp::getConfig('CONF_DEFAULT_CURRENCY_SEPARATOR', FatUtility::VAR_STRING, '.');
        $separator =  $decimalpoint == '.' ? ',' : '.';

        if ($numberFormat && !$stringFormat) {
            $val = number_format($val, $decimals, $decimalpoint, $separator);
        } else {
            $afterDecimal = $val - floor($val);
            $val = (0 < $afterDecimal ? number_format($val, $decimals, $decimalpoint, $separator) : floor($val));
        }

        return $val;
    }

    public static function convertCurrencyToRewardPoint($currencyValue)
    {
        $currencyValue = FatUtility::convertToType($currencyValue, FatUtility::VAR_FLOAT);
        if ($currencyValue == 0) {
            return 0;
        }
        return round(($currencyValue * FatApp::getConfig('CONF_REWARD_POINT')), 2);
    }

    public static function convertRewardPointToCurrency($rewardPoints)
    {
        $rewardPoints = FatUtility::int($rewardPoints);
        if ($rewardPoints == 0) {
            return 0;
        }
        return round(($rewardPoints / FatApp::getConfig('CONF_REWARD_POINT')), 2);
    }

    public static function displayNotApplicable($langId, $val, $str = "-NA-")
    {
        $str = ($str == "") ? Labels::getLabel("LBL_-NA-", $langId) : $str;
        return $val != "" ? $val : $str;
    }

    public static function editorSvg($path)
    {
        $headers = FatApp::getApacheRequestHeaders();
        if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($path))) {
            header('Content-type: image/svg+xml');
            header('Cache-Control: public, must-revalidate');
            header("Pragma: public");
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT', true, 304);
            header("Expires: " . date('D, d M Y H:i:s', strtotime("+30 days")));
            exit;
        }
        header('Content-type: image/svg+xml');
        header("Pragma: public");
        header('Cache-Control: public, must-revalidate');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT', true, 200);
        header("Expires: " . date('D, d M Y H:i:s', strtotime("+30 days")));
        readfile($path);
    }

    /* public static function captchaImg() {
        require_once  CONF_INSTALLATION_PATH . 'library/securimage/securimage.php';

        $options = array(
                'text_color' => '#000',
                'noise_color' => '#000',
                'code_length' => 5,
                'num_lines' => 0,
                'image_width' => 300,
                'font_ratio' => 1
            );
        $img = new Securimage($options);
        $img->case_sensitive = true;
        $img->show();
    } */



    public static function convertToCsv($input_array, $output_file_name, $delimiter = ',')
    {
        /** open raw memory as file, no need for temp files */
        $temp_memory = fopen('php://memory', 'w');
        fputs($temp_memory, chr(0xEF) . chr(0xBB) . chr(0xBF));
        /** loop through array  */
        foreach ($input_array as $key => $line) {
            /** default php csv handler **/
            //fprintf( $temp_memory, chr(0xEF).chr(0xBB).chr(0xBF) );
            //$line = array_map("utf8_decode", $line);
            //$line = array_map( FatUtility::decodeHtmlEntities, $line );
            //fprintf( $temp_memory, chr(0xEF).chr(0xBB).chr(0xBF) );
            //fputs( $temp_memory, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF) );
            fputcsv($temp_memory, $line, $delimiter);
        }
        /** rewrind the "file" with the csv lines **/
        fseek($temp_memory, 0);
        /** modify header to be downloadable csv file **/
        header('Content-Description: File Transfer');
        /* header('Content-Transfer-Encoding: binary');
        header('Content-Type: application/octet-stream'); */
        header('Content-Encoding: UTF-8');
        header('Content-type: application/csv; charset=UTF-8; encoding=UTF-8');
        if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") > 0) {
            header('Content-Disposition: attachment; filename="' . rawurlencode($output_file_name) . '"');
        } else {
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($output_file_name));
        }
        /** Send file to browser for download */

        //echo "\xEF\xBB\xBF";
        header("Cache-Control: cache, must-revalidate");
        header("Pragma: public");

        fpassthru($temp_memory);
    }

    public static function addToCSV($handle, $fileContent = array(), $headerRow = false)
    {
        if (!$handle) {
            return false;
        }

        if (true === $headerRow) {
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        }

        if (is_array($fileContent) && 0 < count($fileContent)) {
            fputcsv($handle, $fileContent);
        }
    }

    /* File creation in temporary memory. */
    public static function writeExportDataToCSV($handle, $fileContent = array(), $download = false, $output_file_name = '', $headerRow = false)
    {
        self::addToCSV($handle, $fileContent, $headerRow);

        if ($download) {
            /** rewrind the "file" with the csv lines **/
            fseek($handle, 0);
            /** modify header to be downloadable csv file **/
            header('Content-Description: File Transfer');
            header('Content-Encoding: UTF-8');
            header('Content-type: application/csv; charset=UTF-8; encoding=UTF-8');
            if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") > 0) {
                header('Content-Disposition: attachment; filename="' . rawurlencode($output_file_name) . '"');
            } else {
                header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($output_file_name));
            }
            /** Send file to browser for download */

            header("Cache-Control: cache, must-revalidate");
            header("Pragma: public");

            fpassthru($handle);
        }
    }

    /* To retain file on server. */
    public static function writeToCSVFile($handle, $fileContent = array(), $fileClose = false, $headerRow = false)
    {
        self::addToCSV($handle, $fileContent, $headerRow);

        if ($fileClose) {
            fclose($handle);
        }
    }

    public static function checkCSVFile($fileName)
    {
        if (empty($fileName)) {
            return false;
        }

        $file = fopen(ImportexportCommon::IMPORT_ERROR_LOG_PATH . $fileName, "r");

        /**** Skip first heading row ****/
        fgetcsv($file);
        /**** Skip first heading row ****/

        $havingData = fgetcsv($file);

        if (!$havingData) {
            unlink(ImportexportCommon::IMPORT_ERROR_LOG_PATH . $fileName);
        }
        return $havingData;
    }

    public static function getPercentValue($percentage, $total)
    {
        if (!$total) {
            return 0;
        }
        $percent = $percentage / $total;
        return number_format($percent * 100, 2) . '%';
    }

    public static function addCaptchaField($frm)
    {
        $caller = (debug_backtrace())[1];
        $action = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Form', '', $caller['function'])));

        $siteKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
        $secretKey = FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '');
        if (false === MOBILE_APP_API_CALL && !empty($frm) && !empty($siteKey) && !empty($secretKey)) {
            $frm->addHiddenField('', 'g-recaptcha-response', '', ['data-action' => $action]);
        }
    }

    public static function verifyCaptcha()
    {
        $siteKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
        $secretKey = FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '');
        if (true === MOBILE_APP_API_CALL || empty($siteKey) || empty($secretKey)) {
            return true;
        }

        $captcha = FatApp::getPostedData('g-recaptcha-response', FatUtility::VAR_STRING, '');
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = ['secret' => $secretKey, 'response' => $captcha];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        $responseKeys = json_decode($response, true);
        header('Content-type: application/json');
        return ($responseKeys["success"]) ? true : false;
    }

    public static function stripJavascript($content = '')
    {
        $javascript = '/<script[^>]*?>.*?<\/script>/si';
        $noscript = '';
        return preg_replace($javascript, $noscript, $content);
    }

    public static function addhttp($url)
    {
        return $url;
    }

    public static function escapeStringAndAddQuote($string)
    {
        $db = FatApp::getDb();
        if (method_exists($db, 'quoteVariable')) {
            return $db->quoteVariable($string);
        } else {
            return "'" . mysql_real_escape_string($string) . "'";
        }
    }

    public static function setAppUser()
    {
        $_SESSION['app_user'] = true;
    }

    /**
     * isAppUser - Used in case of web view.
     *
     * @return bool
     */
    public static function isAppUser(): bool
    {
        if (isset($_SESSION['app_user'])) {
            return true;
        }
        return false;
    }

    public static function escapeString($string)
    {
        return trim(self::escapeStringAndAddQuote($string), "'");
    }

    public static function getnavigationUrl($type, $nav_url = '', $nav_cpage_id = 0, $nav_category_id = 0, $getOriginalUrl = false)
    {
        if ($type == NavigationLinks::NAVLINK_TYPE_CMS) {
            $url = UrlHelper::generateUrl('cms', 'view', array($nav_cpage_id), '', null, false, $getOriginalUrl);
        } elseif ($type == NavigationLinks::NAVLINK_TYPE_EXTERNAL_PAGE) {
            $url = str_replace('{SITEROOT}', UrlHelper::generateUrl(), $nav_url);
            $url = str_replace('{siteroot}', UrlHelper::generateUrl(), $url);
            $url = CommonHelper::processURLString($url);
        } elseif ($type == NavigationLinks::NAVLINK_TYPE_CATEGORY_PAGE) {
            $url = UrlHelper::generateUrl('category', 'view', array($nav_category_id), '', null, false, $getOriginalUrl);
        }

        return $url;
    }

    public static function redirectUserReferer($returnUrl = false)
    {
        if (!defined('REFERER')) {
            if (!isset($_SERVER['HTTP_REFERER']) || UrlHelper::getCurrUrl() == $_SERVER['HTTP_REFERER'] || empty($_SERVER['HTTP_REFERER'])) {
                define('REFERER', UrlHelper::generateUrl('/'));
            } else {
                define('REFERER', $_SERVER['HTTP_REFERER']);
            }
        }

        if ($returnUrl) {
            return REFERER;
        }
        FatApp::redirectUser(REFERER);
    }

    public static function renderJsonError($tpl, $msg)
    {
        $tpl->set('msg', $msg);
        $tpl->render(false, false, 'json-error.php', false, false);
    }

    public static function renderJsonSuccess($tpl, $msg)
    {
        $tpl->set('msg', $msg);
        $tpl->render(false, false, 'json-success.php', false, false);
    }

    public static function checkMsgs()
    {
        $msgs_result['has_msgs'] = false;
        $msgs_result['msgs_html'] = '';
        if (Message::getErrorCount() > 0 || Message::getMessageCount() > 0) {
            $msgs_result['has_msgs'] = true;
            $msgs_result['msgs_html'] = Message::getHtml();
        }
        return $msgs_result;
    }

    public static function getRandomPassword($n)
    {
        $pass = '';
        if ($n > 4) {
            $n = $n - 4;
            $pass = 'Yk@1';
        }
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        for ($i = 0; $i < $n; $i++) {
            $pass .= substr($chars, rand(0, strlen($chars) - 1), 1);
        }
        return $pass;
    }

    public static function getAdminUrl($controller = '', $action = '', $queryData = array(), $use_root_url = '/admin/', $url_rewriting = null)
    {
        return FatUtility::generateFullUrl($controller, $action, $queryData, $use_root_url, $url_rewriting);
    }

    /* static function getInnovaEditorObj($textareaId, $divId, $jsTag = true){
        $innovaObj = 'window["fatbit_'.$textareaId.'"] = new InnovaEditor("fatbit_'.$textareaId.'");
                    window["fatbit_'.$textareaId.'"].width = "100%";
                    window["fatbit_'.$textareaId.'"].groups = [
                    ["group1", "", ["Bold", "Italic", "Underline", "FontDialog", "ForeColor", "TextDialog", "RemoveFormat"]],
                    ["group2", "", ["Bullets", "Numbering", "JustifyLeft", "JustifyCenter", "JustifyRight"]],
                    ["group3", "", ["LinkDialog"]],
                    ["group4", "", ["Undo", "Redo", "FullScreen", "SourceDialog","ImageDialog"]]
                    ];
                    window["fatbit_'.$textareaId.'"].REPLACE("'.$textareaId.'", "'.$divId.'");
                    window["fatbit_'.$textareaId.'"] .fileBrowser = "/admin/innova/assetmanager/asset_php";
                    ';

        if($jsTag){
            $innovaObj ='<script>'.$innovaObj.'</script>';
        }
        return $innovaObj;
    } */

    public static function currentDateTime($dateFormat = null, $dateTime = false, $timeFormat = null, $timeZone = null)
    {
        if ($timeZone == null) {
            $timeZone = FatApp::getConfig('CONF_TIMEZONE', FatUtility::VAR_STRING, date_default_timezone_get());
        }

        if ($dateFormat == null) {
            $dateFormat = FatApp::getConfig('CONF_DATE_FORMAT', FatUtility::VAR_STRING, 'Y-m-d');
        }

        if ($dateTime) {
            if ($timeFormat == null) {
                $timeFormat = FatApp::getConfig('CONF_DATEPICKER_FORMAT_TIME', FatUtility::VAR_STRING, 'H:i');
            }
        }

        $format = $dateFormat . ' ' . $timeFormat;
        return FatDate::nowInTimezone($timeZone, trim($format));
    }

    public static function getLangFields($condition_id = 0, $condition_field = "", $condition_lang_field = "", $lang_flds = array(), $lang_table = "")
    {
        $condition_id = FatUtility::int($condition_id);
        if ($condition_id == 0 || $condition_field == "" || $condition_lang_field == "" || $lang_table == "" || empty($lang_flds)) {
            return array();
        }
        $langs = Language::getAllNames();;
        $array = array();
        $srch = new SearchBase($lang_table);
        $srch->addCondition($condition_field, '=', $condition_id);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();

        while ($rec = FatApp::getDb()->fetch($rs)) {
            foreach ($langs as $langId => $lang) {
                if ($rec[$condition_lang_field] == $langId) {
                    foreach ($lang_flds as $fld) {
                        $array[$fld][$langId] = $rec[$fld];
                        $array[$fld . $langId] = $rec[$fld];
                    }
                }
            }
        }

        /* $record = FatApp::getDb()->fetchAll($rs);
        foreach ($langs as $langId => $lang) {
            foreach ($record as $rec) {
                if ($rec[$condition_lang_field] == $langId) {
                    foreach ($lang_flds as $fld) {
                        $array[$fld][$langId] = $rec[$fld];
                        $array[$fld . $langId] = $rec[$fld];
                    }
                    continue;
                }
            }
        } */
        return $array;
    }


    /* static function generateOptionsArr($option_ids, $option_value_ids, $option_names, $option_value_names){
        $option_ids_arr = explode(",",$option_ids);
        $option_value_ids_arr = explode(",",$option_value_ids);
        $option_names_arr = explode(",",$option_names);
        $option_value_names_arr = explode(",",$option_value_names);

        $options_arr = array();
        if( count($option_ids_arr) > 0 && $option_ids_arr[0] != '' ){
            foreach($option_ids_arr as $key => $option_id)
            $options_arr[$option_id] = array(
                'option_id'  => $option_id,
                'optionvalue_id' => $option_value_ids_arr[$key],
                'option_name'=> $option_names_arr[$key],
                'optionvalue_name' => $option_value_names_arr[$key]
            );
        }
        return $options_arr;
    } */

    public static function arrayToAssocArray($arr)
    {
        $arr_url_params = array();
        if (!empty($arr)) {
            foreach ($arr as $key => $val) {
                $v = 0;
                if ($key % 2 == 0) {
                    $k = $val;
                } else {
                    $v = $val;
                }
                $arr_url_params[$k] = $v;
            }
        }

        return $arr_url_params;
    }

    public static function crop($data, $src, $langId, $dst = '')
    {
        if (empty($data)) {
            return;
        }

        $size = getimagesize($src);
        $size_w = $size[0]; // natural width
        $size_h = $size[1]; // natural height

        $src_img_w = $size_w;
        $src_img_h = $size_h;

        $degrees = isset($data->rotate) ? $data->rotate : 0;

        switch ($size['mime']) {
            case "image/gif":
                $src_img = imagecreatefromgif($src);
                break;

            case "image/jpeg":
                $src_img = imagecreatefromjpeg($src);
                break;

            case "image/png":
                $src_img = imagecreatefrompng($src);
                break;
        }

        //  $src_img = imagecreatefromjpeg($src);
        // Rotate the source image
        if (is_numeric($degrees) && $degrees != 0) {
            // PHP's degrees is opposite to CSS's degrees
            $new_img = imagerotate($src_img, -$degrees, imagecolorallocatealpha($src_img, 0, 0, 0, 127));

            imagedestroy($src_img);
            $src_img = $new_img;

            $deg = abs($degrees) % 180;
            $arc = ($deg > 90 ? (180 - $deg) : $deg) * M_PI / 180;

            $src_img_w = $size_w * cos($arc) + $size_h * sin($arc);
            $src_img_h = $size_w * sin($arc) + $size_h * cos($arc);

            // Fix rotated image miss 1px issue when degrees < 0
            $src_img_w -= 1;
            $src_img_h -= 1;
        }

        $tmp_img_w = $data->width;
        $tmp_img_h = $data->height;
        $dst_img_w = 320;
        $dst_img_h = 320;

        $src_x = $data->x;
        $src_y = $data->y;

        if ($src_x <= -$tmp_img_w || $src_x > $src_img_w) {
            $src_x = $src_w = $dst_x = $dst_w = 0;
        } elseif ($src_x <= 0) {
            $dst_x = -$src_x;
            $src_x = 0;
            $src_w = $dst_w = min($src_img_w, $tmp_img_w + $src_x);
        } elseif ($src_x <= $src_img_w) {
            $dst_x = 0;
            $src_w = $dst_w = min($tmp_img_w, $src_img_w - $src_x);
        }

        if ($src_w <= 0 || $src_y <= -$tmp_img_h || $src_y > $src_img_h) {
            $src_y = $src_h = $dst_y = $dst_h = 0;
        } elseif ($src_y <= 0) {
            $dst_y = -$src_y;
            $src_y = 0;
            $src_h = $dst_h = min($src_img_h, $tmp_img_h + $src_y);
        } elseif ($src_y <= $src_img_h) {
            $dst_y = 0;
            $src_h = $dst_h = min($tmp_img_h, $src_img_h - $src_y);
        }

        // Scale to destination position and size
        $ratio = $tmp_img_w / $dst_img_w;
        $dst_x /= $ratio;
        $dst_y /= $ratio;
        $dst_w /= $ratio;
        $dst_h /= $ratio;

        $dst_img = imagecreatetruecolor($dst_img_w, $dst_img_h);

        // Add transparent background to destination image
        imagefill($dst_img, 0, 0, imagecolorallocatealpha($dst_img, 0, 0, 0, 127));
        imagesavealpha($dst_img, true);

        $result = imagecopyresampled($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

        if ($result) {
            $dst = ($dst != '') ? $dst : $src;
            if (!imagepng($dst_img, $dst)) {
                return Labels::getLabel('MSG_Failed_to_save_cropped_file', $langId);
            }
        } else {
            return Labels::getLabel('MSG_Failed_to_crop_file', $langId);
        }

        imagedestroy($src_img);
        imagedestroy($dst_img);
    }

    public static function isRenderTemplateExist($template = '')
    {
        $instance = FatApplication::getInstance();
        if ($template == '') {
            $themeDirName = FatUtility::camel2dashed(substr($instance->getController(), 0, - (strlen('controller'))));
            $actionName = FatUtility::camel2dashed($instance->getAction()) . '.php';
            $template = $themeDirName . '/' . $actionName;
        }

        if (file_exists(CONF_THEME_PATH . $template)) {
            return true;
        }
        return false;
    }

    public static function subStringByWords($str, $maxlength, $trailingString = '')
    {
        $str = (string) $str;
        if (!empty($str) && strlen($str) < $maxlength) {
            return $str;
        }
        $str = substr($str, 0, $maxlength);
        $rpos = strrpos($str, ' ');
        if ($rpos > 0) {
            $str = substr($str, 0, $rpos);
        }
        return $str . $trailingString;
    }

    public static function getWeightInGrams($unit, $val)
    {
        $unit = FatUtility::int($unit);
        switch ($unit) {
            case applicationConstants::WEIGHT_GRAM:
                $weight = $val;
                break;

            case applicationConstants::WEIGHT_POUND:
                $weight = $val * 453.592;
                break;

                /* case 'OU':
            case 'OUNCE':
                $weight = $val * 28.3495;
            break; */

            case applicationConstants::WEIGHT_KILOGRAM:
                $weight = $val * 0.001;
                break;
            default:
                trigger_error("Invalid Argument", E_USER_ERROR);
        }
        return $weight;
    }

    public static function getLengthInCentimeter($val, $unit)
    {
        $unit = FatUtility::int($unit);
        switch ($unit) {
            case applicationConstants::LENGTH_CENTIMETER:
                $length = $val;
                break;
            case applicationConstants::LENGTH_METER:
                $length = $val * 100;
                break;
            case applicationConstants::LENGTH_INCH:
                $length = $val * 2.54;
                break;
            default:
                trigger_error("Invalid Argument", E_USER_ERROR);
        }
        return $length;
    }

    public static function getVolumeInCC($unit, $val)
    {
        $unit = FatUtility::int($unit);
        return $val;
    }

    public static function isMultidimArray($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        foreach ($arr as $elm) {
            if (!is_array($elm)) {
                return false;
            }
        }
        return true;
    }

    public static function formatOrderReturnRequestNumber($requestId)
    {
        $new_value = str_pad($requestId, 5, '0', STR_PAD_LEFT);
        $new_value = "R" . $new_value;
        return $new_value;
    }

    public static function processURLString($urlString)
    {
        $strtestpos = strpos(" " . $urlString, ".");
        if (!$strtestpos) {
            return $urlString;
        }
        $urlString = trim($urlString);
        if ($urlString) {
            $my_bool = false;
            if (substr($urlString, 0, 5) == 'https') {
                $my_bool = true;
            }
            $urlString = preg_replace('/https?:\/\//', '', $urlString);
            $urlString = trim($urlString);
            $pre_str = 'http://';
            if ($my_bool) {
                $pre_str = 'https://';
            }
            $urlString = $pre_str . $urlString;
        }
        return $urlString;
    }

    public static function currencyDisclaimer($langId, $amount)
    {
        $str = Labels::getLabel('LBL_NOTE_CHARGED_IN_CURRENCY_DISCLAIMER_{DEFAULT-CURRENCY-SYMBOL}', $langId);
        return CommonHelper::replaceStringData($str, ["{DEFAULT-CURRENCY-SYMBOL}" => static::displayMoneyFormat($amount, true, true)]);
    }

    public static function showProductDiscountedText(array $product, int $langId, $fld = 'theprice')
    {
        $langId = FatUtility::int($langId);
        if (empty($product) || $langId <= 0) {
            trigger_error("Invalid Argument Passed!", E_USER_ERROR);
        }

        $originalPrice = $product['selprod_price'];
        if (1 > $originalPrice) {
            return 0;
        }

        $specialPrice = $product[$fld];
        $discount = (($originalPrice - $specialPrice) * 100) / $originalPrice;

        return round($discount, 2) . "% " . Labels::getLabel('LBL_Off', $langId);
    }

    public static function truncateCharacters($string, $limit, $break = " ", $pad = "...", $nl2br = false)
    {
        if (null == $string) {
            return;
        }

        if (strlen($string) <= $limit) {
            return ($nl2br) ? nl2br($string) : $string;
        }


        $tempString = str_replace('\n', '^', $string);
        $tempString = mb_substr($tempString, 0, $limit);
        if (mb_substr($tempString, -1) == "^") {
            $limit = $limit - 1;
        }
        $string = mb_substr($string, 0, $limit);

        if (false !== ($breakpoint = mb_strrpos($string, $break))) {
            $string = mb_substr($string, 0, $breakpoint);
        }
        return (($nl2br) ? nl2br($string) : $string) . $pad;
    }

    public static function displayName($string)
    {
        if (!empty($string)) {
            return ucfirst($string);
        }
    }

    public static function getFirstChar($string, $capitalize = false)
    {
        if (!empty($string)) {
            if ($capitalize == true) {
                return strtoupper($string[0]);
            } else {
                return $string[0];
            }
        }
    }

    public static function seoUrl($string)
    {
        //Lower case everything
        $string = ltrim(strtolower($string), '/');
        //Make alphanumeric (removes all other characters)
        //$string = preg_replace("/[^a-z0-9,&_\s-\/]/", "", $string);
        //covert / to -
        $string = preg_replace("/[\s,&#%+]/", "-", $string);
        //Clean up multiple dashes or whitespaces
        $string = preg_replace("/[\s-]+/", " ", $string);
        //Convert whitespaces and underscore to dash
        $string = preg_replace("/[\s_]/", "-", $string);
        $string = str_replace('"', "", $string);
        $keyword = strtolower($string);
        $keyword = ucfirst(FatUtility::dashed2Camel($keyword));

        if (file_exists(CONF_INSTALLATION_PATH . 'application/controllers/' . $keyword . 'Controller' . '.php')) {
            return $string . '-' . rand(1, 100);
        }

        return trim($string, '-');
    }

    public static function recursiveDelete($str, $removeParent = false)
    {
        if (is_file($str)) {
            return @unlink($str);
        } elseif (is_dir($str)) {
            $scan = glob(rtrim($str, '/') . '/*');
            foreach ($scan as $index => $path) {
                static::recursiveDelete($path, true);
            }

            if ($removeParent) {
                return @rmdir($str);
            }
        }
    }

    public static function displayText($value = '')
    {
        if (empty($value)) {
            return '-';
        }
        return empty(trim($value)) ? '-' : $value;
    }

    public static function getPlaceholderForAmtField($langId)
    {
        return Labels::getLabel('Lbl_Amount_in', $langId) . ' ' . static::concatCurrencySymbolWithAmtLbl();
    }

    public static function concatCurrencySymbolWithAmtLbl()
    {
        $currencyId = FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1);
        $currencyData = Currency::getAttributesById(
            $currencyId,
            array('currency_code', 'currency_symbol_left', 'currency_symbol_right', 'currency_value')
        );
        $symbol = $currencyData['currency_symbol_right'] ?? $currencyData['currency_symbol_left'];

        return empty($symbol) ? '' : " ($symbol)";
    }

    public static function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function multipleExplode($delimiters = array(), $string = '')
    {
        $mainDelim = end($delimiters);
        array_pop($delimiters);
        foreach ($delimiters as $delimiter) {
            $string = str_replace($delimiter, $mainDelim, $string);
        }
        $result = explode($mainDelim, $string);
        return self::arrayTrim($result);
    }

    public static function arrayTrim($ar)
    {
        foreach ($ar as $key => $val) {
            $val = trim($val);
            if (!empty($val)) {
                $reArray[] = $val;
            }
        }
        return $reArray;
    }

    public static function referralTrackingUrl($code)
    {
        return UrlHelper::generateFullUrl('Home', 'Referral', array($code), CONF_WEBROOT_FRONTEND);
    }

    public static function affiliateReferralTrackingUrl($code)
    {
        return UrlHelper::generateFullUrl('Home', 'AffiliateReferral', array($code), CONF_WEBROOT_FRONTEND);
    }

    public static function createSlug($string)
    {
        return preg_replace('/[^A-Za-z0-9-\/]+/', '-', ltrim($string, '/'));
    }

    public static function getProdRatingInPercentage($rating, $total, $circleView)
    {
        $percentage = ($rating / $total) * 100;
        if ($circleView == true) {
            $degree = ($percentage * 360) / 100;
            return $degree;
        }
        return $percentage;
    }

    public static function getValidTillDate($packageInfo, $sub_last_date = '')
    {
        $days = $packageInfo['ossubs_interval'];
        $durationArr = SellerPackagePlans::getSubscriptionPeriodValues();
        $duration = $durationArr[$packageInfo['ossubs_frequency']];
        if ($sub_last_date == '') {
            $sub_last_date = date('Y-m-d');
        }

        if (0 == $days && SellerPackagePlans::SUBSCRIPTION_PERIOD_UNLIMITED == $packageInfo['ossubs_frequency']) {
            $days = 100;
        }

        return  date('Y-m-d', strtotime("+" . $days . " " . $duration, strtotime($sub_last_date)));
    }

    public static function isCsvValidMimes()
    {
        $csvValidMimes = array(
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'application/octet-stream',
            'application/vnd.ms-excel',
            'application/x-csv',
            'text/x-csv',
            'text/csv',
            'application/csv',
            'application/excel',
            'application/vnd.msexcel',
            'text/plain'
        );
        return $csvValidMimes;
    }


    public static function createDropDownFromArray($name = '', $arr = array(), $selected = 0, $extra = ' ', $selectCaption = '')
    {
        $dropDown = '<select name="' . $name . '" ' . $extra . '>';
        if ($selectCaption) {
            $dropDown .= '<option  value="0">' . $selectCaption . '</option>';
        }

        foreach ($arr as $key => $val) {
            $selectedStr = ($key == $selected) ? "selected=selected" : "";
            $dropDown .= '<option ' . $selectedStr . ' value="' . $key . '">' . $val . '</option>';
        }
        $dropDown .= '</select>';
        return  $dropDown;
    }

    public static function getUserFirstName($userName = '')
    {
        $nameArr = explode(" ", $userName);
        $firstName = $nameArr[0];
        if (strlen($firstName) > 15) {
            return substr($firstName, 0, 11) . "...";
        } else {
            return $firstName;
        }
    }

    public static function setCookie($cookieName, $cookieValue, $cookieExpiryTime, $cookiePath = '', $cokieSubDomainName = '', $isCookieSecure = false, $isCookieHttpOnly = true)
    {
        $cookiePath = ($cookiePath == "") ? CONF_WEBROOT_URL : $cookiePath;

        /* manipulating $cookieValue to make it array containg real data and storing creation datetime [ */
        /* */
        /* ] */

        setcookie($cookieName, $cookieValue, $cookieExpiryTime, $cookiePath, $cokieSubDomainName, $isCookieSecure, $isCookieHttpOnly);
    }

    public static function writeFile($name, $data, &$response)
    {
        $fName = CONF_UPLOADS_PATH . preg_replace('/[^a-zA-Z0-9\/\-\_\.]/', '', $name);
        $dest = dirname($fName);

        if (!file_exists($dest)) {
            mkdir($dest, 0777, true);
        }

        $file = fopen($fName, 'w');
        if (!fwrite($file, $data)) {
            $response = Labels::getLabel('MSG_Could_not_save_file.', CommonHelper::getLangId());
            return false;
        }
        fclose($file);
        $response = $fName;
        return true;
    }

    public static function getPaymentCancelPageUrl()
    {
        return  UrlHelper::generateFullUrl("Custom", "paymentCancel");
    }
    public static function getPaymentFailurePageUrl()
    {
        return  UrlHelper::generateFullUrl("Custom", "paymentFailed");
    }

    public static function minifyHtml($input)
    {
        if (trim($input) === "") {
            return $input;
        }
        // Remove extra white-space(s) between HTML attribute(s)
        $input = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function ($matches) {
            return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
        }, str_replace("\r", "", $input));
        // Minify inline CSS declaration(s)
        if (strpos($input, ' style=') !== false) {
            $input = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function ($matches) {
                return '<' . $matches[1] . ' style=' . $matches[2] . CommonHelper::minifyCss($matches[3]) . $matches[2];
            }, $input);
        }
        if (strpos($input, '</style>') !== false) {
            $input = preg_replace_callback('#<style(.*?)>(.*?)</style>#is', function ($matches) {
                return '<style' . $matches[1] . '>' . CommonHelper::minifyCss($matches[2]) . '</style>';
            }, $input);
        }
        if (strpos($input, '</script>') !== false) {
            $input = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function ($matches) {
                return '<script' . $matches[1] . '>' . CommonHelper::minifyJs($matches[2]) . '</script>';
            }, $input);
        }
        return preg_replace(
            array(
                // t = text
                // o = tag open
                // c = tag close
                // Keep important white-space(s) after self-closing HTML tag(s)
                '#<(img|input)(>| .*?>)#s',
                // Remove a line break and two or more white-space(s) between tag(s)
                '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
                '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s', // t+c || o+t
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s', // o+o || c+c
                '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s', // c+t || t+o || o+t -- separated by long white-space(s)
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s', // empty tag
                '#<(img|input)(>| .*?>)<\/\1>#s', // reset previous fix
                '#(&nbsp;)&nbsp;(?![<\s])#', // clean up ...
                '#(?<=\>)(&nbsp;)(?=\<)#', // --ibid
                // Remove HTML comment(s) except IE comment(s)
                '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s'
            ),
            array(
                '<$1$2</$1>',
                '$1$2$3',
                '$1$2$3',
                '$1$2$3$4$5',
                '$1$2$3$4$5$6$7',
                '$1$2$3',
                '<$1$2',
                '$1 ',
                '$1',
                ""
            ),
            $input
        );
    }

    public static function minifyCss($input)
    {
        if (trim($input) === "") {
            return $input;
        }
        return preg_replace(
            array(
                // Remove comment(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                // Remove unused white-space(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                // Replace `:0 0 0 0` with `:0`
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                // Replace `background-position:0` with `background-position:0 0`
                '#(background-position):0(?=[;\}])#si',
                // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                '#(?<=[\s:,\-])0+\.(\d+)#s',
                // Minify string value
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                // Minify HEX color code
                '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                // Replace `(border|outline):none` with `(border|outline):0`
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                // Remove empty selector(s)
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
            ),
            array(
                '$1',
                '$1$2$3$4$5$6$7',
                '$1',
                ':0',
                '$1:0 0',
                '.$1',
                '$1$3',
                '$1$2$4$5',
                '$1$2$3',
                '$1:0',
                '$1$2'
            ),
            $input
        );
    }
    // JavaScript Minifier
    public static function minifyJs($input)
    {
        if (trim($input) === "") {
            return $input;
        }
        return preg_replace(
            array(
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3'
            ),
            $input
        );
    }

    public static function getUserCookiesEnabled()
    {
        $userId = UserAuthentication::getLoggedUserId(true);
        if ($userId > 0) {
            $user = new User($userId);
            $userSelectedCookies = $user->getUserSelectedCookies();
            return !empty($userSelectedCookies) ? true : false;
        } else {
            return static::checkCookiesEnabledSession();
        }
    }

    public static function checkCookiesEnabledSession()
    {
        return ((isset($_COOKIE['ykStatisticalCookies']) && 1 == $_COOKIE['ykStatisticalCookies']) || (isset($_COOKIE['ykPersonaliseCookies']) && 1 == $_COOKIE['ykPersonaliseCookies']));
    }


    public static function getDefaultCurrencySymbol()
    {
        $row = Currency::getAttributesById(FatApp::getConfig('CONF_CURRENCY'), array('currency_symbol_left', 'currency_symbol_right'));
        if (!empty($row)) {
            return ($row['currency_symbol_left'] != '') ? $row['currency_symbol_left'] : $row['currency_symbol_right'];
        }
        trigger_error(Labels::getLabel('ERR_Default_currency_not_specified.', CommonHelper::getLangId()), E_USER_ERROR);
    }

    public static function logData($str)
    {
        if (is_array($str)) {
            $str = json_encode($str);
        }
        //Something to write to txt log
        $log = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i:s:u a") . PHP_EOL .
            "data: " . $str . PHP_EOL .
            "-------------------------" . PHP_EOL;
        $file = CONF_UPLOADS_PATH . './log_' . date("Y-m-d") . '.txt';
        //Save string to log, use FILE_APPEND to append.
        file_put_contents($file, $log, FILE_APPEND);
    }

    public static function fullCopy($source, $target, $empty_first = true)
    {
        if ($empty_first) {
            self::recursiveDelete($target);
        }
        if (is_dir($source)) {
            @mkdir($target);
            $d = dir($source);
            while (false !== ($entry = $d->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $Entry = $source . '/' . $entry;
                if (is_dir($Entry)) {
                    self::fullCopy($Entry, $target . '/' . $entry);
                    continue;
                }
                copy($Entry, $target . '/' . $entry);
            }

            $d->close();
        } else {
            copy($source, $target);
        }
    }

    public static function demoUrl($includeDevDemo = false)
    {
        if (true == $includeDevDemo) {
            if (strpos($_SERVER['SERVER_NAME'], 'demo.yo-kart.com') !== false || strpos($_SERVER['SERVER_NAME'], 'demo.yokart.4livedemo.com') !== false) {
                return true;
            }
            return false;
        }

        return (strpos($_SERVER['SERVER_NAME'], 'demo.yo-kart.com') !== false);
    }

    public static function jsonEncodeUnicode($data, $convertToType = false)
    {
        if (true === $convertToType) {
            $data = static::cleanArray($data);
        }

        header('Content-Type: application/json; charset=utf-8');
        die(LibHelper::convertToJson($data, JSON_UNESCAPED_UNICODE));
    }

    public static function cleanArray($obj)
    {        
        ini_set('memory_limit', '100M');
        $orig_obj = $obj;

        // We want to preserve the object name to the array
        // So we get the object name in case it is an object before we convert to an array (which we lose the object name)
        if (is_object($obj)) {
            $obj = (array) $obj;
            if (empty($obj)) {
                return $orig_obj;
            }
        }

        // If obj is now an array, we do a recursion
        // If obj is not, just return the value
        if (is_array($obj)) {
            $new = [];
            //initiate the recursion
            foreach ($obj as $key => $val) {
                if (is_object($orig_obj)) {
                    // Remove full class name from the key
                    $key = str_replace(get_class($orig_obj), '', $key);
                    // We don't want those * infront of our keys due to protected methods
                }

                $new[$key] = self::cleanArray($val);
            }
        } else {
            $new = FatUtility::convertToType($obj, FatUtility::VAR_STRING);
        }

        return $new;
    }

    public static function displayBadgeCount($totalCount, $maxValue = 99)
    {
        if ($totalCount > $maxValue) {
            return $maxValue . '+';
        }
        return $totalCount;
    }

    public static function displayTaxPercantage($taxVal, $displayPercentage = false)
    {
        if (false == $displayPercentage) {
            return $taxVal['name'];
        }

        if ($taxVal['inPercentage'] == Tax::TYPE_PERCENTAGE) {
            return $taxVal['name'] . ' (' . CommonHelper::numberFormat($taxVal['percentageValue'], true, false, 4) . '%)';
        }
        return $taxVal['name'] . ' (' . CommonHelper::numberFormat($taxVal['percentageValue']) . ')';
    }


    public static function replaceStringData($str, $replacements = array(), $stripTags = false)
    {
        foreach ($replacements as $key => $val) {
            if (is_null($val)) {
                continue;
            }

            if ($stripTags) {
                $val = strip_tags($val);
            }
            $str = str_replace($key, $val, $str);
            $str = str_replace(strtolower($key), $val, $str);
            $str = str_replace(strtoupper($key), $val, $str);
        }
        return $str;
    }

    public static function segregateUrl(string $url): array
    {
        if (empty($url)) {
            return [];
        }

        if (strpos($url, "?") !== false) {
            $url = str_replace('?', '/?', $url);
        }
        $url = preg_replace('/https:/', 'http:', $url, 1);
        /* [ Check url rewritten by the system and "/" discarded in url rewrite*/
        $systemUrl = UrlHelper::generateFullUrl('', '', array(), CONF_WEBROOT_FRONTEND);
        $systemUrl = preg_replace('/https:/', 'http:', $systemUrl, 1);
        $systemUrl = substr($url, strlen($systemUrl));
        $systemUrl = rtrim($systemUrl, '/');
        $customUrl = array_filter(explode('/', $systemUrl));
        $customUrl = array_values($customUrl);
        if (empty($customUrl)) {
            return [];
        }
        $srch = UrlRewrite::getSearchObject();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $cond = $srch->addCondition(UrlRewrite::DB_TBL_PREFIX . 'custom', '=', $customUrl[0]);
        $cond->attachCondition(UrlRewrite::DB_TBL_PREFIX . 'original', '=', $systemUrl);

        $rs = $srch->getResultSet();
        if (!$row = FatApp::getDb()->fetch($rs)) {
            $url = $systemUrl;
        } else {
            $url = $row['urlrewrite_original'];
        }

        return array_values(array_filter(explode('/', $url)));
    }

    public static function getUrlTypeData($url)
    {
        $arr = self::segregateUrl($url);
        if (empty($arr)) {
            return false;
        }

        $controller = (isset($arr[0])) ? $arr[0] : '';
        array_shift($arr);
        $action = (isset($arr[0])) ? $arr[0] : '';
        array_shift($arr);
        $queryString = $arr;
        if ($controller != '' && $action == '') {
            $action = 'index';
        }
        if ($controller == '') {
            $controller = 'Content';
        }
        $recordId = isset($queryString[0]) ? $queryString[0] : 0;
        $extra = (object) [];
        switch ($controller . '/' . $action) {
            case 'category/view':
                $urlType = applicationConstants::URL_TYPE_CATEGORY;
                break;
            case 'brands/view':
                $urlType = applicationConstants::URL_TYPE_BRAND;
                break;
            case 'shops/view':
                $urlType = applicationConstants::URL_TYPE_SHOP;
                break;
            case 'products/view':
                $urlType = applicationConstants::URL_TYPE_PRODUCT;
                break;
            case 'collections/view':
            case 'shops/collection':
                $collectionType = 0 < $recordId ? Collections::getAttributesById($recordId, 'collection_type') : 0;
                $urlType = applicationConstants::URL_TYPE_COLLECTION;
                if (0 < $recordId) {
                    $queryString = array_values(array_diff($queryString, [$recordId]));
                }
                $extra = [
                    'collectionType' => $collectionType,
                    'queryParams' => $queryString
                ];
                break;
            case 'guest-user/login-form':
                $urlType = !empty($recordId) ? applicationConstants::URL_TYPE_REGISTER : applicationConstants::URL_TYPE_SIGN_IN;
                break;
            case 'cms/view':
                $urlType = applicationConstants::URL_TYPE_CMS;
                break;
            case 'custom/contact-us':
                $urlType = applicationConstants::URL_TYPE_CONTACT_US;
                break;
            case 'blog/post-detail':
                $urlType = applicationConstants::URL_TYPE_BLOG;
                break;
            case 'home/referral':
                $urlType = applicationConstants::URL_TYPE_REGISTER;
                $extra = [
                    'referralToken' => $recordId
                ];
                break;
            default:
                $recordId = applicationConstants::NO;
                $urlType = applicationConstants::URL_TYPE_EXTERNAL;
                break;
        }
        return array(
            'url' => $url,
            'recordId' => $recordId,
            'urlType' => $urlType,
            'extra' => $extra
        );
    }

    public static function getImageAttributes($fileType, $recordId, $recordSubId = 0, $afileId = 0, $screen = 0, $langId = 0)
    {
        $fileType = FatUtility::int($fileType);
        $recordId = FatUtility::int($recordId);
        $afileId = FatUtility::int($afileId);
        $screen = FatUtility::int($screen);
        $recordSubId = FatUtility::int($recordSubId);
        $langId = FatUtility::int($langId);

        if ($langId == 0) {
            $langId = self::$_lang_id;
        }
        /* if($recordId == 0 && $afileId == 0) {
            return array();
        } */
        if ($afileId > 0) {
            $res = AttachedFile::getAttributesById($afileId);
            if (!false == $res && $res['afile_type'] == $fileType) {
                $file_row = $res;
            }
        } else {
            $file_row = AttachedFile::getAttachment($fileType, $recordId, $recordSubId, $langId, true, $screen);
        }
        return $file_row;
    }

    public static function getRoundingOff($childOrder)
    {
        if (array_key_exists('op_rounding_off', $childOrder) && $childOrder['op_rounding_off'] != 0) {
            return CommonHelper::displayMoneyFormat($childOrder['op_rounding_off']);
        }
        return false;
    }

    /**
     * stripAllTags - This differs from strip_tags() because it removes the contents of the <script> and <style> tags. 
     * E.g. strip_tags( '<script>something</script>' ) will return ‘something’. stripAllTags will return ”
     *
     * @param  string $string
     * @param  bool $remove_breaks
     * @return void
     */
    public static function stripAllTags(string $string, bool $remove_breaks = false): string
    {
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $string = strip_tags($string);

        if ($remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }

        return trim($string);
    }

    public static function displayEncryptedEmail($email)
    {
        if (empty($email)) {
            return;
        }
        $userEmail = preg_split('/[@.]/', $email);
        if (empty(array_filter($userEmail))) {
            return;
        }
        $emailFirstPart = substr($userEmail[0], 0, 1) . str_repeat('*', strlen($userEmail[0]) - 1);
        $emailSecondPart = str_repeat('*', strlen($userEmail[1]));
        $emailThirdPart = $userEmail[2];
        return $emailFirstPart . '@' . $emailSecondPart . '.' . $emailThirdPart;
    }

    public static function displayEncryptedDob($dob)
    {
        $userDob = explode('-', $dob);
        $dobFirstPart = substr($userDob[0], 0, 1) . str_repeat('*', strlen($userDob[0]) - 1);
        $dobSecondPart = str_repeat('*', strlen($userDob[1]));
        $dobThirdPart = str_repeat('*', strlen($userDob[2]) - 1) . substr($userDob[2], strlen($userDob[2]) - 1, 1);
        return $dobFirstPart . '-' . $dobSecondPart . '-' . $dobThirdPart;
    }

    public static function displayEncryptedFieldData($data)
    {
        $len = strlen($data);
        return substr($data, 0, 1) . str_repeat('*', $len - 2) . substr($data, $len - 1, 1);
    }

    public static function isFieldEncrypted($data)
    {
        return (strpos($data, '*') !== false);
    }

    public static function getDefaultFormLangId()
    {
        return CommonHelper::getLangId();
    }

    public static function isSetCookie($cookieName)
    {
        if (isset($_COOKIE[$cookieName])) {
            return true;
        }
        return false;
    }

    public static function getCookie($cookieName): string
    {
        if (isset($_COOKIE[$cookieName]) && !empty($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName];
        }
        return false;
    }

    public static function getCopyRight($siteLangId, $backEnd = false, $includeVersion = false)
    {
        if (self::demoUrl()) {
            $str = 'Copyright &copy; ' . date('Y') . ' <a target="_blank" href="https://yo-kart.com">Yo!Kart</a></a>';
        } else {
            if ((FatApp::getController() == 'HomeController' || true == $backEnd) && false == WHITE_LABELED) {
                $str = 'Copyright &copy; ' . date('Y') . ' ' . FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId, FatUtility::VAR_STRING, '');
            } else {
                $replacements = array(
                    '{YEAR}' => '&copy; ' . date("Y"),
                    '{PRODUCT}' => FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId, FatUtility::VAR_STRING, '')
                );
                $str = self::replaceStringData(Labels::getLabel('LBL_COPYRIGHT_TEXT', $siteLangId), $replacements);
            }
        }

        if (true == $includeVersion) {
            $str .= ' ' . CONF_WEB_APP_VERSION;
        }
        return $str;
    }

    public static function getTechPartner($backEnd = false)
    {
        $str = '';
        if (self::demoUrl()) {
            $str = 'Developed by <a target="_blank" href="https://www.fatbit.com/">FATbit Technologies</a>';
        } else {
            if ((FatApp::getController() == 'HomeController' || true == $backEnd) && false == WHITE_LABELED) {
                $str = 'Technology Partner: <a href="https://www.fatbit.com/" target="_blank" rel="nofollow">FATbit</a>';
            }
        }
        return $str;
    }
}
