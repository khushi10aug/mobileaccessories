<?php
defined('SYSTEM_INIT') or die('Invalid Usage');

$frmSearch->setFormTagAttribute('name', 'frmRecordSearch');
$frmSearch->setFormTagAttribute('onsubmit', 'searchRecords(this); return(false);');
$frmSearch->setFormTagAttribute('id', 'frmRecordSearch');
$frmSearch->setFormTagAttribute('class', 'form form-search');

$keyWordFld = $frmSearch->getField('keyword');
$keyWordFld->addFieldtagAttribute('class', 'form-control');
$keyWordFld->setFieldtagAttribute('placeholder', $keywordPlaceholder);

$sortByFld = $frmSearch->getField('sortBy');
$sortByFld->setFieldTagAttribute('id', 'sortBy');

$sortOrderFld = $frmSearch->getField('sortOrder');
$sortOrderFld->setFieldTagAttribute('id', 'sortOrder');

/* Extra Field */
$userNameFld = $frmSearch->getField('user_name');
if (null != $userNameFld) {
    $userNameFld->addFieldtagAttribute('class', 'form-control');
}
/* Extra Field */

echo $frmSearch->getFormTag();
HtmlHelper::renderHiddenFields($frmSearch);
?>
<div class="card-head">
    <div class="card-head-title">
        <div class="row">
            <div class="col-md-12">
                <div class="input-group">
                    <?php echo $frmSearch->getFieldHtml('keyword'); ?>
                    <a class="btn advanced-trigger ms-2 collapsed advSrchToggleJs" data-bs-toggle="collapse" href="#collapseKeyword" aria-expanded="true" aria-controls="collapseKeyword">
                        <svg class="svg" width="22" height="22">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#double-arrow">
                            </use>
                        </svg>
                    </a>
                    <div class="input-group-append advSrchBtnJs">
                        <?php echo $frmSearch->getFieldHtml('btn_submit'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once(CONF_THEME_PATH . '_partial/listing/listing-head.php'); ?>
</div>
<div class="advanced-search collapse advancedSearchJs" id="collapseKeyword">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label class="label"><?php echo Labels::getLabel('FRM_FEATURED', $siteLangId); ?></label>
                <?php echo $frmSearch->getFieldHtml('shop_featured'); ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="label"><?php echo Labels::getLabel('FRM_STATUS', $siteLangId); ?></label>
                <?php echo $frmSearch->getFieldHtml('shop_active'); ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">

                <label class="label"><?php echo Labels::getLabel('FRM_SHOP_STATUS_BY_SELLER', $siteLangId); ?></label>
                <?php echo $frmSearch->getFieldHtml('shop_supplier_display_status'); ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label class="label"><?php echo Labels::getLabel('FRM_DATE_FROM', $siteLangId); ?></label>
                <?php echo $frmSearch->getFieldHtml('date_from'); ?>

            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="label"><?php echo Labels::getLabel('FRM_DATE_TO', $siteLangId); ?></label>
                <?php echo $frmSearch->getFieldHtml('date_to'); ?>

            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="label"></label>
                <?php echo $frmSearch->getFieldHtml('btn_clear'); ?>
            </div>
        </div>
    </div>
    <div class="separator separator-dashed my-2"></div>
</div>
</form>
<?php echo $frmSearch->getExternalJS(); ?>