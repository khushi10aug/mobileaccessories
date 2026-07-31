<?php
class CustomRouter
{
    public static function setRoute(&$controller, &$action, &$queryString)
    {
        $langId = CommonHelper::getLangId();
        define('LANG_CODES_ARR', Language::getAllCodesAssoc());

        if ('app-api' == $controller) {
            self::setAPIRoute($controller, $action, $queryString);
        } else {
            define('MOBILE_APP_API_CALL', false);
            define('MOBILE_APP_API_VERSION', '');
            define('MOBILE_APP_USER_TYPE', null);

            if (in_array(CONF_WEBROOT_URL, [CONF_WEBROOT_DASHBOARD, CONF_WEBROOT_BACKEND])) {
                define('SYSTEM_LANG_ID', $langId);
                return;
            }

            /* [ Handled lang code in url */
            if (FatApp::getConfig('CONF_LANG_SPECIFIC_URL', FatUtility::VAR_INT, 0) && in_array(strtoupper($controller), LANG_CODES_ARR)) {
                // $langId = FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1);
                $langCodes = array_flip(LANG_CODES_ARR);
                $langId = $langCodes[strtoupper($controller)];
                $langId = ($langId > 0) ? $langId : CommonHelper::getLangId();
                setcookie('defaultSiteLang', $langId, time() + 3600 * 24 * 10, CONF_WEBROOT_FRONTEND);

                $controller = ($action == 'index') ? 'Home' : $action;
                if (!array_key_exists(0, $queryString)) {
                    $action = 'index';
                } else {
                    $action = $queryString[0];
                    array_shift($queryString);
                }
            }
            /* ] */
        }
        define('SYSTEM_LANG_ID', $langId);

        /* Handled CDN url for static contents and 404 for other requests. Specially when mapped on same root directory[*/
        if (CDN_DOMAIN_URL != '' && (strpos(CDN_DOMAIN_URL, $_SERVER['SERVER_NAME']) !== false)) {
            if (!UrlHelper::staticContentProvider($controller, $action)) {
                $action = 'error404';
                return;
            }
        }
        /* ]*/

        if (defined('SYSTEM_FRONT') && SYSTEM_FRONT === true/*  && !FatUtility::isAjaxCall() */) {
            $url = urldecode($_SERVER['REQUEST_URI']);
            $reqPath = parse_url($url, PHP_URL_PATH);
            if ($reqPath !== null && preg_match('#^/cdn-cgi/#i', $reqPath)) {
                return;
            }

            if (strpos($url, "index.php?url=") !== false || UrlHelper::staticContentProvider($controller, $action) == true) {
                return;
            }

            if (strpos($url, "?") !== false && strpos($url, "/?") === false) {
                $url = str_replace('?', '/?', $url);
            }

            $customUrl = substr($url, strlen(CONF_WEBROOT_URL));
            $customUrl = rtrim($customUrl, '/');
            $customUrl = explode('/?', $customUrl);

            /* [ Strip leading "xx/rest" when xx is a real language code — must not depend on CONF_LANG_SPECIFIC_URL
             * (live can have the setting off while Google still has /ar/… /es/… URLs; else slug keeps "/" and never matches SEO rewrite rules). */
            $langParts = explode('/', $customUrl[0], 2);
            if (isset($langParts[1]) && $langParts[0] !== '' && in_array(strtoupper($langParts[0]), LANG_CODES_ARR, true)) {
                $customUrl[0] = $langParts[1];
            }
            /* ] */

            /* [ Check url rewritten by the system or system url with query parameter*/
            $row = false;
            if (!empty($customUrl[0])) {
                $requestSlug = rawurldecode($customUrl[0]);
                $customUrl[0] = $requestSlug;

                $srch = UrlRewrite::getSearchObject();
                $srch->doNotCalculateRecords();
                $srch->addMultipleFields(array('urlrewrite_custom', 'urlrewrite_original'));
                $srch->setPageSize(1);
                $srch->addCondition(UrlRewrite::DB_TBL_PREFIX . 'custom', '=', $requestSlug);
                //$srch->addCondition(UrlRewrite::DB_TBL_PREFIX . 'lang_id', '=', SYSTEM_LANG_ID);
                $rs = $srch->getResultSet();
                $row = FatApp::getDb()->fetch($rs);

                if (!$row) {
                    $row = self::resolveBuiltInCustomSlug($requestSlug);
                }

                /* Malformed indexed URLs (–, /, |, &ndash;): normalize and 301 to clean slug when it exists. */
                if (
                    !$row
                    && FatApp::getConfig('CONF_ENABLE_301', FatUtility::VAR_INT, 1)
                    && !FatUtility::isAjaxCall()
                    && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)
                ) {
                    $normalizedSlug = CommonHelper::seoUrl($requestSlug);
                    if (
                        $normalizedSlug !== ''
                        && strcasecmp($normalizedSlug, $requestSlug) !== 0
                    ) {
                        $srch = UrlRewrite::getSearchObject();
                        $srch->doNotCalculateRecords();
                        $srch->addMultipleFields(array('urlrewrite_custom', 'urlrewrite_original'));
                        $srch->setPageSize(1);
                        $srch->addCondition(UrlRewrite::DB_TBL_PREFIX . 'custom', '=', $normalizedSlug);
                        $normRow = FatApp::getDb()->fetch($srch->getResultSet());
                        if (!empty($normRow) && $normRow['urlrewrite_custom'] != '') {
                            $redirectQueryString = (isset($customUrl[1]) && $customUrl[1] != '') ? '?' . $customUrl[1] : '';
                            header('HTTP/1.1 301 Moved Permanently');
                            header('Location: ' . UrlHelper::generateFullUrl('', '', [], CONF_WEBROOT_URL) . $normRow['urlrewrite_custom'] . $redirectQueryString);
                            header('Connection: close');
                            exit;
                        }
                    }
                }

                if (!$row && FatApp::getConfig('CONF_ENABLE_301', FatUtility::VAR_INT, 1) && !FatUtility::isAjaxCall()) {
                    $srch = UrlRewrite::getSearchObject();
                    $srch->doNotCalculateRecords();
                    $srch->addMultipleFields(array('urlrewrite_custom', 'urlrewrite_original'));
                    $srch->setPageSize(1);
                    $srch->addCondition(UrlRewrite::DB_TBL_PREFIX . 'original', '=', $requestSlug);
                    $rs = $srch->getResultSet();
                    $res = FatApp::getDb()->fetch($rs);
                    if (!empty($res) && $res['urlrewrite_custom'] != '') {
                        $redirectQueryString = (isset($customUrl[1]) && $customUrl[1] != '') ?  '?' . $customUrl[1] : '';
                        header("HTTP/1.1 301 Moved Permanently");
                        header("Location: " . UrlHelper::generateFullUrl('', '', [], CONF_WEBROOT_URL) . $res['urlrewrite_custom'] . $redirectQueryString);
                        header("Connection: close");
                        exit;
                    }
                }
            }

            /* Empty path after webroot = homepage; do not fall through (empty $url would map to Content/error404). */
            if ($customUrl[0] === '' || $customUrl[0] === null) {
                return;
            }

            if (!$row && (!isset($customUrl[1]) || (isset($customUrl[1]) && strpos($customUrl[1], 'pagesize') === false))) {
                $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
                if (
                    !empty($customUrl[0])
                    && in_array($method, ['GET', 'HEAD'], true)
                    && !FatUtility::isAjaxCall()
                ) {
                    $slug = $customUrl[0];
                    if (strpos($slug, '..') !== false) {
                        return;
                    }
                    /* Short slug: shop/brand-style. Long slug: product SEO URLs that include "/" (e.g. a1990-/-a1707-...).
                     * Multi-segment: e.g. reviews/product/24999/154 when no url_rewrite row (each segment = slug or digits).
                     * Missing rewrite → hard 404 so Google drops ghost URLs (do not 301 to homepage). */
                    $shortSeo = (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*$/', $slug);
                    $longProductSeo = strlen($slug) >= 48 && (bool) preg_match('#^[a-zA-Z0-9][a-zA-Z0-9\-/]*$#', $slug);
                    $multiSegSeo = self::isCatalogSeoMultiSegmentRedirectCandidate($slug) && !self::isDirectFrontendRoutePath($slug);
                    $seoSlugCandidate = ($shortSeo && !self::isDirectFrontendRoutePath($slug) && !self::isBuiltInSystemCustomSlug($slug)) || $longProductSeo || $multiSegSeo;
                    if ($seoSlugCandidate) {
                        FatUtility::exitWithErrorCode(404);
                    }
                }
                /* Unknown slug without rewrite: let framework keep parsed route, or fall through to map direct MVC paths below. */
                if (!empty($customUrl[0]) && !self::isDirectFrontendRoutePath($customUrl[0])) {
                    return;
                }
            }
            /*]*/

            $url = (!empty($row['urlrewrite_original'])) ? $row['urlrewrite_original'] : '';
            if (!$row && isset($customUrl[1])) {
                $url = $customUrl[0];
            } elseif (!$row && !empty($customUrl[0]) && self::isDirectFrontendRoutePath($customUrl[0])) {
                $url = $customUrl[0];
            }

            $arr = explode('/', $url);

            $controller = (isset($arr[0])) ? $arr[0] : '';
            array_shift($arr);

            $action = (isset($arr[0])) ? $arr[0] : '';
            array_shift($arr);

            $queryString = $arr;

            /* [ used in case of filters when passed through url*/
            if (isset($customUrl[1]) && !empty($customUrl[1])) {
                $customUrl = explode('&', $customUrl[1]);
                $queryString = array_merge($queryString, $customUrl);
            }
            /* ]*/

            if ($controller != '' && $action == '') {
                $action = 'index';
            }

            if ($controller == '') {
                $controller = 'Content';
            }

            if ($action == '') {
                $action = 'error404';
            }
        }
    }

    public static function setAPIRoute(&$controller, &$action, &$queryString)
    {
        define('MOBILE_APP_API_CALL', true);
        define('MOBILE_APP_API_VERSION', str_replace('v', '', $action));
        $userType = null;

        if (!array_key_exists(0, $queryString)) {
            $arr = array('status' => -1, 'msg' => "Invalid Request");
            die(json_encode($arr));
        }

        $controller = $queryString[0];
        array_shift($queryString);

        if (!array_key_exists(0, $queryString)) {
            $queryString[0] = '';
        }

        $action = $queryString[0];
        if ($controller != '' && $action == '') {
            $action = 'index';
        }

        array_shift($queryString);

        if (array_key_exists('HTTP_X_USER_TYPE', $_SERVER)) {
            $userType = intval($_SERVER['HTTP_X_USER_TYPE']);
        }
        define('MOBILE_APP_USER_TYPE', $userType);
    }

    /**
     * True when the path should be routed as a normal controller/action URL (not a bare SEO slug for 301-to-home).
     * First segment is lowercased and matched (same idea as blocked first segments for multi-segment SEO redirects).
     */
    private static function isDirectFrontendRoutePath($slug)
    {
        if ($slug === '' || strpos($slug, '..') !== false) {
            return false;
        }
        $first = strtolower(explode('/', $slug, 2)[0]);
        static $reserved = null;
        if ($reserved === null) {
            $reserved = [
                'admin', 'dashboard', 'seller', 'buyer', 'cart', 'checkout', 'wallet', 'image', 'cache', 'install',
                'public', 'scripts', 'app-api', 'user-uploads', 'products', 'shops', 'brands', 'category', 'cms',
                'content', 'blog', 'banner', 'payment', 'order', 'orders', 'account', 'supplier', 'gift', 'rfq',
                'notifications', 'payment-status', 'invoice', 'download', 'embed', 'oauth', 'cron', 'cronjob',
                'reviews', 'navigation', 'common', 'guest-user', 'custom', 'wallet-pay', 'error', 'instagram-login',
                'collections',
            ];
        }

        return in_array($first, $reserved, true);
    }

    /**
     * True for paths like reviews/product/123/456 or any multi-segment slug-only URL that is not a reserved app prefix.
     */
    private static function isCatalogSeoMultiSegmentRedirectCandidate($slug)
    {
        if ($slug === '' || strpos($slug, '/') === false || strpos($slug, '..') !== false) {
            return false;
        }
        if (strlen($slug) > 220) {
            return false;
        }
        static $blockedFirst = null;
        if ($blockedFirst === null) {
            $blockedFirst = [
                'admin', 'dashboard', 'seller', 'buyer', 'cart', 'checkout', 'wallet', 'image', 'cache', 'install',
                'public', 'scripts', 'app-api', 'user-uploads', 'products', 'shops', 'brands', 'category', 'cms',
                'content', 'blog', 'banner', 'payment', 'order', 'orders', 'account', 'supplier', 'gift', 'rfq',
                'notifications', 'payment-status', 'invoice', 'download', 'embed', 'oauth', 'cron', 'cronjob',
                'custom', 'guest-user', 'wallet-pay', 'reviews', 'navigation', 'common', 'error',
                'collections',
            ];
        }
        $parts = explode('/', strtolower($slug));
        $first = $parts[0] ?? '';
        if ($first === '' || in_array($first, $blockedFirst, true)) {
            return false;
        }
        foreach ($parts as $p) {
            if ($p === '' || !preg_match('#^([a-z0-9][a-z0-9\-]*|\d+)$#', $p)) {
                return false;
            }
        }

        return count($parts) >= 2;
    }

    /**
     * Built-in SEO slugs shipped with Yo!Kart when tbl_url_rewrite rows are missing.
     */
    private static function getBuiltInCustomSlugMap()
    {
        return [
            'faqs' => 'custom/faq',
            'faq' => 'custom/faq',
            'contact-us' => 'custom/contact-us',
            'seller' => 'supplier',
        ];
    }

    private static function resolveBuiltInCustomSlug($customSlug)
    {
        $map = self::getBuiltInCustomSlugMap();
        $key = strtolower(trim((string) $customSlug));
        if ($key === '') {
            return false;
        }

        if (isset($map[$key])) {
            return [
                'urlrewrite_custom' => $key,
                'urlrewrite_original' => $map[$key],
            ];
        }

        /* Direct system route fallback when tbl_url_rewrite rows are missing (e.g. guest-user/forgot-password-form). */
        if (self::isDirectFrontendRoutePath($key)) {
            return [
                'urlrewrite_custom' => $key,
                'urlrewrite_original' => $key,
            ];
        }

        return false;
    }

    /**
     * Single-segment system URLs (faqs, contact-us) must not 301 to homepage.
     */
    private static function isBuiltInSystemCustomSlug($slug)
    {
        if ($slug === '' || strpos($slug, '/') !== false) {
            return false;
        }

        return isset(self::getBuiltInCustomSlugMap()[strtolower($slug)]);
    }
}