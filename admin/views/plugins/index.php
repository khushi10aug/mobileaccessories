<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$sortByFld = $frmSearch->getField('sortBy');
$sortByFld->setFieldTagAttribute('id', 'sortBy');

$sortOrderFld = $frmSearch->getField('sortOrder');
$sortOrderFld->setFieldTagAttribute('id', 'sortOrder'); ?>

<main class="main">
    <div class="container">
        <?php echo $frmSearch->getFormHtml(); ?>
        <?php if (1 > $tourStep) {
            $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false);
        ?>
            <div class="grid-layout">
                <div class="grid-layout-left">
                    <button class="float-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#card-aside" aria-controls="card-aside">
                        <svg class="svg" width="20" height="20">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#nav">
                            </use>
                        </svg>
                    </button>
                    <div class="card  offcanvas sticky-sidebar sticky-top  card-aside" tabindex="-1" id="card-aside" aria-labelledby="card-asideLabel">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h3 class="card-head-title">
                                    <?php echo Labels::getLabel('LBL_headings', $siteLangId); ?>
                                </h3>
                            </div>
                            <div class="card-toolbar">
                                <button type="button" class="btn-close card-aside-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="settings-inner">
                                <ul class="pluginTypesJs">
                                    <?php foreach ($pluginTypes as $formType => $tabName) {
                                        $tabsId = 'tabJs-' . $formType; ?>
                                        <li class="settings-inner-item <?php echo $tabsId; ?> <?php echo ($activeTab == $formType) ? 'is-active' : '' ?>" data-listType="<?php echo $formType; ?>">
                                            <a class="settings-inner-link" href="javascript:void(0)" onclick="searchRecords(<?php echo $formType; ?>);">
                                                <i class="settings-inner-icn">
                                                    <svg class="svg" width="20" height="20">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-settings.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#<?php echo isset($svgIconNames[$formType]) ? $svgIconNames[$formType] : 'icon-extension'; ?>">
                                                        </use>
                                                    </svg>
                                                </i>
                                                <div>
                                                    <h6 class="settings-inner-title"><?php echo $tabName; ?></h6>
                                                    <span class="settings-inner-desc"><?PHP echo $labels[$formType] ?> </span>
                                                </div>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid-layout-right">
                    <div class="card" id="pluginsListing">
                        <?php require_once(CONF_THEME_PATH . 'plugins/search.php'); ?>
                    </div>
                </div>
            </div>
        <?php } else { ?>
            <?php require_once(CONF_THEME_PATH . 'getting-started/top-nav.php'); ?>
            <div class="onboarding">
                <?php require_once(CONF_THEME_PATH . 'getting-started/left-nav.php'); ?>
                <div class="onboarding-main "  >
                    <div class="card" id="pluginsListing">
                        <?php require_once(CONF_THEME_PATH . 'plugins/search.php'); ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</main>