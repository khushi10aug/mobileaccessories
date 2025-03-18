<?php
require_once CONF_INSTALLATION_PATH . 'vendor/autoload.php';

use Google\Client as GoogleClient;

class Notifications extends MyAppModel
{
    public const DB_TBL = 'tbl_user_notifications';
    public const DB_TBL_PREFIX = 'unotification_';

    public const SELLER_ONLY_NOTIFICATION_TYPES = [
        'SELLER_ORDER',
        'ORDER_CANCELLATION_REQUEST',
        'SELLER_RETURN_REQUEST',
        'MESSAGE_RETURN_REQUEST',
    ];

    public function __construct($unotificationId = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $unotificationId);
    }

    public static function getSearchObject()
    {
        $srch = new SearchBase(static::DB_TBL, 'unt');
        return $srch;
    }

    public function addNotification($data, $sendInstantNotification = false)
    {
        $userId = FatUtility::int($data['unotification_user_id']);
        if (1 > $userId) {
            trigger_error(Labels::getLabel('ERR_INVALID_REQUEST', $this->commonLangId), E_USER_ERROR);
            return false;
        }
        $data['unotification_date'] = date('Y-m-d H:i:s');
        if (true === $sendInstantNotification) {
            $data['unotification_sent_to_app'] = 1;
        }
        $this->assignValues($data);
        if (!$this->save()) {
            return false;
        }

        if (true === $sendInstantNotification) {
            $serviceAccountJsonKey = FatApp::getConfig("CONF_FIREBASE_SERVICE_ACCOUNT_JSON_KEY", FatUtility::VAR_STRING, '');
            if (trim($serviceAccountJsonKey) == '') {
                return $this->getMainTableRecordId();
            }
            $config = json_decode($serviceAccountJsonKey, true);
            if (json_last_error() !== JSON_ERROR_NONE &&  $config['project_id'] == '') {
                return $this->getMainTableRecordId();
            }

            $uObj = new User($userId);
            $fcmDeviceIds = $uObj->getPushNotificationTokens();
            if (empty($fcmDeviceIds)) {
                return $this->getMainTableRecordId();
            }

            $siteName = FatApp::getConfig('CONF_WEBSITE_NAME_' . $this->commonLangId, FatUtility::VAR_STRING, 'Yo!Kart');
            $message = array('title' => empty($siteName) ? $_SERVER['SERVER_NAME'] : $siteName, 'text' => $data['unotification_body'], 'type' => $data['unotification_type'], 'customData' => $data['customData'] ?? []);
            foreach ($fcmDeviceIds as $pushNotificationApiToken) {
                self::sendPushNotification($config, $pushNotificationApiToken['uauth_fcm_id'], $pushNotificationApiToken['uauth_device_os'], $message);
            }
        }

        return $this->getMainTableRecordId();
    }

    public static function sendPushNotification($config, $deviceToken, $os, $notiData = array())
    {
        $client = new GoogleClient();
        $client->setAuthConfig($config);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $accessTokenData = $client->fetchAccessTokenWithAssertion();
        $access_token = $accessTokenData['access_token'];

        $headers = [
            "Authorization: Bearer $access_token",
            'Content-Type: application/json'
        ];

        $notificationId = $notiData['notification_id'] ?? 0;

        $msg = [
            "title" => (string)($notiData['title'] ?? ''),
            "message" =>  (string)($notiData['text'] ?? ''),
            "type" => (string)($notiData['type'] ?? ''),
            'image' => ($notiData['image'] ?? ''),
        ];

        if (isset($notiData['customData']) && !empty($notiData['customData'])) {
            $msg = array_merge($msg, CommonHelper::cleanArray($notiData['customData']));
        }

        $data = [
            "message" => [
                "token" => $deviceToken,
                "notification" => [
                    "title" => $msg['title'],
                    "body" =>  $msg['message'],
                    "image" =>  $msg['image'],
                ],
                "data" => $msg,
                "android" => [
                    "priority" => 'high',
                ],
                "apns" => [
                    "headers" => [
                        "apns-priority" => "10",
                    ],
                ],
                "webpush" => [
                    "headers" => [
                        "Urgency" => 'high',
                    ],
                ],
            ]
        ];

        if (User::DEVICE_OS_ANDROID == $os) {
            unset($data['message']['notification']);
        }

        $payload = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/' . $config['project_id'] . '/messages:send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output for debugging
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            if (empty($response) || false == $response) {
                $response = Labels::getLabel('ERR_UNABLE_TO_SEND_PUSH_NOTIFICATION._ERROR_{ERROR}');
                $response = CommonHelper::replaceStringData($response, ['{ERROR}' => curl_error($ch)]);
            } else if (is_array($response)) {
                $response = json_encode($response);
            }
            $errorBody = [
                'headers' => $headers,
                'requestBody' => $data,
            ];

            SystemLog::plugin(json_encode($errorBody), $response, 'Firebase Push Notification Failure');
            curl_close($ch);
            return false;
        } else {
            $response = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE || !empty($response['error'])) {
                SystemLog::plugin(json_encode($data), json_encode($response), 'Firebase Push Notification Failure');
                curl_close($ch);
                return false;
            }
            if ($notificationId) {
                FatApp::getDb()->updateFromArray(
                    self::DB_TBL,
                    array('unotification_sent_to_app' => 1),
                    array(
                        'smt' => "unotification_id = ?",
                        'vals' => array((int)$notificationId)
                        )
                    );
            }
        }
        curl_close($ch);
        return true;
    }

    public function readUserNotification($notificationId, $userId)
    {
        $smt = array(
            'smt' => static::DB_TBL_PREFIX . 'id = ? AND ' . static::DB_TBL_PREFIX . 'user_id = ?',
            'vals' => array((int)$notificationId, (int)$userId)
        );
        if (!FatApp::getDb()->updateFromArray(static::DB_TBL, array(static::DB_TBL_PREFIX . 'is_read' => 1), $smt)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public function getUnreadNotificationCount($userId)
    {
        $srch = new SearchBase(static::DB_TBL, 'unt');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('unt.unotification_user_id', '=', $userId);
        $srch->addCondition('unt.unotification_is_read', '=', 0);
        $srch->addMultipleFields(array("count(unt.unotification_id) as UnReadNotificationCount"));
        $rs = $srch->getResultSet();
        if (!$rs) {
            return 0;
        }
        $res = FatApp::getDb()->fetch($rs);
        return $res['UnReadNotificationCount'];
    }

    public static function triggerNotification($notificationId = 0)
    {
        $srch = new SearchBase(static::DB_TBL, 'unt');
        if ($notificationId) {
            $srch->addCondition('unotification_id', '=', $notificationId);
        } else {
            $srch->addCondition('unotification_sent_to_app', '=', applicationConstants::NO);
            $srch->addCondition('unotification_is_read', '=', applicationConstants::NO);
        }
        $srch->addOrder('unotification_date', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(50);
        $rs = $srch->getResultSet();
        $results = FatApp::getDb()->fetchAll($rs);
        if (empty($results)) {
            return;
        }

        $config = static::getConfArr();
        $fcmDeviceIdsArr = [];
        $unreadNotificationCount = [];

        $siteName = FatApp::getConfig('CONF_WEBSITE_NAME_' . CommonHelper::getLangId(), FatUtility::VAR_STRING, ' ');

        foreach ($results as $data) {
            if (1 > $data['unotification_user_id']) {
                continue;
            }

            if (!array_key_exists($data['unotification_user_id'], $fcmDeviceIdsArr)) {
                $uObj = new User($data['unotification_user_id']);
                $fcmDeviceIds = $uObj->getPushNotificationTokens();
                if (empty($fcmDeviceIds)) {
                    continue;
                }
                $fcmDeviceIdsArr[$data['unotification_user_id']] = $fcmDeviceIds;
            }

            $message = array(
                'title' => empty($siteName) ? $_SERVER['SERVER_NAME'] : $siteName,
                'text' => $data['unotification_body'],
                'type' => $data['unotification_type'],
                'notification_id' => $data['unotification_id']
            );

            $fcmDeviceIds = $fcmDeviceIdsArr[$data['unotification_user_id']] ?? [];
            foreach ($fcmDeviceIds as $pushNotificationApiToken) {
                self::sendPushNotification($config, $pushNotificationApiToken['uauth_fcm_id'], $pushNotificationApiToken['uauth_device_os'], $message);
            }
        }
    }

    public static function getConfArr()
    {
        $serviceAccountJsonKey = FatApp::getConfig("CONF_FIREBASE_SERVICE_ACCOUNT_JSON_KEY", FatUtility::VAR_STRING, '');
        if (trim($serviceAccountJsonKey) == '') {
            return [];
        }
        $config = json_decode($serviceAccountJsonKey, true);
        if (json_last_error() !== JSON_ERROR_NONE &&  $config['project_id'] == '') {
            return [];
        }

        return $config;
    }
}
