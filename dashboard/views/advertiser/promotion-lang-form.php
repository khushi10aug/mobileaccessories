<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
HtmlHelper::formatFormFields($langFrm);

$langFrm->setFormTagAttribute('class', 'form modalFormJs');
if (CommonHelper::getLayoutDirection() != $formLayout) {
    $langFrm->addFormTagAttribute('class', "layout--" . $formLayout);
    $langFrm->setFormTagAttribute('dir', $formLayout);
}
$langFrm->setFormTagAttribute('onsubmit', 'setupPromotionLang(this); return(false);');

$langFld = $langFrm->getField('lang_id');
$langFld->setfieldTagAttribute('onChange', "promotionLangForm(" . $recordId . ", this.value);");
$translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
if (!empty($translatorSubscriptionKey) && $langId != CommonHelper::getDefaultFormLangId()) {
    $langFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
    $langFld->htmlAfterField = '<a href="javascript:void(0);" onclick="promotionLangForm(' . $recordId . ', ' . $langId . ', 1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $langId) . '">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                            </use>
                        </svg>
                    </a>';
}

unset($languages[CommonHelper::getDefaultFormLangId()]);
?>
<div class="modal-header">
    <h5 class="modal-title">
        <?php echo Labels::getLabel('LBL_PROMOTION_SETUP'); ?>
    </h5>
</div>
<div class="modal-body form-edit">
    <div class="form-edit-head">
        <nav class="nav nav-tabs navTabsJs">
            <a class="nav-link" href="javascript:void(0);" title="<?php echo Labels::getLabel('NAV_GENERAL', $siteLangId); ?>" onclick="promotionForm(<?php echo $recordId; ?>)"><?php echo Labels::getLabel('NAV_GENERAL', $siteLangId); ?></a>
            <?php if(0 < count($languages)){ ?>
            <a class="nav-link active" href="javascript:void(0);" <?php echo (0 < $recordId) ? "onclick='promotionLangForm(" . $recordId . "," . array_key_first($languages) . ");'" : ""; ?>>
                <?php echo Labels::getLabel('LBL_Language_Data', $siteLangId); ?>
            </a>
            <?php } ?>
            <?php if ($promotionType == Promotion::TYPE_BANNER || $promotionType == Promotion::TYPE_SLIDES) { ?>
                <a class="nav-link" href="javascript:void(0)" <?php if ($recordId > 0) { ?> onclick="promotionMediaForm(<?php echo $recordId; ?>)" <?php } ?>><?php echo Labels::getLabel('LBL_Media', $siteLangId); ?></a>
            <?php } ?>
        </nav>
    </div>
    <div class="form-edit-body loaderContainerJs sectionbody space">
        <div class="row" id="promotionsChildBlockJs">
            <div class="col-md-12">
                <?php echo $langFrm->getFormHtml(); ?>
            </div>
        </div>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div>