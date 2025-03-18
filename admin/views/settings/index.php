<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<main class="main mainJs">
    <div class="container">
        <?php
        $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false); ?>
        <div class="card">
            <?php if ($canEdit) { ?>
                <div class="card-head">
                    <div class="card-head-label"></div>
                    <div class="card-toolbar">
                        <div class="maintenance-mode">
                            <label class="switch switch-sm switch-icon">
                                <?php
                                $status = FatApp::getConfig('CONF_MAINTENANCE', FatUtility::VAR_INT, 0);
                                $checked = applicationConstants::ON == $status ? 'checked' : '';
                                ?>
                                <input type="checkbox" name="CONF_MAINTENANCE" data-old-status="<?php echo $status; ?>" value="<?php echo $status; ?>" onclick="updateMaintenanceModeStatus(event, this, <?php echo ((int) !$status); ?>,<?php echo $siteLangId; ?>)" <?php echo $checked; ?>>
                                <span class="input-helper"></span><?php echo Labels::getLabel('FRM_MAINTENANCE_MODE', $siteLangId); ?>
                            </label>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="card-body">
                <?php if (
                    $objPrivilege->canViewGeneralSettings(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewPlugins(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewPaymentMethods(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewCurrencyManagement(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewCommissionSettings(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewAffiliateCommissionSettings(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewSellerPackages(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewThemeColor(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewZones(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewCountries(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewStates(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewEmptyCartItems(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewAbusiveWords(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canEditPagesLanguageData(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewShopReportReasons(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewRatingTypes(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewSmsTemplate(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewEmailTemplates(AdminAuthentication::getLoggedAdminId(), true) ||
                    $objPrivilege->canViewSocialPlatforms(AdminAuthentication::getLoggedAdminId(), true)

                ) { ?>
                    <div class="setting-search">
                        <form class="form">
                            <div class="row justify-content-center">
                                <div class="col-md-12">
                                    <input type="search" id="settingsSearch" autocomplete="off" class="form-control omni-search" name="search" value="" placeholder="<?php echo Labels::getLabel('FRM_SEARCH', $siteLangId); ?>">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="settings settingListJs">
                        <?php if ($objPrivilege->canViewGeneralSettings(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('configurations'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#general-settings">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_SYSTEM_CONFIGURATIONS', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_CONFIGURE_AND_SETUP_E-COMMERCE_STORE', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewPlugins(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('Plugins'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#plugins">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_PLUGINS', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_ADDONS,_THIRD_PARTY_SERVICES', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewLanguageLabels(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('Labels'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#labels">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_LABELS', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_CUSTOMIZE_TEXT_OF_VARIOUS_ELEMENTS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if ($objPrivilege->canEditPagesLanguageData(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('PageLanguageData'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#shop-reports">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_PAGES_LANGUAGE_DATA', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_MANAGE_HELPING_CONTENT_FOR_END_USERS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewThemeColor(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('ThemeColor'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#theme">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_THEME', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_ADJUST_COLORS,_FONTS,_STYLING,_LAYOUT', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewCurrencyManagement(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('CurrencyManagement'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#currencies">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_CURRENCIES', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_MANAGE_CURRENCY_CONVERSION,SYMBOL,_BASE_CURRENCY', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewCommissionSettings(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('Commission'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#site-commission">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_SITE_COMMISSION', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_ADMIN_COMMISION_INCLUDING_PRODUCT,_CATEGORIES,_USERS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewAffiliateCommissionSettings(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('AffiliateCommission'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#affiliate-commision">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_AFFILIATE_COMMISSION', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_AFFILIATE_COMMISION_INCLUDING_PRODUCT_CATEGORY,_AFFILIATE_USERS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewSellerPackages(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('SellerPackages'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#subscriptions-packages">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_SELLER_PACKAGES', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_SUBSCRIPTION_PLANS_AVAILABLE_FOR_SELLERS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewZones(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('Zones'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#zones">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_MANAGE_SHIPPING_ZONES', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_SETUP_SHIPPING_ZONES_AS_PER_SHIPPING_OPTIONS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if ($objPrivilege->canViewCountries(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('Countries'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#countries">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_COUNTRIES', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_COUNTRIES_TO_OFFER_SHIPPING,_TAXES,_PRODUCTS_LISTING', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewStates(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('States'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#states">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_STATES', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_COVERING_COUNTRIES_TO_OFFER_SHIPPING,_TAXES,_PRODUCTS_LISTING', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewAbusiveWords(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('AbusiveWords'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#abusive-keywords">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_ABUSIVE_KEYWORDS', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_LIST_OF_WORDS_TO_MARK_SPAM', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>

                        <?php if ($objPrivilege->canViewEmptyCartItems(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('emptyCartItems'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#empty-cart">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_EMPTY_CART', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_MANAGE_LINKS_TO_EMPTRY_CART_SCREEN', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if ($objPrivilege->canViewShopReportReasons(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('ShopReportReasons'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#shop-reports">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_SHOP_REPORT_REASONS', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_LIST_OF_REASONS_TO_REPORT_SHOP_ISSUES', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if ($objPrivilege->canViewRatingTypes(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('RatingTypes'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#star-outline">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_RATING_TYPES', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_LIST_OF_RATINGS_AVAILABLE_FOR_BUYERS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if ($objPrivilege->canViewSmsTemplate(AdminAuthentication::getLoggedAdminId(), true) && SmsArchive::canSendSms()) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('SmsTemplates'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#sms-notification">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_SMS_TEMPLATES', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_LIST_OF_SMS_NOTIFICATIONS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if ($objPrivilege->canViewEmailTemplates(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('EmailTemplates'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#email">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_EMAIL_TEMPLATES', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_LIST_OF_EMAIL_NOTIFICATIONS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if ($objPrivilege->canViewSellerApprovalForm(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('sellerApprovalForm'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#shop-reports">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_SELLER_APPROVAL_FORM', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_SELLER_INFORMATION_FORM_FIELDS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if ($objPrivilege->canViewSocialPlatforms(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('SocialPlatform'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#shop-reports">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_SOCIAL_PLATFORM', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_SOCIAL_PLATFORM_FORM_FIELDS', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                        <?php if ($objPrivilege->canViewAppReleaseVersions(AdminAuthentication::getLoggedAdminId(), true)) { ?>
                            <a class="setting" href="<?php echo UrlHelper::generateUrl('AppReleaseVersion'); ?>">
                                <div class="setting__icon">
                                    <span class="icon">
                                        <svg class="icon" width="40" height="40">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#app-update">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <div class="setting__detail">
                                    <h6><?php echo Labels::getLabel('NAV_APP_RELEASE_VERSIONS', $siteLangId); ?></h6>
                                    <span><?php echo Labels::getLabel('MSG_APP_RELEASE_VERSION_UPDATES', $siteLangId); ?></span>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>
                <div style="display: none;">
                    <?php $this->includeTemplate('_partial/no-record-found.php'); ?>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    var formType = '<?php echo Configurations::FORM_SYSTEM; ?>';
</script>