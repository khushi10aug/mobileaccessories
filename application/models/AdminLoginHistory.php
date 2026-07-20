<?php

class AdminLoginHistory extends MyAppModel
{
    public const DB_TBL = 'tbl_admin_login_history';
    public const DB_TBL_PREFIX = 'alh_';

    public const LOGIN_TYPE_PASSWORD = 1;
    public const LOGIN_TYPE_REMEMBER_ME = 2;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
    }

    public static function getSearchObject()
    {
        return new SearchBase(static::DB_TBL, 'alh');
    }

    public static function getLoginTypes(): array
    {
        $langId = CommonHelper::getLangId();
        return [
            self::LOGIN_TYPE_PASSWORD => Labels::getLabel('LBL_PASSWORD_LOGIN', $langId),
            self::LOGIN_TYPE_REMEMBER_ME => Labels::getLabel('LBL_REMEMBER_ME_LOGIN', $langId),
        ];
    }

    /**
     * Log a successful admin login.
     *
     * @param array $adminRow admin fields (admin_id, admin_username, admin_name, admin_email)
     * @param string $ip
     * @param int $loginType
     * @return bool
     */
    public static function logLogin(array $adminRow, string $ip = '', int $loginType = self::LOGIN_TYPE_PASSWORD): bool
    {
        if (empty($adminRow['admin_id'])) {
            return false;
        }

        if ($ip === '') {
            $ip = CommonHelper::getClientIp();
        }

        $userAgent = CommonHelper::userAgent();
        $parsed = self::parseUserAgent($userAgent);

        $data = [
            'alh_admin_id' => FatUtility::int($adminRow['admin_id']),
            'alh_admin_username' => $adminRow['admin_username'] ?? '',
            'alh_admin_name' => $adminRow['admin_name'] ?? '',
            'alh_admin_email' => $adminRow['admin_email'] ?? '',
            'alh_ip' => substr($ip, 0, 45),
            'alh_user_agent' => substr($userAgent, 0, 500),
            'alh_browser' => substr($parsed['browser'], 0, 100),
            'alh_platform' => substr($parsed['platform'], 0, 100),
            'alh_device' => substr($parsed['device'], 0, 50),
            'alh_login_type' => $loginType,
            'alh_session_id' => session_id() ?: '',
            'alh_referer' => substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
            'alh_logged_at' => date('Y-m-d H:i:s'),
            'alh_logout_at' => null,
        ];

        $obj = new self();
        $obj->assignValues($data);
        return $obj->save();
    }

    /**
     * Mark logout time on the latest open session for this admin.
     */
    public static function logLogout(int $adminId = 0): bool
    {
        $adminId = FatUtility::int($adminId);
        if ($adminId < 1) {
            return false;
        }

        $db = FatApp::getDb();
        $sessionId = session_id() ?: '';

        $srch = self::getSearchObject();
        $srch->addCondition('alh_admin_id', '=', $adminId);
        $srch->addCondition('alh_logout_at', 'is', 'mysql_func_NULL', 'AND', true);
        if ($sessionId !== '') {
            $srch->addCondition('alh_session_id', '=', $sessionId);
        }
        $srch->addOrder('alh_id', 'DESC');
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $row = $db->fetch($srch->getResultSet());

        if (empty($row['alh_id'])) {
            return false;
        }

        return $db->updateFromArray(
            self::DB_TBL,
            ['alh_logout_at' => date('Y-m-d H:i:s')],
            ['smt' => 'alh_id = ?', 'vals' => [$row['alh_id']]]
        );
    }

    /**
     * Basic user-agent parsing for browser, platform and device.
     */
    public static function parseUserAgent(string $ua): array
    {
        $uaLower = strtolower($ua);
        $browser = 'Unknown';
        $platform = 'Unknown';
        $device = 'Desktop';

        if (preg_match('/edg\/([0-9\.]+)/i', $ua, $m)) {
            $browser = 'Edge ' . $m[1];
        } elseif (preg_match('/opr\/([0-9\.]+)/i', $ua, $m) || preg_match('/opera\/([0-9\.]+)/i', $ua, $m)) {
            $browser = 'Opera ' . $m[1];
        } elseif (preg_match('/chrome\/([0-9\.]+)/i', $ua, $m) && !preg_match('/edg/i', $ua)) {
            $browser = 'Chrome ' . $m[1];
        } elseif (preg_match('/firefox\/([0-9\.]+)/i', $ua, $m)) {
            $browser = 'Firefox ' . $m[1];
        } elseif (preg_match('/safari\/([0-9\.]+)/i', $ua, $m) && preg_match('/version\/([0-9\.]+)/i', $ua, $v)) {
            $browser = 'Safari ' . $v[1];
        } elseif (preg_match('/msie\s([0-9\.]+)/i', $ua, $m) || preg_match('/trident.*rv:([0-9\.]+)/i', $ua, $m)) {
            $browser = 'IE ' . $m[1];
        }

        if (stripos($ua, 'Windows') !== false) {
            $platform = 'Windows';
        } elseif (stripos($ua, 'Android') !== false) {
            $platform = 'Android';
        } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false || stripos($ua, 'iPod') !== false) {
            $platform = 'iOS';
        } elseif (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) {
            $platform = 'macOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $platform = 'Linux';
        }

        if (preg_match('/ipad|tablet|playbook|silk|(android(?!.*mobile))/i', $uaLower)) {
            $device = 'Tablet';
        } elseif (preg_match('/mobile|iphone|ipod|android.*mobile|blackberry|opera mini|iemobile/i', $uaLower)) {
            $device = 'Mobile';
        }

        return [
            'browser' => $browser,
            'platform' => $platform,
            'device' => $device,
        ];
    }
}
