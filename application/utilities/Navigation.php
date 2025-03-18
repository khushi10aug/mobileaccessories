<?php

class Navigation
{
    private static $navigationData = [];
    public static function headerTopNavigation($template)
    {
        $siteLangId = CommonHelper::getLangId();

        $headerTopNavigation = CacheHelper::get('headerTopNavigations_' . $siteLangId, CONF_HOME_PAGE_CACHE_TIME, '.txt');

        if ($headerTopNavigation) {
            $headerTopNavigation = unserialize($headerTopNavigation);
        } else {
            if (!array_key_exists(Navigations::NAVTYPE_TOP_HEADER, self::$navigationData)) {
                self::$navigationData[Navigations::NAVTYPE_TOP_HEADER] = self::getNavigation(Navigations::NAVTYPE_TOP_HEADER);
            }
            $headerTopNavigation = self::$navigationData[Navigations::NAVTYPE_TOP_HEADER];
            CacheHelper::create('headerTopNavigations_' . $siteLangId, serialize($headerTopNavigation), CacheHelper::TYPE_NAVIGATION);
        }
        $template->set('top_header_navigation', $headerTopNavigation);
    }

    public static function headerNavigation($template)
    {
        $siteLangId = CommonHelper::getLangId();
        $template->set('siteLangId', $siteLangId);

        $layout = FatApp::getConfig('CONF_LAYOUT_MEGA_MENU', FatUtility::VAR_INT, 1);
        $includeCategories = ($layout == Navigations::LAYOUT_MEGA_MENU) ? false : true;
        
        $headerNavigation = CacheHelper::get('headerNavigation_' . $siteLangId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if ($headerNavigation) {
            $headerNavigation = unserialize($headerNavigation);
        } else {
            $headerNavigation = self::getNavigation(Navigations::NAVTYPE_HEADER, $includeCategories);
            CacheHelper::create('headerNavigation_' . $siteLangId, serialize($headerNavigation), CacheHelper::TYPE_NAVIGATION);
        }

        $isUserLogged = UserAuthentication::isUserLogged();
        if ($isUserLogged) {
            $template->set('userName', ucfirst(CommonHelper::getUserFirstName(UserAuthentication::getLoggedUserAttribute('user_name'))));
        }

        $headerTopNavigation = CacheHelper::get('headerTopNavigations_' . $siteLangId, CONF_HOME_PAGE_CACHE_TIME, CacheHelper::TYPE_NAVIGATION);

        if ($headerTopNavigation) {
            $headerTopNavigation = unserialize($headerTopNavigation);
        } else {
            if (!array_key_exists(Navigations::NAVTYPE_TOP_HEADER, self::$navigationData)) {
                self::$navigationData[Navigations::NAVTYPE_TOP_HEADER] = self::getNavigation(Navigations::NAVTYPE_TOP_HEADER);
            }
            $headerTopNavigation = self::$navigationData[Navigations::NAVTYPE_TOP_HEADER];
            CacheHelper::create('headerTopNavigations_' . $siteLangId, serialize($headerTopNavigation), CacheHelper::TYPE_NAVIGATION);
        }
        /* $headerCategories = [];
        if ($layout == Navigations::LAYOUT_MEGA_MENU) {
            $headerCategories = CacheHelper::get('headerCategories_' . $siteLangId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
            if ($headerCategories) {
                $headerCategories = unserialize($headerCategories);
            } else {
                $headerCategories = ProductCategory::getArray($siteLangId, 0, false, true, false, CONF_USE_FAT_CACHE);
                CacheHelper::create('headerCategories_' . $siteLangId, serialize($headerCategories), CacheHelper::TYPE_NAVIGATION);
            }
        }
        $template->set('headerCategories', $headerCategories); */
        $template->set('top_header_navigation', $headerTopNavigation);
        $template->set('isUserLogged', $isUserLogged);
        $template->set('headerNavigation', $headerNavigation);
    }

    public static function buyerDashboardNavigation($template)
    {
        $siteLangId = CommonHelper::getLangId();
        $controller = str_replace('Controller', '', FatApp::getController());
        $action = FatApp::getAction();
        $userId = UserAuthentication::getLoggedUserId();
        /* Unread Message Count [*/
        $threadObj = new Thread();
        $todayUnreadMessageCount = $threadObj->getMessageCount($userId, Thread::MESSAGE_IS_UNREAD, date('Y-m-d'));
        /*]*/
        $template->set('siteLangId', $siteLangId);
        $template->set('controller', $controller);
        $template->set('action', $action);
        $template->set('todayUnreadMessageCount', $todayUnreadMessageCount);
    }

    public static function topHeaderDashboard($template)
    {
        /* $userData = User::getAttributesById(UserAuthentication::getLoggedUserId());
        $userId = (0 < $userData['user_parent']) ? $userData['user_parent'] : UserAuthentication::getLoggedUserId(); */
        $userId = UserAuthentication::getLoggedUserId();
        /* Unread Message Count [*/
        $threadObj = new Thread();
        $todayUnreadMessageCount = $threadObj->getMessageCount($userId, Thread::MESSAGE_IS_UNREAD, date('Y-m-d'));
        /*]*/

        /*
        $shopDetails = Shop::getAttributesByUserId($userId, array('shop_id'), false);
        $shop_id = 0;
        if (!false == $shopDetails) {
            $shop_id = $shopDetails['shop_id'];
        }
        */

        $controllerName = substr(FatApp::getController(), 0, '-' . strlen('Controller'));
        $activeTab = 'B';
        $sellerActiveTabControllers = array('Seller');
        $buyerActiveTabControllers = array('Buyer');

        if (in_array($controllerName, $sellerActiveTabControllers)) {
            $activeTab = 'S';
        } elseif (in_array($controllerName, $buyerActiveTabControllers)) {
            $activeTab = 'B';
        } elseif (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'])) {
            $activeTab = $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'];
        }
        /*
        $shop = new Shop(0, $userId);
        $isShopActive = $shop->isActive();
        $template->set('shop_id', $shop_id);
        $template->set('isShopActive', $isShopActive);
        */
        $template->set('userPrivilege', UserPrivilege::getInstance());
        $template->set('activeTab', $activeTab);
        $template->set('controllerName', $controllerName);
        $template->set('todayUnreadMessageCount', $todayUnreadMessageCount);
    }

    public static function advertiserDashboardNavigation($template)
    {
        $siteLangId = CommonHelper::getLangId();
        $controller = str_replace('Controller', '', FatApp::getController());
        $action = FatApp::getAction();
        $userData = User::getAttributesById(UserAuthentication::getLoggedUserId());
        $userParentId = (0 < $userData['user_parent']) ? $userData['user_parent'] : UserAuthentication::getLoggedUserId();
        $template->set('userParentId', $userParentId);
        $template->set('userPrivilege', UserPrivilege::getInstance());
        $template->set('siteLangId', $siteLangId);
        $template->set('controller', $controller);
        $template->set('action', $action);
    }

    public static function sellerDashboardNavigation($template)
    {
        $siteLangId = CommonHelper::getLangId();
        $userData = User::getAttributesById(UserAuthentication::getLoggedUserId());
        $userId = (0 < $userData['user_parent']) ? $userData['user_parent'] : UserAuthentication::getLoggedUserId();
        /* Unread Message Count [*/
        $threadObj = new Thread();
        $todayUnreadMessageCount = $threadObj->getMessageCount(UserAuthentication::getLoggedUserId(), Thread::MESSAGE_IS_UNREAD, date('Y-m-d'));
        /*]*/
        $controller = str_replace('Controller', '', FatApp::getController());
        $action = FatApp::getAction();

        $shopDetails = Shop::getAttributesByUserId($userId, array('shop_id'), false);

        $shop_id = 0;
        if (!false == $shopDetails) {
            $shop_id = $shopDetails['shop_id'];
        }

        $shop = new Shop(0, $userId);
        $isShopActive = $shop->isActive();

        $template->set('userParentId', $userId);
        $template->set('userPrivilege', UserPrivilege::getInstance());
        $template->set('shop_id', $shop_id);
        $template->set('isShopActive', $isShopActive);
        $template->set('siteLangId', $siteLangId);
        $template->set('controller', $controller);
        $template->set('action', $action);
        $template->set('params', FatApp::getParameters());
        $template->set('todayUnreadMessageCount', $todayUnreadMessageCount);
    }

    public static function affiliateDashboardNavigation($template)
    {
        $siteLangId = CommonHelper::getLangId();
        $controller = str_replace('Controller', '', FatApp::getController());
        $action = FatApp::getAction();

        $template->set('siteLangId', $siteLangId);
        $template->set('controller', $controller);
        $template->set('action', $action);
    }

    public static function dashboardTop($template)
    {
        $siteLangId = CommonHelper::getLangId();
        $controller = str_replace('Controller', '', FatApp::getController());

        $activeTab = 'B';
        $sellerActiveTabControllers = array('Seller');
        $buyerActiveTabControllers = array('Buyer');

        if (in_array($controller, $sellerActiveTabControllers)) {
            $activeTab = 'S';
        } elseif (in_array($controller, $buyerActiveTabControllers)) {
            $activeTab = 'B';
        } elseif (isset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'])) {
            $activeTab = $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'];
        }

        $jsVariables = array(
            'confirmDelete' => Labels::getLabel('LBL_Do_you_want_to_delete', $siteLangId),
            'confirmDefault' => Labels::getLabel('LBL_Do_you_want_to_set_default', $siteLangId),
        );

        $template->set('jsVariables', $jsVariables);
        $template->set('siteLangId', $siteLangId);
        $template->set('activeTab', $activeTab);
    }

    public static function customPageLeft($template)
    {
        $siteLangId = CommonHelper::getLangId();
        $contentBlockUrlArr = array(Extrapage::CONTACT_US_CONTENT_BLOCK => UrlHelper::generateUrl('Custom', 'ContactUs'));

        $srch = Extrapage::getSearchObject($siteLangId);
        $srch->addCondition('epage_default', '=', 1);
        $srch->addMultipleFields(
            array('epage_id as id', 'epage_type as pageType', 'IFNULL(epage_label,epage_identifier) as pageTitle ')
        );

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $pagesArr = FatApp::getDb()->fetchAll($rs);

        $srch = ContentPage::getSearchObject($siteLangId);
        $srch->addCondition('cpagelang_cpage_id', 'is not', 'mysql_func_null', 'and', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $cpagesArr = FatApp::getDb()->fetchAll($rs);

        $template->set('pagesArr', $pagesArr);
        $template->set('cpagesArr', $cpagesArr);
        $template->set('contentBlockUrlArr', $contentBlockUrlArr);
        $template->set('siteLangId', $siteLangId);
    }

    public static function getNavigation($type = 0, $includeCategories = true)
    {
        $siteLangId = CommonHelper::getLangId();
        $headerNavCache = CacheHelper::get('headerNavCache' . $siteLangId . '-' . $type . '-' . ($includeCategories ? 1 : 0), CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if ($headerNavCache) {
            return  unserialize($headerNavCache);
        }

        if ($includeCategories) {
            /* Category have products[ */
            $rootCatArr = ProductCategory::getArray($siteLangId, 0, false, true, false, CONF_USE_FAT_CACHE);

            $categoriesMainRootArr = CacheHelper::get('navigationCatCache' . $siteLangId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
            if ($categoriesMainRootArr) {
                $categoriesMainRootArr = unserialize($categoriesMainRootArr);
            } else {
                $categoriesMainRootArr = array_keys($rootCatArr);
                CacheHelper::create('navigationCatCache' . $siteLangId, serialize($categoriesMainRootArr), CacheHelper::TYPE_NAVIGATION);
            }

            $catWithProductConditoon = '';
            if ($categoriesMainRootArr) {
                $catWithProductConditoon = " and nlink_category_id in(" . implode(",", $categoriesMainRootArr) . ")";
            }
            /* ] */
        }

        $srch = new NavigationLinkSearch($siteLangId);
        $srch->doNotCalculateRecords();
        if ($includeCategories) {
            $srch->joinProductCategory($siteLangId);
            $srch->addMultipleFields(array(
                'nav_id',
                'IFNULL( nav_name, nav_identifier ) as nav_name',
                'IFNULL( nlink_caption, nlink_identifier ) as nlink_caption',
                'nlink_type',
                'nlink_cpage_id',
                'nlink_category_id',
                'IFNULL( prodcat_active, ' . applicationConstants::ACTIVE . ' ) as filtered_prodcat_active',
                'IFNULL(prodcat_deleted, ' . applicationConstants::NO . ') as filtered_prodcat_deleted',
                'IFNULL( cpage_deleted, ' . applicationConstants::NO . ' ) as filtered_cpage_deleted',
                'nlink_target',
                'nlink_url',
                'nlink_login_protected'
            ));
            $srch->addDirectCondition("((nlink_type = " . NavigationLinks::NAVLINK_TYPE_CATEGORY_PAGE . " AND nlink_category_id > 0 $catWithProductConditoon ) OR (nlink_type = " . NavigationLinks::NAVLINK_TYPE_CMS . " AND nlink_cpage_id > 0 ) OR  ( nlink_type = " . NavigationLinks::NAVLINK_TYPE_EXTERNAL_PAGE . " ))");
            $srch->addHaving('filtered_prodcat_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);
            $srch->addHaving('filtered_prodcat_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
            $srch->doNotLimitRecords();
        } else {
            $srch->addDirectCondition("((nlink_type != " . NavigationLinks::NAVLINK_TYPE_CATEGORY_PAGE . "  ) OR (nlink_type = " . NavigationLinks::NAVLINK_TYPE_CMS . " AND nlink_cpage_id > 0 ) OR  ( nlink_type = " . NavigationLinks::NAVLINK_TYPE_EXTERNAL_PAGE . " ))");
            $srch->addMultipleFields(array(
                'nav_id',
                'IFNULL( nav_name, nav_identifier ) as nav_name',
                'IFNULL( nlink_caption, nlink_identifier ) as nlink_caption',
                'nlink_type',
                'nlink_cpage_id',
                'nlink_category_id',
                'IFNULL( cpage_deleted, ' . applicationConstants::NO . ' ) as filtered_cpage_deleted',
                'nlink_target',
                'nlink_url',
                'nlink_login_protected'
            ));
            $srch->setPageSize(10);
        }

        $srch->joinNavigation();
        $srch->joinContentPages();

        $srch->addOrder('nav_id');
        $srch->addOrder('nlink_display_order');

        $srch->addCondition('nav_type', '=', $type);
        $srch->addCondition('nlink_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);
        $srch->addCondition('nav_active', '=', 'mysql_func_' . applicationConstants::ACTIVE, 'AND', true);

        $srch->addHaving('filtered_cpage_deleted', '=', 'mysql_func_' . applicationConstants::NO, 'AND', true);

        $isUserLogged = UserAuthentication::isUserLogged();
        if ($isUserLogged) {
            $cnd = $srch->addCondition('nlink_login_protected', '=', 'mysql_func_' . NavigationLinks::NAVLINK_LOGIN_BOTH, 'AND', true);
            $cnd->attachCondition('nlink_login_protected', '=', 'mysql_func_' . NavigationLinks::NAVLINK_LOGIN_YES, 'OR', true);
        }
        if (!$isUserLogged) {
            $cnd = $srch->addCondition('nlink_login_protected', '=', 'mysql_func_' . NavigationLinks::NAVLINK_LOGIN_BOTH, 'AND', true);
            $cnd->attachCondition('nlink_login_protected', '=', 'mysql_func_' . NavigationLinks::NAVLINK_LOGIN_NO, 'OR', true);
        }
        $srch->addGroupBy('nav_id');
        $srch->addGroupBy('nlink_id');

        $rs = $srch->getResultSet();

        $navigation = array();
        $previous_nav_id = 0;
        $rootCatArr = $rootCatArr ?? ProductCategory::getArray($siteLangId, 0, false, true, false, CONF_USE_FAT_CACHE);

        $count = 0;
        while ($row = FatApp::getDb()->fetch($rs)) {
            if ($count == 0 || $previous_nav_id != $row['nav_id']) {
                $previous_nav_id = $row['nav_id'];
            }
            $navigation[$previous_nav_id]['parent'] = $row['nav_name'];
            $navigation[$previous_nav_id]['pages'][$count] = $row;

            $childrenCats = array();
            if ($includeCategories && $row['nlink_category_id'] > 0) {
                if (array_key_exists($row['nlink_category_id'], $rootCatArr)) {
                    $childrenCats = $rootCatArr[$row['nlink_category_id']]['children'];
                } else {
                    $childrenCats = ProductCategory::getArray($siteLangId, $row['nlink_category_id'], false, true, false, CONF_USE_FAT_CACHE);
                }
            }
            $navigation[$previous_nav_id]['pages'][$count]['children'] = isset($childrenCats) ? $childrenCats : [];
            $count++;
        }

        CacheHelper::create('headerNavCache' . $siteLangId . '-' . $type . '-' . ($includeCategories ? 1 : 0), serialize($navigation), CacheHelper::TYPE_NAVIGATION);
        return $navigation;
    }

    public static function footerNavigation($template)
    {
        $siteLangId = CommonHelper::getLangId();
        $footerNavigation = CacheHelper::get('footerNavigationCache' . $siteLangId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if ($footerNavigation) {
            $footerNavigation = unserialize($footerNavigation);
        } else {
            $footerNavigation = self::getNavigation(Navigations::NAVTYPE_FOOTER);
            CacheHelper::create('footerNavigationCache' . $siteLangId, serialize($footerNavigation), CacheHelper::TYPE_NAVIGATION);
        }
        $template->set('footer_navigation', $footerNavigation);
    }

    public static function blogNavigation($template)
    {
        $siteLangId = CommonHelper::getLangId();
        $categoriesArr = CacheHelper::get('blogCategoriesHeaderCache' . $siteLangId, CONF_HOME_PAGE_CACHE_TIME, '.txt');
        if ($categoriesArr) {
            $categoriesArr = json_decode($categoriesArr, true);
        } else {
            $categoriesArr = BlogPostCategory::getRootBlogPostCatArr($siteLangId, true);
            foreach ($categoriesArr as $id => $category) {
                $srch = BlogPost::getSearchObject(0, true, true, true);
                $srch->addCondition('ptc_bpcategory_id', '=', $id);
                $srch->doNotCalculateRecords();
                $srch->setPageSize(1);
                if (!FatApp::getDb()->fetch($srch->getResultSet())) {
                    unset($categoriesArr[$id]);
                }
            }
            CacheHelper::create('blogCategoriesHeaderCache' . $siteLangId, json_encode($categoriesArr), CacheHelper::TYPE_BLOG_CATEGORY);
        }


        $template->set('categoriesArr', $categoriesArr);
        $template->set('siteLangId', $siteLangId);
    }

    public static function blogNavigationSearchForm($template)
    {
        $blog = new BlogController();
        $blogSearchFrm = $blog->getBlogSearchForm();
        $template->set('blogSearchFrm', $blogSearchFrm);
    }
}
