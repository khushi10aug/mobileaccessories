<?php

class AdminLoginHistory extends MyAppModel
{
    public const DB_TBL = 'tbl_admin_login_history';
    public const DB_TBL_PREFIX = 'alh_';
    public const LOGIN_HISTORY_COOKIE_NAME = 'yokartAdmin_login_history';

    public const LOGIN_TYPE_PASSWORD = 1;
    public const LOGIN_TYPE_REMEMBER_ME = 2;

    public function __construct($id = 0)
    {
        parent::__construct(static::DB_TBL, static::DB_TBL_PREFIX . 'id', $id);
    }

    /**
     * Table has no *_updated_on column; parent save() would fatal on unknown column.
     */
    public function updateModifiedTime()
    {
        return true;
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
     * Calculate time spent between login and logout (or until now if still active).
     *
     * @return array{seconds:int,hours:float,label:string,is_active:bool}
     */
    public static function getSessionDuration(array $row, int $langId = 0): array
    {
        $langId = $langId > 0 ? $langId : CommonHelper::getLangId();
        $start = !empty($row['alh_logged_at']) ? strtotime($row['alh_logged_at']) : 0;
        if ($start < 1) {
            return [
                'seconds' => 0,
                'hours' => 0,
                'label' => '-',
                'is_active' => false,
            ];
        }

        $isActive = empty($row['alh_logout_at']);
        if ($isActive) {
            $end = !empty($row['alh_last_activity']) ? strtotime($row['alh_last_activity']) : time();
        } else {
            $end = strtotime($row['alh_logout_at']);
        }

        if ($end < $start) {
            $end = $start;
        }

        $seconds = (int) ($end - $start);
        $hours = round($seconds / 3600, 2);
        $days = intdiv($seconds, 86400);
        $rem = $seconds % 86400;
        $hrs = intdiv($rem, 3600);
        $mins = intdiv($rem % 3600, 60);
        $secs = $rem % 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hrs > 0 || $days > 0) {
            $parts[] = $hrs . 'h';
        }
        if ($mins > 0 || ($days === 0 && $hrs === 0)) {
            $parts[] = $mins . 'm';
        }
        if ($days === 0 && $hrs === 0 && $mins === 0) {
            $parts[] = $secs . 's';
        }

        $label = implode(' ', $parts);
        if ($hours > 0) {
            $label .= ' (' . $hours . ' ' . Labels::getLabel('LBL_HRS', $langId) . ')';
        }
        if ($isActive) {
            $label .= ' - ' . Labels::getLabel('LBL_STILL_ACTIVE', $langId);
        }

        return [
            'seconds' => $seconds,
            'hours' => $hours,
            'label' => $label,
            'is_active' => $isActive,
        ];
    }

    public static function formatSessionDuration(array $row, int $langId = 0): string
    {
        return self::getSessionDuration($row, $langId)['label'];
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

        // Close any previous open history (e.g. expired session before remember-me re-login).
        self::closeOpenSessionFromCookie();

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
            'alh_last_activity' => date('Y-m-d H:i:s'),
            'alh_logout_at' => null,
        ];

        $db = FatApp::getDb();
        // Use direct insert — MyAppModel::save() also tries updating *_updated_on (not in this table).
        if (!$db->insertFromArray(self::DB_TBL, $data)) {
            return false;
        }

        $alhId = FatUtility::int($db->getInsertId());
        if ($alhId > 0) {
            self::setLoginHistoryCookie($alhId);
            if (!empty($_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]) && is_array($_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME])) {
                $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['alh_id'] = $alhId;
            }
        }
        return true;
    }

    /**
     * Mark logout time on the latest open session for this admin.
     */
    public static function logLogout(int $adminId = 0): bool
    {
        $adminId = FatUtility::int($adminId);
        $alhId = self::getActiveHistoryIdFromSessionOrCookie();

        if ($alhId > 0) {
            return self::closeHistoryById($alhId);
        }

        if ($adminId < 1) {
            return self::closeOpenSessionFromCookie();
        }

        $db = FatApp::getDb();
        $sessionId = session_id() ?: '';

        $srch = self::getSearchObject();
        $srch->addCondition('alh_admin_id', '=', $adminId);
        $srch->addDirectCondition('alh_logout_at IS NULL');
        if ($sessionId !== '') {
            $srch->addCondition('alh_session_id', '=', $sessionId);
        }
        $srch->addOrder('alh_id', 'DESC');
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $row = $db->fetch($srch->getResultSet());

        if (empty($row['alh_id'])) {
            return self::closeOpenSessionFromCookie();
        }

        return self::closeHistoryById((int) $row['alh_id'], $row);
    }

    /**
     * Idle timeout in seconds (aligned with PHP session lifetime).
     */
    public static function getSessionIdleTimeout(): int
    {
        $timeout = FatUtility::int(ini_get('session.gc_maxlifetime'));
        return $timeout > 0 ? $timeout : 1440;
    }

    /**
     * True when admin session has been idle longer than allowed.
     */
    public static function isSessionIdleExpired(): bool
    {
        $session = $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME] ?? null;
        if (!is_array($session)) {
            return false;
        }
        $lastActivity = FatUtility::int($session['admin_last_activity'] ?? 0);
        if ($lastActivity < 1) {
            return false;
        }
        return (time() - $lastActivity) > self::getSessionIdleTimeout();
    }

    /**
     * Close open login history when PHP/admin session has expired (cookie still present).
     */
    public static function closeOpenSessionFromCookie(): bool
    {
        $alhId = self::getLoginHistoryIdFromCookie();
        if ($alhId < 1) {
            return false;
        }
        return self::closeHistoryById($alhId);
    }

    /**
     * Close abandoned open sessions whose last activity is older than idle timeout.
     * Works even when browser cookie/session is already gone.
     */
    public static function closeExpiredOpenSessions(): bool
    {
        $timeout = self::getSessionIdleTimeout();
        $cutoff = date('Y-m-d H:i:s', time() - $timeout);

        $sql = 'UPDATE `' . self::DB_TBL . '`
            SET `alh_logout_at` = COALESCE(`alh_last_activity`, `alh_logged_at`)
            WHERE `alh_logout_at` IS NULL
              AND COALESCE(`alh_last_activity`, `alh_logged_at`) < '
            . FatApp::getDb()->quoteVariable($cutoff);

        return (bool) FatApp::getDb()->query($sql);
    }

    /**
     * Run all auto-logout history closers (cookie + stale open rows).
     */
    public static function handleAutoSessionExpiry(): void
    {
        self::closeOpenSessionFromCookie();
        self::closeExpiredOpenSessions();
    }

    /**
     * Update last activity while admin is still logged in.
     */
    public static function touchLastActivity(): void
    {
        $session = $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME] ?? null;
        if (!is_array($session)) {
            return;
        }

        // Always refresh in-session activity marker (used for idle timeout).
        $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['admin_last_activity'] = time();

        $alhId = self::getActiveHistoryIdFromSessionOrCookie();
        if ($alhId < 1) {
            // Recover history id from latest open row for this admin.
            $alhId = self::getOpenHistoryIdForAdmin(FatUtility::int($session['admin_id'] ?? 0));
            if ($alhId > 0) {
                $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['alh_id'] = $alhId;
                self::setLoginHistoryCookie($alhId);
            }
        }

        if ($alhId < 1) {
            return;
        }

        // Throttle DB writes to once per minute.
        $lastTouch = FatUtility::int($session['alh_last_touch'] ?? 0);
        if ($lastTouch > 0 && (time() - $lastTouch) < 60) {
            return;
        }

        FatApp::getDb()->updateFromArray(
            self::DB_TBL,
            ['alh_last_activity' => date('Y-m-d H:i:s')],
            [
                'smt' => 'alh_id = ? AND alh_logout_at IS NULL',
                'vals' => [$alhId],
            ]
        );

        $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['alh_last_touch'] = time();
        $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['alh_id'] = $alhId;
    }

    public static function getOpenHistoryIdForAdmin(int $adminId): int
    {
        $adminId = FatUtility::int($adminId);
        if ($adminId < 1) {
            return 0;
        }

        $db = FatApp::getDb();
        $srch = self::getSearchObject();
        $srch->addCondition('alh_admin_id', '=', $adminId);
        $srch->addDirectCondition('alh_logout_at IS NULL');
        $srch->addOrder('alh_id', 'DESC');
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        $row = $db->fetch($srch->getResultSet());

        return FatUtility::int($row['alh_id'] ?? 0);
    }

    public static function closeHistoryById(int $alhId, array $row = []): bool
    {
        $alhId = FatUtility::int($alhId);
        if ($alhId < 1) {
            return false;
        }

        $db = FatApp::getDb();
        if (empty($row) || empty($row['alh_id'])) {
            $srch = self::getSearchObject();
            $srch->addCondition('alh_id', '=', $alhId);
            $srch->addDirectCondition('alh_logout_at IS NULL');
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            $row = $db->fetch($srch->getResultSet()) ?: [];
        }

        if (empty($row['alh_id'])) {
            self::clearLoginHistoryCookie();
            return false;
        }

        // Prefer last activity so expired sessions show when the user was last active.
        $logoutAt = !empty($row['alh_last_activity']) ? $row['alh_last_activity'] : date('Y-m-d H:i:s');

        $updated = $db->updateFromArray(
            self::DB_TBL,
            ['alh_logout_at' => $logoutAt],
            ['smt' => 'alh_id = ? AND alh_logout_at IS NULL', 'vals' => [$alhId]]
        );

        self::clearLoginHistoryCookie();
        if (!empty($_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]) && is_array($_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME])) {
            unset(
                $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['alh_id'],
                $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['alh_last_touch']
            );
        }

        return (bool) $updated;
    }

    public static function getActiveHistoryIdFromSessionOrCookie(): int
    {
        $session = $_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME] ?? null;
        if (is_array($session)) {
            $alhId = FatUtility::int($session['alh_id'] ?? 0);
            if ($alhId > 0) {
                return $alhId;
            }
        }
        return self::getLoginHistoryIdFromCookie();
    }

    public static function getLoginHistoryIdFromCookie(): int
    {
        return FatUtility::int($_COOKIE[self::LOGIN_HISTORY_COOKIE_NAME] ?? 0);
    }

    public static function setLoginHistoryCookie(int $alhId): void
    {
        $alhId = FatUtility::int($alhId);
        if ($alhId < 1) {
            return;
        }

        $expires = time() + (86400 * 30);
        $value = (string) $alhId;
        // Root path so cookie is available on /admin even after PHP session dies.
        @setcookie(self::LOGIN_HISTORY_COOKIE_NAME, $value, $expires, '/');
        if (defined('CONF_WEBROOT_URL') && CONF_WEBROOT_URL !== '/') {
            @setcookie(self::LOGIN_HISTORY_COOKIE_NAME, $value, $expires, CONF_WEBROOT_URL);
        }
        $_COOKIE[self::LOGIN_HISTORY_COOKIE_NAME] = $value;
    }

    public static function clearLoginHistoryCookie(): void
    {
        @setcookie(self::LOGIN_HISTORY_COOKIE_NAME, '', time() - 3600, '/');
        if (defined('CONF_WEBROOT_URL') && CONF_WEBROOT_URL !== '/') {
            @setcookie(self::LOGIN_HISTORY_COOKIE_NAME, '', time() - 3600, CONF_WEBROOT_URL);
        }
        unset($_COOKIE[self::LOGIN_HISTORY_COOKIE_NAME]);
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
            $empty['location'] = 'Local / Private Network';
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
