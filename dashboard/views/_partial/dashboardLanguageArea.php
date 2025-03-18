<?php defined('SYSTEM_INIT') or die('Invalid Usage');

if ($languages && count($languages) > 1) { ?>
    <li class="dashboard-menu-item dropdownJs">
        <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?> data-bs-toggle="collapse" data-bs-target="#nav-language" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
            <span class="dashboard-menu-icon">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#language">
                    </use>
                </svg>
            </span>
            <span class="dashboard-menu-head menuTitleJs">
                <?php echo Labels::getLabel("LBL_LANGUAGE", $siteLangId); ?>
            </span>
            <?php if (false === $quickSearch) { ?>
                <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
            <?php } ?>
        </button>
        <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-language" aria-labelledby="" data-parent="#dashboard-menu">
            <?php foreach ($languages as $langId => $language) { ?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link noCollapseJs navLinkJs <?php echo (false === $quickSearch && $siteLangId == $langId) ? 'active' : ''; ?>" href="javascript:void(0);" onclick="setSiteDefaultLang(<?php echo $langId; ?>)">
                        <span class="menu-sub-title navTextJs">
                            <?php echo $language['language_name']; ?>
                        </span>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </li>
<?php }

if ($currencies && count($currencies) > 1) { ?>
    <li class="dashboard-menu-item dropdownJs">
        <button class="dashboard-menu-btn menuLinkJs dropdown-toggle-custom collapsed" type="button" <?php if (false === $quickSearch) { ?> data-bs-toggle="collapse" data-bs-target="#nav-currency" aria-expanded="true" aria-controls="collapseOne" <?php } ?> title="">
            <span class="dashboard-menu-icon">
                <svg class="svg" width="18" height="18">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-sidebar.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#currency">
                    </use>
                </svg>
            </span>
            <span class="dashboard-menu-head menuTitleJs">
                <?php echo Labels::getLabel("LBL_CURRENCY", $siteLangId); ?>
            </span>
            <?php if (false === $quickSearch) { ?>
                <i class="dashboard-menu-arrow dropdown-toggle-custom-arrow"></i>
            <?php } ?>
        </button>
        <ul class="menu-sub menu-sub-accordion <?php echo $collapseClass; ?>" id="nav-currency" aria-labelledby="" data-parent="#dashboard-menu">
            <?php foreach ($currencies as $currencyId => $currency) { ?>
                <li class="menu-sub-item navItemJs">
                    <a class="menu-sub-link noCollapseJs navLinkJs <?php echo (false === $quickSearch && CommonHelper::getCurrencyId() == $currencyId) ? 'active' : ''; ?>" href="javascript:void(0);" onclick="setSiteDefaultCurrency(<?php echo $currencyId; ?>)">
                        <span class="menu-sub-title navTextJs">
                            <?php echo $currency; ?>
                        </span>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </li>
<?php } ?>