<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$this->includeTemplate('_partial/footer-part/headerSearchFormArea.php'); ?>

<div class="offcanvas offcanvas-start categories-menu categoriesJs" tabindex="-1" aria-labelledby="categories-menuLabel"
    id="categories-menu">

    <ul class="grouping grouping-level">
        <li class="skeleton grouping-item"></li>
        <li class="skeleton grouping-item"></li>
        <li class="skeleton grouping-item"></li>
        <li class="skeleton grouping-item"></li>
        <li class="skeleton grouping-item"></li>
        <li class="skeleton grouping-item"></li>
        <li class="skeleton grouping-item"></li>
        <li class="skeleton grouping-item"></li>
        <li class="skeleton grouping-item"></li>
    </ul>

</div>

<?php if (!in_array($controllerName, ['Cart', 'Checkout'])) { ?>
    <?php $this->includeTemplate('_partial/cart-summary.php', ['showHeaderButton' => false]); ?>
<?php } ?>

<div class="offcanvas offcanvas-end offcanvas-filters" tabindex="-1" id="filters-right">
    <div class="offcanvas-header">
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="productFiltersJs"></div>
        <div class="otherFiltersJs"></div>
    </div>
</div>
<?php if ((!UserAuthentication::isUserLogged() && UserAuthentication::isGuestUserLogged()) || UserAuthentication::isUserLogged()) {
    $this->includeTemplate('_partial/headerUserArea.php', ['layoutType' => applicationConstants::SCREEN_MOBILE]);
}

if (FatApp::getConfig('CONF_ENABLE_GEO_LOCATION', FatUtility::VAR_INT, 0) && !empty(FatApp::getConfig('CONF_GOOGLEMAP_API_KEY', FatUtility::VAR_STRING, ''))) { ?>
    <div class="offcanvas offcanvas-bottom offcanvas-gps-location" tabindex="-1" id="offcanvas-gps-location">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title"><?php echo Labels::getLabel('LBL_CHANGE_LOCATION', $siteLangId); ?></h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="geo-location-mobile">
                <div class="geo-location_inner">
                    <?php
                    $geoAddress = '';
                    if ((!isset($_COOKIE['_ykGeoLat']) || !isset($_COOKIE['_ykGeoLng']) || !isset($_COOKIE['_ykGeoCountryCode'])) && FatApp::getConfig('CONF_DEFAULT_GEO_LOCATION', FatUtility::VAR_INT, 0)) {
                        $geoAddress = FatApp::getConfig('CONF_GEO_DEFAULT_ADDR', FatUtility::VAR_STRING, '');
                        if (empty($address)) {
                            $address = FatApp::getConfig('CONF_GEO_DEFAULT_ZIPCODE', FatUtility::VAR_INT, 0) . '-' . FatApp::getConfig('CONF_GEO_DEFAULT_STATE', FatUtility::VAR_STRING, '');
                        }
                    }
                    if (empty($geoAddress)) {
                        $geoAddress = Labels::getLabel("LBL_Location", $siteLangId);
                    }
                    $geoAddress = isset($_COOKIE["_ykGeoAddress"]) ? $_COOKIE["_ykGeoAddress"] : $geoAddress;
                    ?>
                    <div class="geo-location_dropdown-menu">
                        <div class="geo-location_body">
                            <input autocomplete="no" id="ga-autoComplete-mobile" class="geo-location_input pac-target-input"
                                title="<?php echo Labels::getLabel('LBL_TYPE_YOUR_ADDRESS', $siteLangId); ?>"
                                placeholder="<?php echo Labels::getLabel('LBL_TYPE_YOUR_ADDRESS', $siteLangId); ?>"
                                type="text" name="location" value="<?php echo $geoAddress; ?>">
                            <div class="or">
                                <span><?php echo Labels::getLabel("LBL_OR", $siteLangId); ?></span>
                            </div>
                            <button onclick="loadGeoLocation()" class="btn btn-brand btn-block btn-detect">

                                <svg class="svg" width="18" height="18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-header.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#gps">
                                    </use>
                                </svg>

                                <span class="txt">
                                    <?php echo Labels::getLabel('LBL_DETECT_MY_CURRENT_LOCATION', $siteLangId); ?>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (in_array($controllerName, ['Supplier', 'GuestAffiliate', 'GuestAdvertiser']) && in_array($action, ['index', 'account'])) {
    $this->includeTemplate('_partial/footer-part/mobile-header-top-navigation.php', ['liClass' => 'seller-nav-item', 'aClass' => 'seller-nav-link']);
} ?>

<?php if ('Blog' == $controllerName) {
    $this->includeTemplate('_partial/footer-part/blog-search-form.php', ['siteLangId' => $siteLangId]);
    $this->includeTemplate('_partial/footer-part/blog-mobile-menu.php', ['siteLangId' => $siteLangId]);
} ?>