<div class="app">
    <?php
    $this->includeTemplate('_partial/navigation/left-navigation.php');
    $getProfileImageData = ImageDimension::getData(ImageDimension::TYPE_USER_PROFILE_IMAGE, ImageDimension::VIEW_CROPED);
    ?>
    <div class="wrap">
        <header class="main-header mainHeaderJs">
            <div class="container-fluid">
                <div class="main-header-inner">
                    <div class="page-title">
                        <h1>
                            <?php
                            if (0 < SiteTourHelper::getStepIndex()) {
                                echo Labels::getLabel('NAV_GETTING_STARTED', $siteLangId);
                            } elseif (array_key_exists('pageTitle', $this->variables)) {
                                echo $this->variables['pageTitle'];
                            } else {
                                echo Labels::getLabel('NAV_DASHBOARD', $siteLangId);
                            } ?>
                        </h1>
                        <?php if (isset($pageData['plang_summary'])) { ?>
                            <span class="page-title-sub"> <?php echo $pageData['plang_summary']; ?> <a
                                    href="javascript:void(0)" class="openAlertJs"
                                    data-pageid="<?php echo $pageData['plang_id']; ?>"
                                    data-name="<?php echo 'alert_' . $pageData['plang_id']; ?>"
                                    title="<?php echo Labels::getLabel('LBL_ALERT', $siteLangId) ?>">
                                    <?php if (!empty($pageData['plang_warring_msg'])) { ?>
                                        <i class="fas fa-exclamation-triangle"></i></a></span>
                            <?php } ?>
                        <?php } ?>

                    </div>
                    <div class="main-header-toolbar">
                        <div class="header-action">
                            <div class="header-action__item">
                                <a class="header-action__trigger"
                                    href="<?php echo SiteTourHelper::getUrl(SiteTourHelper::STEP_CONFIGURATION); ?>"
                                    title="<?php echo Labels::getLabel('LBL_GET_STARTED', $siteLangId); ?>">
                                    <span class="icon">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-getting-started">
                                            </use>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                            <div class="header-action__item">
                                <a class="header-action__trigger quickSearchMainJs" href="javascript:void(0);"
                                    data-bs-toggle="modal" data-bs-target="#search-main"
                                    title="<?php echo Labels::getLabel('LBL_GLOBAL_SEARCH', $siteLangId); ?>">
                                    <span class="icon">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-search">
                                            </use>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                            <div class="header-action__item">
                                <a class="header-action__trigger" href="<?php echo CONF_WEBROOT_FRONT_URL; ?>"
                                    target="_blank"
                                    title="<?php echo Labels::getLabel('LBL_VIEW_STORE', $siteLangId); ?>">
                                    <span class="icon">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-store">
                                            </use>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                            <div class="header-action__item">
                                <a class="header-action__trigger" href="javascript:void(0)" onclick="clearCache()"
                                    title="<?php echo Labels::getLabel('LBL_CLEAR_CACHE', $siteLangId); ?>">
                                    <span class="icon">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-cache">
                                            </use>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                            <div class="header-action__item dropdown">
                                <a class="header-action__trigger dropdown-toggle no-after" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" href="javascript:void();"
                                    onclick="getNotifications(0);"
                                    title="<?php echo Labels::getLabel('LBL_NOTIFICATIONS', $siteLangId); ?>">
                                    <?php if (0 < $notificationCount) { ?>
                                        <span class="dot"></span>
                                    <?php } ?>
                                    <span class="icon">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon-notification">
                                            </use>
                                        </svg>
                                    </span>
                                </a>
                                <div
                                    class="header-action__target p-0 dropdown-menu dropdown-menu-right dropdown-menu-anim notificationDropMenuJs dropDownMenuBlockClose">
                                    <div class="header-notification">
                                        <div class="header-notification__head">
                                            <h5><?php echo Labels::getLabel('LBL_NOTIFICATIONS', $siteLangId); ?> <span
                                                    class="count hide notifiLinkCountJs"></span></h5>
                                            <nav class="nav nav--tabs js-tab">
                                                <a class="is-current headerNotificationTabJs" href="javascript:void(0)"
                                                    onclick="getNotifications(0,this);"><?php echo Labels::getLabel('LBL_NOTIFICATIONS', $siteLangId); ?></a>
                                                <a class="headerNotificationTabJs" href="javascript:void(0)"
                                                    onclick="getNotifications(1,this);"><?php echo Labels::getLabel('LBL_SYSTEM_LOGS', $siteLangId); ?></a>
                                            </nav>
                                        </div>
                                        <div class="header-notification__body">
                                            <div class="tab-1 tab-container visible">
                                                <div class="scroll-y p-3">
                                                    <div class="notifications notificationListJS">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="header-notification__footer">
                                            <a href="javascript:void(0)"
                                                class="text-link text-link--arrow notifiLinkViewAllJs"><?php echo Labels::getLabel('LBL_VIEW_ALL', $siteLangId); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="header-action__item dropdown header-account">
                                <a class="dropdown-toggle no-after" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" href="javascript:void(0)">
                                    <span class="header-account__img">
                                        <img aria-expanded="false"
                                            data-ratio="<?php echo $getProfileImageData[ImageDimension::VIEW_CROPED]['aspectRatio']; ?>"
                                            src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'profileImage', array(AdminAuthentication::getLoggedAdminId(), ImageDimension::VIEW_CROPED, true)) . ($_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['admin_updated_on'] ?? time()), CONF_IMG_CACHE_TIME, '.jpg'); ?>"
                                            alt="<?php echo Labels::getLabel('LBL_ADMIN', $siteLangId); ?>">
                                    </span>
                                </a>

                                <div
                                    class="header-action__target dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim dropDownMenuBlockClose">
                                    <div class="header-account__avtar">
                                        <div class="profile">
                                            <div class="profile__img">
                                                <img alt="<?php echo Labels::getLabel('LBL_ADMIN', $siteLangId); ?>"
                                                    data-ratio="<?php echo $getProfileImageData[ImageDimension::VIEW_CROPED]['aspectRatio']; ?>"
                                                    src="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('Image', 'profileImage', array(AdminAuthentication::getLoggedAdminId(), ImageDimension::VIEW_CROPED, true)) . ($_SESSION[AdminAuthentication::SESSION_ELEMENT_NAME]['admin_updated_on'] ?? time()), CONF_IMG_CACHE_TIME, '.jpg'); ?>">
                                            </div>
                                            <div class="profile__detail">
                                                <h6>
                                                    <?php echo Labels::getLabel('LBL_HI', $siteLangId); ?>,
                                                    <?php echo AdminAuthentication::getLoggedAdminAttribute('admin_name', true); ?>
                                                </h6>
                                                <span>
                                                    <a
                                                        href="mailto:<?php echo AdminAuthentication::getLoggedAdminAttribute('admin_email', true); ?>"><?php echo AdminAuthentication::getLoggedAdminAttribute('admin_email', true); ?></a>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="separator m-0"></div>
                                    <nav class="nav nav--header-account">
                                        <a href="<?php echo UrlHelper::generateUrl('profile'); ?>">
                                            <?php echo Labels::getLabel('LBL_MY_PROFILE', $siteLangId); ?></a>
                                        <a
                                            href="<?php echo UrlHelper::generateUrl('profile', 'index', ['changePassword']); ?>"><?php echo Labels::getLabel('LBL_CHANGE_PASSWORD', $siteLangId); ?></a>
                                    </nav>
                                    <div class="separator m-0"></div>
                                    <nav class="nav nav--header-account">
                                        <?php if (1 < count($languages)) { ?>
                                            <a class="language-selector collapsed" data-bs-toggle="collapse"
                                                href="#languages" role="button" aria-expanded="false"
                                                aria-controls="languages">
                                                <?php echo Labels::getLabel('NAV_LANGUAGES', $siteLangId) ?>
                                                <span class="selected-language">
                                                    <?php echo CommonHelper::getLangCode() ?>
                                                    <span>
                                                        <img
                                                            src="<?php echo CONF_WEBROOT_FRONTEND; ?>images/flags/round/<?php echo CommonHelper::getLangCountryCode() ?>.svg"></span>
                                                </span>

                                            </a>
                                            <div class="languages collapse" id="languages">
                                                <?php foreach ($languages as $languageId => $language) { ?>
                                                    <a class="languages-link <?php echo ($siteLangId == $languageId) ? 'is--active' : ''; ?>"
                                                        href="javascript:void(0);"
                                                        onclick="setSiteDefaultLang(<?php echo $languageId; ?>)"><?php echo $language['language_name'] ?? $language; ?></a>
                                                <?php } ?>
                                            </div>
                                            <?php
                                        } ?>
                                        <a
                                            href="<?php echo UrlHelper::generateUrl('profile', 'logout'); ?>"><?php echo Labels::getLabel('LBL_LOGOUT', $siteLangId); ?></a>
                                    </nav>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($pageData['plang_recommendations']) && !empty($pageData['plang_recommendations'])) { ?>
                <div class="alert alert-solid-info fade show" role="alert">
                    <div class="alert-icon"><svg class="svg" width="20" height="20">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#info">
                            </use>
                        </svg></div>
                    <div class="alert-text"><?php echo nl2br($pageData['plang_recommendations']); ?></div>
                </div>
            <?php } ?>
            <?php if (isset($pageData['plang_warring_msg']) && !empty($pageData['plang_warring_msg']) && ('true' != CommonHelper::getCookie('alert_' . $pageData['plang_id']))) { ?>
                <div class="alert alert-solid-warning fade alertWarningJs show " role="alert">
                    <div class="alert-icon">
                        <svg class="svg" width="20" height="20">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#warning">
                            </use>
                        </svg>
                    </div>
                    <div class="alert-text"><?php echo nl2br($pageData['plang_warring_msg']); ?></div>
                    <div class="alert-close">
                        <button type="button" class="btn-close closeAlertJs <?php echo 'alert_' . $pageData['plang_id']; ?>"
                            data-bs-dismiss="alert" aria-label="Close"
                            data-name="<?php echo 'alert_' . $pageData['plang_id']; ?>">

                        </button>
                    </div>
                </div>
            <?php } ?>
        </header>