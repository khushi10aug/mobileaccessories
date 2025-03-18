<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form layout--' . $formLayout);
$frm->setFormTagAttribute('onsubmit', 'setupFontStyle(this); return(false);');

$fld = $frm->getField('btn_clear');
$fld->addFieldTagAttribute('onclick', 'resetToDefault();');

$googleFontFamily = "Montserrat";
$fontWeight = FatApp::getConfig('CONF_THEME_FONT_WEIGHT', FatUtility::VAR_STRING, "");
$disabled  = empty($fontWeight) ? 'disabled="disabled"' : '';

$googleFontFamilyUrl = FatApp::getConfig('CONF_THEME_FONT_FAMILY_URL', FatUtility::VAR_STRING, '');
$themeColor = FatApp::getConfig('CONF_THEME_COLOR', FatUtility::VAR_STRING, "#FF3A59");
$themeColorRgb = FatApp::getConfig('CONF_THEME_COLOR_RGB', FatUtility::VAR_STRING, "255,58,89");
$themeColorHsl = FatApp::getConfig('CONF_THEME_COLOR_HSL', FatUtility::VAR_STRING, "351,100%,61%");

$themeColorInverse = FatApp::getConfig('CONF_THEME_COLOR_INVERSE', FatUtility::VAR_STRING, "#ffffff");
$themeColorInverseRgb = FatApp::getConfig('CONF_THEME_COLOR_INVERSE_RGB', FatUtility::VAR_STRING, "255,255,255");
$themeColorInverseHsl = FatApp::getConfig('CONF_THEME_COLOR_INVERSE_HSL', FatUtility::VAR_STRING, "0,0%,100%");

$secondaryThemeColor = FatApp::getConfig('CONF_SECONDARY_THEME_COLOR', FatUtility::VAR_STRING, "#6DCDEF");
$secondaryThemeColorRgb = FatApp::getConfig('CONF_SECONDARY_THEME_COLOR_RGB', FatUtility::VAR_STRING, "109 205 239");
$secondaryThemeColorHsl = FatApp::getConfig('CONF_SECONDARY_THEME_COLOR_HSL', FatUtility::VAR_STRING, "196,80%,68%");

$secondaryThemeColorInverse = FatApp::getConfig('CONF_SECONDARY_THEME_COLOR_INVERSE', FatUtility::VAR_STRING, "#ffffff");
$secondaryThemeColorInverseRgb = FatApp::getConfig('CONF_SECONDARY_THEME_COLOR_INVERSE_RGB', FatUtility::VAR_STRING, "255,255,255");
$secondaryThemeColorInverseHsl = FatApp::getConfig('CONF_SECONDARY_THEME_COLOR_INVERSE_HSL', FatUtility::VAR_STRING, "0,0%,100%");

if (!empty($googleFontFamilyUrl)) {
    $googleFontFamily = FatApp::getConfig('CONF_THEME_FONT_FAMILY', FatUtility::VAR_STRING, '');
    $googleFontFamily = str_replace("+", " ", explode('-', $googleFontFamily)[0]);

?>
    <link href="<?php echo $googleFontFamilyUrl; ?>" rel="stylesheet">
<?php
}
?>
<main class="main">
    <div class="container">
        <?php $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false); ?>
        <div class="grid-layout theme-layout">
            <div class="grid-layout-left">
                <div class="card">
                    <div class="card-body">
                        <?php echo $frm->getFormTag();
                        if (null != $frm->getField('CONF_THEME_FONT_FAMILY_URL')) {
                            echo $frm->getFieldHtml('CONF_THEME_FONT_FAMILY_URL');
                        }
                        ?>

                        <?php if (!empty($apiKey)) { ?>
                            <div class="form-group">

                                <label class="label"><?php echo Labels::getLabel('LBL_FONT_FAMILY', $siteLangId); ?>*</label>
                                <input type="search" name="CONF_THEME_FONT_FAMILY" placeholder="<?php echo Labels::getLabel('LBL_SEARCH_FONTS', $siteLangId); ?>" value="<?php echo $googleFontFamily; ?>">

                            </div>

                            <div class="form-group">
                                <label class="label">
                                    <?php echo Labels::getLabel('LBL_SELECT_FONT_WEIGHT', $siteLangId); ?>*
                                </label>
                                <input name='CONF_THEME_FONT_WEIGHT' class='form-control tagify--outside tagifyWeightJs' placeholder='<?php echo Labels::getLabel('LBL_SELECT_WEIGHT', $siteLangId); ?>' <?php echo $disabled; ?> value="<?php echo htmlentities($fontWeight); ?>">
                            </div>
                        <?php } ?>
                        <div class="form-group">
                            <label class="label"><?php echo Labels::getLabel('FRM_PRIMARY_THEME_COLOR', $siteLangId); ?></label>
                            <div class="color-data colorBlockJs">
                                <?php
                                $fld = $frm->getField('CONF_THEME_COLOR_RGB');
                                $fld->addFieldTagAttribute('class', 'inputRgbJs');

                                $fld = $frm->getField('CONF_THEME_COLOR_HSL');
                                $fld->addFieldTagAttribute('class', 'inputHslJs');

                                echo $frm->getFieldHtml('CONF_THEME_COLOR_RGB');
                                echo $frm->getFieldHtml('CONF_THEME_COLOR_HSL');
                                ?>
                                <div class="color-swatch" title="Selected color">
                                    <input type="color" value="<?php echo $themeColor; ?>" class="themeColorJs colorPickerJs" name="CONF_THEME_COLOR">
                                </div>
                                <div class="color-label color-hex">
                                    <h5><?php echo Labels::getLabel('LBL_HEX', $siteLangId); ?></h5>
                                    <span class="hex hexJs"><?php echo $themeColor; ?></span>
                                </div>
                                <div class="color-label color-rgb">
                                    <h5><?php echo Labels::getLabel('LBL_RGB', $siteLangId); ?></h5>
                                    <span class="rgb rgbJs"><?php echo $themeColorRgb; ?></span>
                                </div>
                                <div class="color-label color-hsl">
                                    <h5><?php echo Labels::getLabel('LBL_HSL', $siteLangId); ?></h5>
                                    <span class="hsl hslJs"><?php echo $themeColorHsl; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="label"><?php echo Labels::getLabel('FRM_PRIMARY_THEME_COLOR_INVERSE', $siteLangId); ?></label>
                            <div class="color-data colorBlockJs">
                                <?php
                                $fld = $frm->getField('CONF_THEME_COLOR_INVERSE_RGB');
                                $fld->addFieldTagAttribute('class', 'inputRgbJs');

                                $fld = $frm->getField('CONF_THEME_COLOR_INVERSE_HSL');
                                $fld->addFieldTagAttribute('class', 'inputHslJs');

                                echo $frm->getFieldHtml('CONF_THEME_COLOR_INVERSE_RGB');
                                echo $frm->getFieldHtml('CONF_THEME_COLOR_INVERSE_HSL');
                                ?>
                                <div class="color-swatch" title="Selected color">
                                    <input type="color" value="<?php echo $themeColorInverse; ?>" class="themeColorInverseJs colorPickerJs" name="CONF_THEME_COLOR_INVERSE">
                                </div>
                                <div class="color-label color-hex">
                                    <h5><?php echo Labels::getLabel('LBL_HEX', $siteLangId); ?></h5>
                                    <span class="hex hexJs"><?php echo $themeColorInverse; ?></span>
                                </div>
                                <div class="color-label color-rgb">
                                    <h5><?php echo Labels::getLabel('LBL_RGB', $siteLangId); ?></h5>
                                    <span class="rgb rgbJs"><?php echo $themeColorInverseRgb; ?></span>
                                </div>
                                <div class="color-label color-hsl">
                                    <h5><?php echo Labels::getLabel('LBL_HSL', $siteLangId); ?></h5>
                                    <span class="hsl hslJs"><?php echo $themeColorInverseHsl; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="label"><?php echo Labels::getLabel('FRM_SECONDARY_THEME_COLOR', $siteLangId); ?></label>
                            <div class="color-data colorBlockJs">
                                <?php
                                $fld = $frm->getField('CONF_SECONDARY_THEME_COLOR_RGB');
                                $fld->addFieldTagAttribute('class', 'inputRgbJs');

                                $fld = $frm->getField('CONF_SECONDARY_THEME_COLOR_HSL');
                                $fld->addFieldTagAttribute('class', 'inputHslJs');

                                echo $frm->getFieldHtml('CONF_SECONDARY_THEME_COLOR_RGB');
                                echo $frm->getFieldHtml('CONF_SECONDARY_THEME_COLOR_HSL');
                                ?>
                                <div class="color-swatch" title="Selected color">
                                    <input type="color" value="<?php echo $secondaryThemeColor; ?>" class="themeColorJs colorPickerSecondaryJs" name="CONF_SECONDARY_THEME_COLOR">
                                </div>
                                <div class="color-label color-hex">
                                    <h5><?php echo Labels::getLabel('LBL_HEX', $siteLangId); ?></h5>
                                    <span class="hex hexJs"><?php echo $secondaryThemeColor; ?></span>
                                </div>
                                <div class="color-label color-rgb">
                                    <h5><?php echo Labels::getLabel('LBL_RGB', $siteLangId); ?></h5>
                                    <span class="rgb rgbJs"><?php echo $secondaryThemeColorRgb; ?></span>
                                </div>
                                <div class="color-label color-hsl">
                                    <h5><?php echo Labels::getLabel('LBL_HSL', $siteLangId); ?></h5>
                                    <span class="hsl hslJs"><?php echo $secondaryThemeColorHsl; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="label"><?php echo Labels::getLabel('FRM_SECONDARY_THEME_COLOR_INVERSE', $siteLangId); ?></label>
                            <div class="color-data colorBlockJs">
                                <?php
                                $fld = $frm->getField('CONF_SECONDARY_THEME_COLOR_INVERSE_RGB');
                                $fld->addFieldTagAttribute('class', 'inputRgbJs');

                                $fld = $frm->getField('CONF_SECONDARY_THEME_COLOR_INVERSE_HSL');
                                $fld->addFieldTagAttribute('class', 'inputHslJs');

                                echo $frm->getFieldHtml('CONF_SECONDARY_THEME_COLOR_INVERSE_RGB');
                                echo $frm->getFieldHtml('CONF_SECONDARY_THEME_COLOR_INVERSE_HSL');
                                ?>
                                <div class="color-swatch" title="Selected color">
                                    <input type="color" value="<?php echo $secondaryThemeColorInverse; ?>" class="themeColorInverseJs colorPickerSecondaryJs" name="CONF_SECONDARY_THEME_COLOR_INVERSE">
                                </div>
                                <div class="color-label color-hex">
                                    <h5><?php echo Labels::getLabel('LBL_HEX', $siteLangId); ?></h5>
                                    <span class="hex hexJs"><?php echo $secondaryThemeColorInverse; ?></span>
                                </div>
                                <div class="color-label color-rgb">
                                    <h5><?php echo Labels::getLabel('LBL_RGB', $siteLangId); ?></h5>
                                    <span class="rgb rgbJs"><?php echo $secondaryThemeColorInverseRgb; ?></span>
                                </div>
                                <div class="color-label color-hsl">
                                    <h5><?php echo Labels::getLabel('LBL_HSL', $siteLangId); ?></h5>
                                    <span class="hsl hslJs"><?php echo $secondaryThemeColorInverseHsl; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-solid-brand " role="alert">
                            <div class="alert-icon">
                                <svg class="svg" width="20" height="20">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#warning">
                                    </use>
                                </svg>
                            </div>
                            <div class="alert-text text-xs">
                                <?php echo Labels::getLabel('LBL_DISCLAIMER:_INVERSE_COLOR_SHOULD_BE_IN_CONTRAST_TO_THE_THEME_COLOR', $siteLangId); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <?php
                                $fld = $frm->getField('btn_clear');
                                $fld->addFieldTagAttribute('class', 'btn btn-outline-brand');
                                echo $frm->getFieldHtml('btn_clear'); ?>
                            </div>
                            <div class="col-auto">
                                <?php
                                $fld = $frm->getField('btn_submit');
                                $fld->addFieldTagAttribute('class', 'btn btn-brand');
                                echo $frm->getFieldHtml('btn_submit'); ?>
                            </div>
                        </div>

                        </form>
                        <?php echo $frm->getExternalJS(); ?>


                    </div>

                </div>

            </div>
            <div class="grid-layout-right">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="palette googleFontsJs">
                            <?php require_once('theme-color-svg.php'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>