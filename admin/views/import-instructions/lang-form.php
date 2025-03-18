<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

HtmlHelper::formatFormFields($langFrm);

$langFrm->setFormTagAttribute('id', 'editorLangFormJs');
$langFrm->setFormTagAttribute('data-onclear', 'editLangData(' . $recordId . ', ' . $siteLangId . ')');
$langFrm->setFormTagAttribute('class', 'modal-body form form-edit modalFormJs layout--' . $formLayout);
$langFrm->setFormTagAttribute('dir', $formLayout);
$langFrm->setFormTagAttribute('onsubmit', 'saveLangData($("#editorLangFormJs")); return(false);');

$langFld = $langFrm->getField('lang_id');
$langFld->setfieldTagAttribute('onChange', "editLangData(" . $recordId . ", this.value);");
$translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
if (!empty($translatorSubscriptionKey) && $lang_id != CommonHelper::getDefaultFormLangId()) {
    $langFld->developerTags['fldWidthValues'] = ['d-flex', '', '', ''];
    $langFld->htmlAfterField = '<a href="javascript:void(0);" onclick="editLangData(' . $recordId . ', ' . $lang_id . ', 1)" class="btn" title="' .  Labels::getLabel('BTN_AUTOFILL_LANGUAGE_DATA', $siteLangId) . '">
                                        <svg class="svg" width="18" height="18">
                                            <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-translate">
                                            </use>
                                        </svg>
                                    </a>';
} 

$fld = $langFrm->getField('epage_content');
$htmlFld = $langFrm->addHTML('','epage_content_html', '<div class="col-md-12"><div class="form-group"><label class="label lbl-link">'.$fld->getCaption().'<a class="link" href="javascript:void(0)" onclick="resetToDefaultContent();">'.Labels::getLabel('LBL_RESET_TO_DEFAULT_CONTENT', $siteLangId).'</a></label>'.$fld->getHtml().'</div></div>');
$langFrm->changeFieldPosition($htmlFld->getFormIndex(), $fld->getFormIndex());
$langFrm->removeField($fld);

$fld = $langFrm->getField('auto_update_other_langs_data');
if ($fld != null) {
    HtmlHelper::configureSwitchForCheckbox($fld);
    $fld->developerTags['noCaptionTag'] = true;
    $fld->developerTags['colWidthValues'] = [null, '12', null, null];
}
?>
<!-- editor's default content[ -->

<div id="editor_default_content" style="display:none;">
    <?php echo (isset($epageData)) ? html_entity_decode($epageData['epage_default_content']) : '';?>
</div>
<!-- ] -->

<div class="modal-header">
    <h5 class="modal-title">
        <?php echo Labels::getLabel('LBL_SETUP_IMPORT_INSTRUCTIONS', $siteLangId); ?>
    </h5>
</div>
<div class="modal-body form-edit"> 
    <div class="form-edit-body loaderContainerJs">
        <?php echo $langFrm->getFormHtml(); ?>
    </div>

    <?php require_once(CONF_THEME_PATH . '_partial/listing/form-edit-foot.php'); ?>
</div>