<?php
$pNameFld = $frm->getField('shipprofile_name');
$translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
if (!empty($translatorSubscriptionKey) && $langId != CommonHelper::getDefaultFormLangId()) {
    $pNameFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
    $pNameFld->htmlAfterField = '<a href="javascript:void(0);" onclick="loadLangData(1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $langId) . '">
                        <svg class="svg" width="18" height="18">
                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                            </use>
                        </svg>
                    </a>';
}
?>
<div class="row" <?php echo CommonHelper::getLayoutDirection() != Language::getLayoutDirection($langId) ? 'dir="'.Language::getLayoutDirection($langId).'"' : ''; ?>>
    <div class="col-md-9">
        <div class="form-group">
            <div class="d-flex">
                <?php
                $pNameFld = $frm->getField('shipprofile_name');
                $pNameFld->addFieldTagAttribute('class', 'form-control');
                echo $pNameFld->getHtml();
                ?>
            </div>
            <span class='form-text text-muted'><?php echo Labels::getLabel("LBL_Customers_will_not_see_this", $langId); ?></span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <div class="">
                <?php
                echo $frm->getFieldHtml('shipprofile_id');
                echo $frm->getFieldHtml('shipprofile_user_id');
                $btn = $frm->getField('btn_submit');
                $btn->addFieldTagAttribute('class', 'btn btn-brand');
                echo $frm->getFieldHtml('btn_submit');
                ?>
            </div>
        </div>
    </div>
</div>