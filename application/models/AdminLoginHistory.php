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
        $ip = self::normalizeIp($ip);

        $userAgent = CommonHelper::userAgent();
        $parsed = self::parseUserAgent($userAgent);
        $location = self::resolveLocationByIp($ip);

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
            'alh_country' => substr($location['country'], 0, 100),
            'alh_country_code' => substr($location['country_code'], 0, 10),
            'alh_region' => substr($location['region'], 0, 100),
            'alh_city' => substr($location['city'], 0, 100),
            'alh_zip' => substr($location['zip'], 0, 20),
            'alh_latitude' => substr($location['latitude'], 0, 30),
            'alh_longitude' => substr($location['longitude'], 0, 30),
            'alh_timezone' => substr($location['timezone'], 0, 60),
            'alh_isp' => substr($location['isp'], 0, 150),
            'alh_location' => substr($location['location'], 0, 255),
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
     * Resolve approximate location from IP via free geo lookup.
     */
    public static function resolveLocationByIp(string $ip): array
    {
        $empty = [
            'country' => '',
            'country_code' => '',
            'region' => '',
            'city' => '',
            'zip' => '',
            'latitude' => '',
            'longitude' => '',
            'timezone' => '',
            'isp' => '',
            'location' => '',
        ];

        $ip = self::normalizeIp($ip);
        if ($ip === '' || $ip === 'UNKNOWN' || self::isPrivateOrLocalIp($ip)) {
            $empty['location'] = Labels::getLabel('LBL_LOCAL_NETWORK', CommonHelper::getLangId());
            return $empty;
        }

        $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,message,country,countryCode,regionName,city,zip,lat,lon,timezone,isp,query';
        $response = self::httpGet($url);
        if ($response === '') {
            return $empty;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return $empty;
        }

        $city = trim((string) ($data['city'] ?? ''));
        $region = trim((string) ($data['regionName'] ?? ''));
        $country = trim((string) ($data['country'] ?? ''));
        $parts = array_filter([$city, $region, $country]);

        return [
            'country' => $country,
            'country_code' => trim((string) ($data['countryCode'] ?? '')),
            'region' => $region,
            'city' => $city,
            'zip' => trim((string) ($data['zip'] ?? '')),
            'latitude' => isset($data['lat']) ? (string) $data['lat'] : '',
            'longitude' => isset($data['lon']) ? (string) $data['lon'] : '',
            'timezone' => trim((string) ($data['timezone'] ?? '')),
            'isp' => trim((string) ($data['isp'] ?? '')),
            'location' => implode(', ', $parts),
        ];
    }

    public static function normalizeIp(string $ip): string
    {
        $ip = trim(explode(',', $ip)[0]);
        return $ip;
    }

    public static function isPrivateOrLocalIp(string $ip): bool
    {
        if ($ip === '::1' || $ip === '127.0.0.1' || strcasecmp($ip, 'localhost') === 0) {
            return true;
        }
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    protected static function httpGet(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'AdminLoginHistory/1.0',
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
                return (string) $response;
            }
            return '';
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'header' => "User-Agent: AdminLoginHistory/1.0\r\n",
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        return $response === false ? '' : (string) $response;
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
