<?php

class RazorpayPayController extends PaymentController
{
    public const KEY_NAME = "Razorpay";

    public function __construct($action)
    {
        parent::__construct($action);
        $this->init();
    }

    protected function allowedCurrenciesArr()
    {
        return [
            'AED', 'ALL', 'AMD', 'ARS', 'AUD', 'AWG', 'BBD', 'BDT', 'BMD', 'BND', 'BOB', 'BSD', 'BWP', 'BZD', 'CAD', 'CHF', 'CNY', 'COP', 'CRC', 'CUP', 'CZK', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD', 'GBP', 'GIP', 'GMD', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'JMD', 'KES', 'KGS', 'KHR', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'MAD', 'MDL', 'MKD', 'MMK', 'MNT', 'MOP', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PEN', 'PGK', 'PHP', 'PKR', 'QAR', 'RUB', 'SAR', 'SCR', 'SEK', 'SGD', 'SLL', 'SOS', 'SSP', 'SVC', 'SZL', 'THB', 'TTD', 'TZS', 'USD', 'UYU', 'UZS', 'YER', 'ZAR'
        ];
    }
    
    private function init(): void
    {
        if (false === $this->plugin->validateSettings($this->siteLangId)) {
            $this->setErrorAndRedirect($this->plugin->getError());
        }

        $this->settings = $this->plugin->getSettings();
    }

    public function charge($orderId)
    {
        $orderPaymentObj = new OrderPayment($orderId, $this->siteLangId);
        $paymentAmount = $orderPaymentObj->getOrderPaymentGatewayAmount();
        $orderInfo = $orderPaymentObj->getOrderPrimaryinfo();

        if (!empty($orderInfo) && $orderInfo["order_payment_status"] != Orders::ORDER_PAYMENT_PENDING) {
            $msg = Labels::getLabel('ERR_INVALID_ORDER_PAID_CANCELLED', $this->siteLangId);
            $this->setErrorAndRedirect($msg, FatUtility::isAjaxCall());
        }

        $frm = $this->getPaymentForm($orderId);
        $this->set('frm', $frm);

        $cancelBtnUrl = CommonHelper::getPaymentCancelPageUrl();
        if ($orderInfo['order_type'] == Orders::ORDER_WALLET_RECHARGE) {
            $cancelBtnUrl = CommonHelper::getPaymentFailurePageUrl();
        }

        $this->set('cancelBtnUrl', $cancelBtnUrl);

        $this->set('paymentAmount', $paymentAmount);
        $this->set('orderInfo', $orderInfo);
        $this->set('paymentSettings', $this->settings);
        $this->set('paymentSuccessUrl', UrlHelper::generateUrl('custom', 'paymentSuccess', array($orderInfo['order_number'])));
        $this->set('paymentCallbackUrl', UrlHelper::generateFullUrl('RazorpayPay', 'callback'));
        $this->set('exculdeMainHeaderDiv', true);
        if (FatUtility::isAjaxCall()) {
            $json['html'] = $this->_template->render(false, false, 'razorpay-pay/charge-ajax.php', true, false);
            FatUtility::dieJsonSuccess($json);
        }
        $this->_template->render(true, false);
    }

    /*public function callback()
    {
        $post = FatApp::getPostedData();
       
        $razorpay_payment_id = $post['razorpay_payment_id'];
        $merchant_order_id = (isset($post['merchant_order_id'])) ? $post['merchant_order_id'] : 0;
        $orderPaymentObj = new OrderPayment($merchant_order_id, $this->siteLangId);
        $paymentGatewayCharge = $orderPaymentObj->getOrderPaymentGatewayAmount();
        $payment_gateway_charge_in_paisa = $paymentGatewayCharge * 100;
        if ($paymentGatewayCharge > 0) {
            $success = false;
            $error = "";
            try {
                $url = 'https://api.razorpay.com/v1/payments/' . $razorpay_payment_id . '/capture';
                $fields_string = "amount=$payment_gateway_charge_in_paisa";
                //cURL Request
                $ch = curl_init();
                //set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERPWD, $this->settings['merchant_key_id'] . ":" . $this->settings['merchant_key_secret']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                //execute post
                $result = curl_exec($ch);
                $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				
				// DEBUG ONLY — DO NOT KEEP IN PRODUCTION
			    //file_put_contents(__DIR__ . '/razorpay_debug.log', "Key ID: " . $this->settings['merchant_key_id'] . "\nSecret: " . $this->settings['merchant_key_secret'], FILE_APPEND);
               
               // echo"<pre>";			   
				//print_r($result);
				//die;
				//echo"<hr>";
				//print_r($http_status);
				//die;
				  

                if ($result === false) {
                    $success = false;
                    $error = 'Curl error: ' . curl_error($ch);
                } else {
                    $response_array = json_decode($result, true);
                    //Check success response
                    if ($http_status === 200 and isset($response_array['error']) === false) {
                        $success = true;
                    } else {
                        $success = false;
                        if (!empty($response_array['error']['code'])) {
                            $error = $response_array['error']['code'] . ":" . $response_array['error']['description'];
                        } else {
                            $error = "RAZORPAY_ERROR:Invalid Response <br/>" . $result;
                        }
                    }
                }
                //close connection
                curl_close($ch);
            } catch (Exception $e) {
                $success = false;
                $error = "ERROR:Request to Razorpay Failed";
            }
            if ($success === true) {
                $orderPaymentObj->addOrderPayment($this->settings["plugin_code"], $razorpay_payment_id, $paymentGatewayCharge, Labels::getLabel("MSG_RECEIVED_PAYMENT", $this->siteLangId), $result);
                FatApp::redirectUser(UrlHelper::generateUrl('custom', 'paymentSuccess', array($orderPaymentObj->getOrderNo())));
            } else {
                $orderPaymentObj->addOrderPaymentComments($error . ' Payment Failed! Check Razorpay dashboard for details of Payment Id:' . $razorpay_payment_id);             
                SystemLog::transaction($result, self::KEY_NAME . "-" . $orderPaymentObj->getOrderNo());
                
                FatApp::redirectUser(CommonHelper::getPaymentFailurePageUrl());
            }
        } else {
            FatUtility::exitWithErrorCode(404);
        }
    }*/


    public function callback()
    {
        $post = FatApp::getPostedData();
        /* Checkout JS already navigated to success; process in background via keepalive POST. */
        $async = !empty($post['async_process']);

        if (empty($post['razorpay_payment_id']) || empty($post['merchant_order_id'])) {
            if ($async) {
                http_response_code(400);
                exit('Missing payment data');
            }
            FatApp::redirectUser(CommonHelper::getPaymentFailurePageUrl());
        }

        $razorpay_payment_id = $post['razorpay_payment_id'];
        $merchant_order_id   = FatUtility::int($post['merchant_order_id']);

        $orderPaymentObj = new OrderPayment($merchant_order_id, $this->siteLangId);
        $orderInfo = $orderPaymentObj->getOrderPrimaryinfo();
        $successUrl = UrlHelper::generateUrl('custom', 'paymentSuccess', array($orderPaymentObj->getOrderNo()));

        /* Already paid (refresh / double submit / race with async). */
        if (!empty($orderInfo) && (int) $orderInfo['order_payment_status'] !== Orders::ORDER_PAYMENT_PENDING) {
            if ($async) {
                http_response_code(200);
                exit('OK');
            }
            FatApp::redirectUser($successUrl);
        }

        /*
         * Async: user is already on success page. ACK immediately, then verify
         * Razorpay + mark order paid (emails etc.) after the connection closes.
         */
        if ($async) {
            $this->acknowledgeAndContinue();
            $this->processVerifiedPayment($orderPaymentObj, $merchant_order_id, $razorpay_payment_id);
            exit;
        }

        /* Legacy form POST fallback: verify, then redirect before slow email work. */
        $result = $this->processVerifiedPayment($orderPaymentObj, $merchant_order_id, $razorpay_payment_id, false);
        if (empty($result['success'])) {
            FatApp::redirectUser(CommonHelper::getPaymentFailurePageUrl());
        }

        $this->redirectAndContinue($successUrl);
        $orderPaymentObj->addOrderPayment(
            $this->settings['plugin_code'],
            $razorpay_payment_id,
            $result['amount'],
            Labels::getLabel('MSG_RECEIVED_PAYMENT', $this->siteLangId),
            $result['response_data']
        );
        exit;
    }

    /**
     * Verify payment with Razorpay and optionally record it.
     *
     * @return array{success:bool,amount:float,response_data:string}
     */
    private function processVerifiedPayment(
        OrderPayment $orderPaymentObj,
        int $merchant_order_id,
        string $razorpay_payment_id,
        bool $recordNow = true
    ): array {
        $paymentGatewayCharge = $orderPaymentObj->getOrderPaymentGatewayAmount();
        $out = ['success' => false, 'amount' => $paymentGatewayCharge, 'response_data' => ''];

        if ($paymentGatewayCharge <= 0) {
            return $out;
        }

        $success = false;
        $error = '';
        $response_data = '';
        $result = '';

        try {
            $payment = $this->razorpayApiRequest('GET', 'https://api.razorpay.com/v1/payments/' . $razorpay_payment_id);
            $result = $payment['raw'];
            $paymentData = $payment['body'];

            $paymentOrderId = isset($paymentData['notes']['system_order_id'])
                ? FatUtility::int($paymentData['notes']['system_order_id'])
                : 0;
            if ($paymentOrderId > 0 && $paymentOrderId !== $merchant_order_id) {
                throw new Exception('Payment order mismatch');
            }

            $paidAmount = isset($paymentData['amount']) ? ((float) $paymentData['amount'] / 100) : 0;
            if ($paidAmount > 0 && abs($paidAmount - $paymentGatewayCharge) > 0.05) {
                throw new Exception('Payment amount mismatch');
            }

            if (($paymentData['status'] ?? '') === 'captured') {
                $success = true;
                $response_data = $result;
            } elseif (($paymentData['status'] ?? '') === 'authorized') {
                $amount_in_paisa = (int) round($paymentGatewayCharge * 100);
                $capture = $this->razorpayApiRequest(
                    'POST',
                    'https://api.razorpay.com/v1/payments/' . $razorpay_payment_id . '/capture',
                    'amount=' . $amount_in_paisa
                );
                $capture_response = $capture['body'];
                $alreadyCaptured = (
                    isset($capture_response['error']['description']) &&
                    $capture_response['error']['description'] === 'This payment has already been captured'
                );

                if ($capture['http_status'] === 200 || $alreadyCaptured) {
                    $success = true;
                    $response_data = $capture['raw'];
                } else {
                    $error = isset($capture_response['error']['description'])
                        ? $capture_response['error']['description']
                        : 'Razorpay capture failed with status: ' . $capture['http_status'];
                }
            } else {
                $error = 'Payment not successful. Status: ' . ($paymentData['status'] ?? 'unknown');
            }
        } catch (Exception $e) {
            $success = false;
            $error = $e->getMessage();
        }

        if ($success !== true) {
            $orderPaymentObj->addOrderPaymentComments(
                $error . ' | Razorpay Payment ID: ' . $razorpay_payment_id
            );
            SystemLog::transaction($error, self::KEY_NAME . "-" . $orderPaymentObj->getOrderNo());
            return $out;
        }

        $out['success'] = true;
        $out['response_data'] = $response_data ?: $result;

        if ($recordNow) {
            $orderPaymentObj->addOrderPayment(
                $this->settings['plugin_code'],
                $razorpay_payment_id,
                $paymentGatewayCharge,
                Labels::getLabel('MSG_RECEIVED_PAYMENT', $this->siteLangId),
                $out['response_data']
            );
        }

        return $out;
    }

    /**
     * Call Razorpay REST API with short timeouts.
     *
     * @return array{raw:string,body:array,http_status:int}
     * @throws Exception
     */
    private function razorpayApiRequest(string $method, string $url, string $postFields = ''): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $this->settings['merchant_key_id'] . ':' . $this->settings['merchant_key_secret']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: close']);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }

        $raw = curl_exec($ch);
        $http_status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($raw === false || !empty($curl_error)) {
            throw new Exception('Razorpay request failed: ' . $curl_error);
        }

        $body = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
            throw new Exception('Invalid JSON response from Razorpay');
        }

        if ($http_status !== 200 && strtoupper($method) === 'GET') {
            $desc = $body['error']['description'] ?? ('HTTP ' . $http_status);
            throw new Exception('Razorpay API error: ' . $desc);
        }

        return [
            'raw' => $raw,
            'body' => $body,
            'http_status' => $http_status,
        ];
    }

    /**
     * Close HTTP response so browser can navigate away; keep PHP running.
     */
    private function acknowledgeAndContinue(): void
    {
        ignore_user_abort(true);
        @set_time_limit(120);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        http_response_code(200);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Length: 2');
        header('Connection: close');
        echo 'OK';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            return;
        }

        flush();
        if (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
    }

    /**
     * Send Location redirect to browser, close connection, keep PHP running.
     */
    private function redirectAndContinue(string $url): void
    {
        ignore_user_abort(true);
        @set_time_limit(120);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header('Location: ' . $url, true, 302);
        header('Content-Length: 0');
        header('Connection: close');

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            return;
        }

        echo '';
        flush();
        if (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
    }
    
    
    
    private function getPaymentForm($orderId)
    {
        $frm = new Form('razorpay-form', array('id' => 'razorpay-form', 'action' => UrlHelper::generateFullUrl('RazorpayPay', 'callback'), 'class' => "form form--normal"));

        $frm->addHiddenField('', 'razorpay_payment_id', '', array('id' => 'razorpay_payment_id'));
        $frm->addHiddenField('', 'merchant_order_id', $orderId, array('id' => 'merchant_order_id'));
        $frm->addButton('', 'btn_submit', Labels::getLabel('BTN_CONFIRM', $this->siteLangId));
        return $frm;
    }

    
    public function getExternalLibraries()
    {
        $json['libraries'] = [
            'https://checkout.razorpay.com/v1/checkout.js',
        ];
        FatUtility::dieJsonSuccess($json);
    }
}
