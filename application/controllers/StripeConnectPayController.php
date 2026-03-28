<?php

class StripeConnectPayController extends PaymentController
{
    public const KEY_NAME = 'StripeConnect';
    public $stripeConnect;
    private $liveMode = '';
    private $paymentAmount = 0;
    private $orderInfo = [];
    private $userId = 0;
    private $customerId = '';
    private $statementDescriptor = '';

    /**
     * __construct
     *
     * @param  string $action
     * @return void
     */
    public function __construct(string $action)
    {
        parent::__construct($action);

        $this->stripeConnect = LibHelper::callPlugin(self::KEY_NAME, [$this->siteLangId], $error, $this->siteLangId);
        if (false === $this->stripeConnect) {
            $this->setErrorAndRedirect($error);
        }
        $this->init();
    }

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        if ('distribute' != $this->_actionName && (UserAuthentication::isUserLogged() || UserAuthentication::isGuestUserLogged())) {
            $this->userId = UserAuthentication::getLoggedUserId(true);
            if (1 > $this->userId) {
                $msg = Labels::getLabel('ERR_INVALID_USER', $this->siteLangId);
                $this->setErrorAndRedirect($msg);
            }
        }

        if (false === $this->stripeConnect->init($this->userId)) {
            $this->setErrorAndRedirect($this->stripeConnect->getError());
        }

        if (!empty($this->stripeConnect->getError())) {
            $this->setErrorAndRedirect($this->stripeConnect->getError());
        }

        $this->settings = $this->stripeConnect->getSettings();

        if (isset($this->settings['env']) && Plugin::ENV_PRODUCTION == $this->settings['env']) {
            $this->liveMode = "live_";
        }
    }

    /**
     * allowedCurrenciesArr
     *
     * @return array
     */
    protected function allowedCurrenciesArr(): array
    {
        return [
            'USD', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'STD', 'SZL', 'THB', 'TJS', 'TND', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'UYU', 'UZS', 'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW', 'USDC', 'GHS', 'EEK', 'LVL', 'SVC', 'VEF', 'LTL'
        ];
    }

    protected function minChargeAmountCurrencies()
    {
        return [
            'USD' => 0.50, 'AED' => 2.00, 'AUD' => 0.50, 'BGN' => 1.00, 'BRL' => 0.50, 'CAD' => 0.50, 'CHF' => 0.50, 'CZK' => 15.00,
            'DKK' => 2.50, 'EUR' => 0.50, 'GBP' => 0.30, 'HKD' => 4.00, 'HUF' => 175.00, 'INR' => 0.50, 'JPY' => 50, 'MXN' => 10,
            'MYR' => 2, 'NOK' => 3.00, 'NZD' => 0.50, 'PLN' => 2.00, 'RON' => 2.00, 'SEK' => 3.00, 'SGD' => 0.50
        ];
    }

    protected function zeroDecimalCurrencies()
    {
        return [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
        ];
    }

    /**
     * getOrderInfo
     *
     * @param  string $orderId
     * @return array
     */
    private function getOrderInfo($orderId): array
    {
        if (empty($this->orderInfo)) {
            $orderPaymentObj = new OrderPayment($orderId, $this->siteLangId);
            $orderPaymentObj->markUserIsGuest(UserAuthentication::isGuestUserLogged());
            $this->paymentAmount = $orderPaymentObj->getOrderPaymentGatewayAmount();
            $this->orderInfo = $orderPaymentObj->getOrderPrimaryinfo();
        }
        return $this->orderInfo;
    }

    /**
     * charge
     *
     * @param  string $orderId
     * @return void
     */
    public function charge($orderId)
    {
        $this->orderId = $orderId;
        if (empty(trim($this->orderId))) {
            $msg = Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId);
            $this->setErrorAndRedirect($msg);
        }

        $this->orderInfo = $this->getOrderInfo($this->orderId);

        if (!$this->orderInfo['id']) {
            $msg = Labels::getLabel('ERR_INVALID_ACCESS', $this->siteLangId);
            $this->setErrorAndRedirect($msg);
        }

        if ($this->orderInfo["order_payment_status"] != Orders::ORDER_PAYMENT_PENDING) {
            $msg = Labels::getLabel('ERR_INVALID_ORDER._ALREADY_PAID_OR_CANCELLED', $this->siteLangId);
            $this->setErrorAndRedirect($msg);
        }

        if (array_key_exists($this->systemCurrencyCode, $this->minChargeAmountCurrencies())) {
            $stripeMinAmount = $this->minChargeAmountCurrencies()[$this->systemCurrencyCode];
            if ($stripeMinAmount > $this->paymentAmount) {
                $this->error = CommonHelper::replaceStringData(Labels::getLabel('ERR_MINIMUM_STRIPE_CHARGE_AMOUNT_IS_{MIN-AMOUNT}', $this->siteLangId), ['{MIN-AMOUNT}' => $stripeMinAmount]);
            }
        }

        if (UserAuthentication::isUserLogged() || UserAuthentication::isGuestUserLogged()) {
            $requestParam = $this->stripeConnect->formatCustomerDataFromOrder($this->orderInfo);
            if (false === $this->stripeConnect->bindCustomer($requestParam)) {
                $this->setErrorAndRedirect($this->stripeConnect->getError());
            }
            $this->customerId = $this->stripeConnect->getCustomerId();
            $this->set('customerId', $this->customerId);
        }

        $orderObj = new Orders();
        $orderProducts = $orderObj->getChildOrders(array('order_id' => $this->orderInfo['id']), $this->orderInfo['order_type'], $this->orderInfo['order_language_id']);

        $cancelBtnUrl = CommonHelper::getPaymentCancelPageUrl();
        if ($this->orderInfo['order_type'] == Orders::ORDER_WALLET_RECHARGE) {
            $cancelBtnUrl = CommonHelper::getPaymentFailurePageUrl();
        }
        $successUrl = CommonHelper::generateFullUrl('custom', 'paymentSuccess', [$this->orderInfo['order_number']]);

        $data = array();
        $orderFormattedData = $this->stripeConnect->formatCustomerDataFromOrder($this->orderInfo);
        $data = [
            'mode' => 'payment',
            'payment_method_types' => $this->stripeConnect->getOtherPaymentMethods(),
            'success_url' => $successUrl,
            'cancel_url' => $cancelBtnUrl,
            'client_reference_id' => $this->orderInfo['order_number'],
            'customer' => $this->customerId,
        ];

        if ($this->orderInfo['order_type'] == Orders::ORDER_PRODUCT) {
            $data = array_merge(
                $data,
                [
                    'line_items' => [],
                    'payment_intent_data' => [
                        'receipt_email' => FatApp::getConfig('CONF_SITE_OWNER_EMAIL'),
                        'shipping' => $orderFormattedData['shipping'],
                        'metadata' => [
                            'orderId' => $this->orderId
                        ]
                    ]
                ]
            );
            foreach ($orderProducts as $op) {
                $netAmount = CommonHelper::orderProductAmount($op, 'NETAMOUNT');
                $amountToBePaidToSeller = CommonHelper::orderProductAmount($op, 'NETAMOUNT', false, User::USER_TYPE_SELLER);
                $amountToBePaidToSeller = ($amountToBePaidToSeller - $op['op_commission_charged']);

                $singleItemPrice = $netAmount / $op['op_qty'];
                $priceData = [
                    'unit_amount' => $this->convertInPaisa($singleItemPrice),
                    'currency' => $this->orderInfo['order_currency_code'],
                    'product_data' => [
                        'name' => $op['op_selprod_title'],
                        'metadata' => [
                            'id' => $op['op_id']
                        ]
                    ],
                    'nickname' => Labels::getLabel('MSG_SHIPPING_COST_AND_TAX_CHARGES_INCLUDED', $this->siteLangId)
                ];

                if (false === $this->stripeConnect->createPriceObject($priceData)) {
                    $this->setErrorAndRedirect($this->stripeConnect->getError());
                }

                $data['line_items'][] = [
                    'price' => $this->stripeConnect->getPriceId(),
                    'quantity' => $op['op_qty']
                ];

                $data['payment_intent_data']['statement_descriptor'] = FatApp::getConfig("CONF_WEBSITE_NAME_" . $this->siteLangId) ?? $this->orderInfo['order_number'];
            }
        } else if ($this->orderInfo['order_type'] == Orders::ORDER_SUBSCRIPTION) {
            $stipePlanInfo = SellerPackagePlans::getAttributesById($orderProducts[key($orderProducts)]['ossubs_plan_id']);
            $planPayable = SellerPackagePlans::getPlanPayableAmountFromRow($stipePlanInfo);
            $packageName = current(SellerPackages::getAttributesByLangId($this->siteLangId, $stipePlanInfo['spplan_spackage_id'], ['COALESCE(spackage_name, spackage_identifier) as spackage_name'], applicationConstants::JOIN_RIGHT));
            $nickname = Labels::getLabel('MSG_{NAME}_SUBSCRIPTION_PAYMENT', $this->siteLangId);

            $priceData = [
                'unit_amount' => $this->convertInPaisa($planPayable),
                'currency' => $this->orderInfo['order_currency_code'],
                'product_data' => [
                    'name' => $packageName,
                    'metadata' => [
                        'spplan_id' => $stipePlanInfo['spplan_id'],
                        'spplan_spackage_id' => $stipePlanInfo['spplan_spackage_id']
                    ]
                ],
                'nickname' => CommonHelper::replaceStringData($nickname, ['{NAME}' => $packageName])
            ];

            if (false === $this->stripeConnect->createPriceObject($priceData)) {
                $this->setErrorAndRedirect($this->stripeConnect->getError());
            }

            $data = array_merge(
                $data,
                [
                    'line_items' => [
                        [
                            'price' => $this->stripeConnect->getPriceId(),
                            'quantity' => 1,
                            'description' => CommonHelper::replaceStringData($nickname, ['{NAME}' => $packageName])
                        ],
                    ],
                    'payment_intent_data' => [
                        'receipt_email' => FatApp::getConfig('CONF_SITE_OWNER_EMAIL'),
                        'metadata' => [
                            'orderId' => $orderId
                        ]
                    ]
                ]
            );
        } else if ($this->orderInfo['order_type'] == Orders::ORDER_WALLET_RECHARGE) {
            $priceData = [
                'unit_amount' => $this->convertInPaisa($this->paymentAmount),
                'currency' => $this->orderInfo['order_currency_code'],
                'product_data' => [
                    'name' => Labels::getLabel('MSG_WALLET_RECHARGE', $this->siteLangId),
                    'metadata' => [
                        'id' => $this->orderInfo['id'],
                        'invoice' => $this->orderInfo['invoice'],
                        'customer_id' => $this->orderInfo['customer_id'],
                        'customer_name' => $this->orderInfo['customer_name'],
                        'customer_email' => $this->orderInfo['customer_email'],
                        'customer_phone_dcode' => $this->orderInfo['customer_phone_dcode'],
                        'customer_phone' => $this->orderInfo['customer_phone'],
                    ]
                ],
                'nickname' => Labels::getLabel('MSG_WALLET_RECHARGE', $this->siteLangId),
            ];

            if (false === $this->stripeConnect->createPriceObject($priceData)) {
                $this->setErrorAndRedirect($this->stripeConnect->getError());
            }

            $data = array_merge(
                $data,
                [
                    'line_items' => [
                        [
                            'price' => $this->stripeConnect->getPriceId(),
                            'quantity' => 1,
                            'description' => Labels::getLabel('MSG_WALLET_RECHARGE', $this->siteLangId),
                        ],
                    ],
                    'payment_intent_data' => [
                        'receipt_email' => FatApp::getConfig('CONF_SITE_OWNER_EMAIL'),
                        'metadata' => [
                            'orderId' => $orderId
                        ]
                    ]
                ]
            );
        } else {
            $msg = Labels::getLabel('ERR_INVALID_ORDER_TYPE', $this->siteLangId);
            $this->setErrorAndRedirect($msg);
        }

        if (false === $this->stripeConnect->initiateSession($data)) {
            $this->setErrorAndRedirect($this->stripeConnect->getError());
        }

        if (true === MOBILE_APP_API_CALL) {
            $this->set('paymentAmount', $this->paymentAmount);
            $this->set('orderInfo', $this->orderInfo);
            $this->_template->render();
        }

        $this->set('exculdeMainHeaderDiv', true);
        $this->set('sessionId', $this->stripeConnect->getSessionId());
        $this->set('publishableKey', $this->settings[$this->liveMode . 'publishable_key']);

        if (FatUtility::isAjaxCall()) {
            $json['html'] = $this->_template->render(false, false, 'stripe-connect-pay/charge-ajax.php', true, false);
            FatUtility::dieJsonSuccess($json);
        }

        $this->_template->render(true, false);
    }

    /**
     * convertInPaisa
     *
     * @param  mixed $amount
     * @return void
     */
    private function convertInPaisa($amount)
    {
        if (in_array($this->systemCurrencyCode, $this->zeroDecimalCurrencies())) {
            return round($amount);
        }
        $amount = number_format($amount, 2, '.', '');
        return $amount * 100;
    }

    /**
     * distribute
     *
     * @return void
     */
    public function distribute()
    {
        if (false === $this->stripeConnect->init()) {
            $error = [
                'msg' => $this->stripeConnect->getError()
            ];
            SystemLog::transaction(json_encode($error), self::KEY_NAME);
            CommonHelper::printArray($error, true);
        }

        $payloadStr = @file_get_contents('php://input');
        $payload = json_decode($payloadStr, true);

        if (empty($payload)) {
            $error = [
                'msg' => Labels::getLabel('ERR_INVALID_REQUEST', $this->siteLangId),
                'response' => $payload,
            ];
            SystemLog::transaction(json_encode($error), self::KEY_NAME);
            return;
        }

        $orderId = isset($payload['data']['object']['metadata']['orderId']) ? $payload['data']['object']['metadata']['orderId'] : '';
        $status = isset($payload['data']['object']['status']) ? $payload['data']['object']['status'] : Labels::getLabel("ERR_FAILURE", $this->siteLangId);

        if (StripeConnect::PAYMENT_RESPONSE_INTENT_TYPE_SUCCESS != $payload['type']) {
            $msg = Labels::getLabel('ERR_UNABLE_TO_CHARGE_:_{STATUS}', $this->siteLangId);
            $msg = CommonHelper::replaceStringData($msg, ['{STATUS}' => $status]);
            $recordId = empty($orderId) ? time() : $orderId;

            $error = [
                'msg' => $msg,
                'response' => $payload,
            ];
            SystemLog::transaction(json_encode($error), self::KEY_NAME . "-" . $recordId);
            return;
        }


        $this->orderId = $orderId;
        $orderPaymentObj = new OrderPayment($this->orderId, $this->siteLangId);
        $this->orderInfo = $this->getOrderInfo($this->orderId);
        $paymentIntendId = isset($payload['data']['object']['id']) ? $payload['data']['object']['id'] : '';

        if (empty($orderId) || empty($paymentIntendId)) {
            $error = [
                'msg' => Labels::getLabel('ERR_INVALID_REQUEST_ORDER/PAYMENT_INTENT_ID', $this->siteLangId),
                'response' => $payload,
            ];
            SystemLog::transaction(json_encode($error), self::KEY_NAME . "-" . $orderId);
            return;
        }

        if ($this->orderInfo["order_payment_status"] != Orders::ORDER_PAYMENT_PENDING) {
            $error = [
                'msg' => Labels::getLabel('ERR_INVALID_ORDER._ALREADY_PAID_OR_CANCELLED', $this->siteLangId),
                'response' => $payload,
            ];
            SystemLog::transaction(json_encode($error), self::KEY_NAME . "-" . $orderId);
            return;
        }

        $chargeResponse = isset($payload['data']['object']['charges']['data']) ? current($payload['data']['object']['charges']['data']) : [];
        
        if (empty($chargeResponse)) {
            $latestCharge = isset($payload['data']['object']['latest_charge']) ? $payload['data']['object']['latest_charge'] : '';
            if(!$this->stripeConnect->doChargeRetrieve($latestCharge)) {
                $error = [
                    'msg' => Labels::getLabel('ERR_INVALID_ORDER_CHARGE', $this->siteLangId),
                    'response' => $payload,
                ];
                SystemLog::transaction(json_encode($error), self::KEY_NAME . "-" . $orderId);
                return;
            }
            $chargeResponse = json_decode(json_encode($this->stripeConnect->getResponse()), true);
            if (empty($chargeResponse)) {
                $error = [
                    'msg' => Labels::getLabel('ERR_INVALID_ORDER_CHARGE', $this->siteLangId),
                    'response' => $payload,
                ];
                SystemLog::transaction(json_encode($error), self::KEY_NAME . "-" . $orderId);
                return;
            }
        }

        $chargeId = $chargeResponse['id'];
        $message = $chargeResponse['status'];

        /* Recording Payment in DB */
        $this->paymentAmount = $chargeResponse['amount_captured'];
        if (!in_array($this->systemCurrencyCode, $this->zeroDecimalCurrencies())) {
            $this->paymentAmount = $this->paymentAmount / 100;
        }

        if (false === $orderPaymentObj->addOrderPayment($this->settings["plugin_code"], $chargeId, $this->paymentAmount, Labels::getLabel("MSG_RECEIVED_PAYMENT", $this->siteLangId), json_encode($chargeResponse), false, 0, Orders::ORDER_PAYMENT_PAID)) {
            $orderPaymentObj->addOrderPaymentComments($message);
        }

        if ($this->orderInfo['order_type'] == Orders::ORDER_PRODUCT) {
            $orderObj = new Orders();
            $orderProducts = $orderObj->getChildOrders(array('order_id' => $this->orderInfo['id']), $this->orderInfo['order_type'], $this->orderInfo['order_language_id']);

            foreach ($orderProducts as $op) {
                $netSellerAmount = CommonHelper::orderProductAmount($op, 'NET_VENDOR_AMOUNT', false, User::USER_TYPE_SELLER);
                $discount = CommonHelper::orderProductAmount($op, 'DISCOUNT');
                $rewardPoint = CommonHelper::orderProductAmount($op, 'REWARDPOINT');
                $totalDiscount = abs($discount) + abs($rewardPoint);

                $firstTransferAmount = $netSellerAmount - $totalDiscount;
                $pendingTransferAmount = $totalDiscount;
                $sellerShippingApiCharges = CommonHelper::orderProductAmount($op, 'SHIPPING_API');

                if (0 == $pendingTransferAmount) {
                    $firstTransferAmount = $firstTransferAmount - $op['op_commission_charged'];
                } else {
                    if ($op['op_commission_charged'] <= $pendingTransferAmount) {
                        $pendingTransferAmount = $pendingTransferAmount - $op['op_commission_charged'];
                    } else {
                        $pendingTransferAmount = $op['op_commission_charged'] - $pendingTransferAmount;
                        $firstTransferAmount = $firstTransferAmount - $pendingTransferAmount;
                    }
                }
                $accountId = User::getUserMeta($op['op_selprod_user_id'], 'stripe_account_id');
                // Credit sold product amount to seller wallet.
                $msg = 'MSG_PRODUCT_SOLD_#{invoice-no}.';
                if (0 < $pendingTransferAmount) {
                    $msg .= "_DISCOUNT/REWARD_POINTS_INCLUSIVE.";
                }
                $comments = Labels::getLabel($msg, $this->siteLangId);
                $comments = CommonHelper::replaceStringData($comments, ['{invoice-no}' => $op['op_invoice_number']]);
                Transactions::creditWallet($op['op_selprod_user_id'], Transactions::TYPE_PRODUCT_SALE, $netSellerAmount, $this->siteLangId, $comments, $op['op_id']);

                $commComments = Labels::getLabel('MSG_COMMISSION_CHARGED._#{invoice-no}', $this->siteLangId);
                $commComments = CommonHelper::replaceStringData($commComments, ['{invoice-no}' => $op['op_invoice_number']]);
                Transactions::debitWallet($op['op_selprod_user_id'], Transactions::TYPE_ADMIN_COMMISSION, $op['op_commission_charged'], $this->siteLangId, $commComments, $op['op_id']);

                if (0 < $sellerShippingApiCharges) {
                    $firstTransferAmount = $firstTransferAmount - $sellerShippingApiCharges;
                    $apiComments = commonHelper::replaceStringData(Labels::getLabel('MSG_DEDUCTED_ADMIN_SHIPPING_API_CHARGES_{invoice}', $this->siteLangId), ['{invoice}' => $op['op_invoice_number']]);
                    Transactions::debitWallet($op['op_selprod_user_id'], Transactions::TYPE_ADMIN_SHIPPING_API_CHARGES, $sellerShippingApiCharges, $this->siteLangId, $apiComments, $op['op_id']);
                    if (1 > $firstTransferAmount) {
                        return;
                    }
                }

                if (!empty($accountId) &&  0 < $firstTransferAmount) {
                    $charge = [
                        'amount' => $this->convertInPaisa($firstTransferAmount),
                        'currency' => $this->orderInfo['order_currency_code'],
                        'destination' => $accountId,
                        // 'transfer_group' => $op['op_invoice_number'],
                        'description' => $comments,
                        'metadata' => [
                            'op_id' => $op['op_id']
                        ],
                        'source_transaction' => $chargeId
                    ];

                    if (false === $this->stripeConnect->doTransfer($charge)) {
                        $error = [
                            'msg' => $this->stripeConnect->getError(),
                            'response' => $charge,
                        ];
                        SystemLog::transaction(json_encode($error), self::KEY_NAME . "-" . $orderId);
                        continue;
                    }

                    $resp = $this->stripeConnect->getResponse();

                    if (empty($resp->id)) {
                        $error = [
                            'msg' => Labels::getLabel('ERR_UNABLE_TO_TRANFER', $this->siteLangId),
                            'response' => $resp,
                        ];
                        SystemLog::transaction(json_encode($error), self::KEY_NAME . "-" . $orderId);
                        continue;
                    }
                    // Debit sold product amount from seller wallet.
                    $comments = $comments . ' ' . Labels::getLabel('MSG_TRANSFERED_TO_ACCOUNT_{account-id}.', $this->siteLangId);
                    $comments = CommonHelper::replaceStringData($comments, ['{account-id}' => $accountId]);
                    Transactions::debitWallet($op['op_selprod_user_id'], Transactions::TYPE_TRANSFER_TO_THIRD_PARTY_ACCOUNT, $firstTransferAmount, $this->siteLangId, $comments, $op['op_id'], $resp->id);
                }

                if (0 < $pendingTransferAmount) {
                    // Credit sold product discount amount to seller wallet.
                    $discountComments = Labels::getLabel('MSG_AMOUNT_CREDITED_FOR_DISCOUNT_APPLIED_ON_PRODUCT_SOLD_#{invoice-no}.', $this->siteLangId);
                    $discountComments = CommonHelper::replaceStringData($discountComments, ['{invoice-no}' => $op['op_invoice_number']]);
                    /*  Transactions::creditWallet($op['op_selprod_user_id'], Transactions::TYPE_PRODUCT_SALE, $discount, $this->siteLangId, $discountComments, $op['op_id']); */

                    unset($charge['source_transaction']);
                    $charge['amount'] = $this->convertInPaisa($pendingTransferAmount);
                    $charge['description'] = $discountComments;
                    $charge['metadata']['source_transaction'] = $chargeId;
                    if (false === $this->stripeConnect->doTransfer($charge)) {
                        $error = [
                            'msg' => $this->stripeConnect->getError(),
                            'response' => $this->stripeConnect->getResponse(),
                        ];
                        SystemLog::transaction(json_encode($error), self::KEY_NAME . "-" . $orderId);
                        continue;
                    }

                    $resp = $this->stripeConnect->getResponse();
                    if (empty($resp->id)) {
                        $error = [
                            'msg' => Labels::getLabel('ERR_UNABLE_TO_TRANFER_PENDING_AMOUNT', $this->siteLangId),
                            'response' => $resp,
                        ];
                        SystemLog::transaction(json_encode($error), self::KEY_NAME . "-" . $orderId);
                        continue;
                    }

                    // Debit sold product discount amount from seller wallet.
                    $comments = Labels::getLabel('MSG_AMOUNT_DEBITED_FOR_DISCOUNT_APPLIED_ON_PRODUCT_SOLD_#{invoice-no}._TRANSFERED_TO_ACCOUNT_{account-id}.', $this->siteLangId);
                    $comments = CommonHelper::replaceStringData($comments, ['{invoice-no}' => $op['op_invoice_number'], '{account-id}' => $accountId]);
                    Transactions::debitWallet($op['op_selprod_user_id'], Transactions::TYPE_TRANSFER_TO_THIRD_PARTY_ACCOUNT, $pendingTransferAmount, $this->siteLangId, $comments, $op['op_id'], $resp->id);
                }
            }
        }
    }

    /**
     * setError
     *
     * @param  mixed $msg
     * @return void
     */
    private function setError(string $msg = "")
    {
        $msg = !empty($msg) ? $msg : $this->stripeConnect->getError();
        LibHelper::exitWithError($msg, true);
    }


    /**
     * getCustomer
     *
     * @return void
     */
    public function getCustomer()
    {
        if (empty($this->stripeConnect->getCustomerId())) {
            $this->setError(Labels::getLabel('ERR_INVALID_CUSTOMER', $this->siteLangId));
        }
        $this->stripeConnect->loadCustomer();
        $customerInfo = $this->stripeConnect->getResponse()->toArray();
        $this->set('customerInfo', $customerInfo);
        $this->_template->render();
    }

    /**
     * getExternalLibraries
     *
     * @return void
     */
    public function getExternalLibraries()
    {
        $json['libraries'] = ['https://js.stripe.com/v3/'];
        FatUtility::dieJsonSuccess($json);
    }
}