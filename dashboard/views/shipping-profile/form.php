<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$this->includeTemplate('_partial/dashboardNavigation.php');

$frm->setFormTagAttribute('onSubmit', 'setupProfile(this); return false;');
$frm->setFormTagAttribute('class', 'form ');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 6;

$submitBtnFld = $frm->getField('btn_submit');
$submitBtnFld->setFieldTagAttribute('class', 'btn btn-brand');
$submitBtnFld->setWrapperAttribute('class', 'col-lg-2');
$submitBtnFld->developerTags['col'] = 2;
$submitBtnFld->developerTags['noCaptionTag'] = true;
?>

<div class="content-wrapper content-space">
    <?php
    $data = [
        'headingLabel' => Labels::getLabel('LBL_Shipping_Profiles', $siteLangId),
        'siteLangId' => $siteLangId,
        'headingBackButton' => [
            'href' => UrlHelper::generateUrl('shippingProfile'),
            'onclick' => ''
        ]
    ];
    $this->includeTemplate('_partial/header/content-header.php', $data, false); ?>
    <div class="content-body">
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body pt-4 ps-4 pe-4 pb-4">
                        <?php echo $frm->getFormTag();
                        $pNameFld = $frm->getField('shipprofile_name[' . $siteDefaultLangId . ']');
                        $pNameFld->htmlAfterField = "<span class='form-text text-muted'>" . Labels::getLabel("LBL_Customers_won't_see_this", $siteLangId) . "</span>";
                        $pNameFld->addFieldTagAttribute('class', 'form-control');
                        ?>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-0">
                                    <?php echo $pNameFld->getHtml(); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <?php
                                    echo $frm->getFieldHtml('shipprofile_id');
                                    echo $frm->getFieldHtml('shipprofile_user_id');
                                    echo $frm->getFieldHtml('btn_submit');
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($languages) && count($languages) > 1) { ?>
                            <div class="accordion my-4" id="specification-accordion">
                                <h6 class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#collapse-1" aria-expanded="true" aria-controls="collapse-1">
                                    <?php echo Labels::getLabel('LBL_Language_Data', $siteLangId); ?>
                                </h6>
                                <div id="collapse-1" class="collapse collapse-js" aria-labelledby="headingOne" data-parent="#specification-accordion">
                                    <div class="p-4 mb-4 bg-gray rounded">
                                        <div class="row">
                                            <?php
                                            foreach ($languages as $langId => $data) {
                                                if ($siteDefaultLangId == $langId) {
                                                    continue;
                                                }
                                                $layout = Language::getLayoutDirection($langId);
                                            ?>
                                                <div class="col-md-6" dir="<?php echo $layout; ?>">
                                                    <div class="field-set">
                                                        <div class="caption-wraper">
                                                            <label class="field_label">
                                                                <?php $fld = $frm->getField('shipprofile_name[' . $langId . ']');
                                                                echo $fld->getCaption();
                                                                ?>
                                                            </label>
                                                        </div>
                                                        <div class="field-wraper">
                                                            <div class="field_cover">
                                                                <?php echo $fld->getHtml(); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        </form>
                        <?php echo $frm->getExternalJs(); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card" id="product-section--js"> </div>
            </div>
        </div>
        <?php if (empty($profileData) || ((isset($profileData['shipprofile_default'])))) { ?>
            <div class="row mb-4">
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h5 class="card-title"><?php echo Labels::getLabel('LBL_Shipping_to', $siteLangId); ?>
                                </h5>
                            </div>
                            <div class="action">
                                <?php if ($canEdit) { ?>
                                    <a class="btn btn-outline-gray btn-icon " href="javascript:void(0);" onclick="zoneForm(<?php echo $profile_id; ?>, 0)" title="<?php echo Labels::getLabel('LBL_ADD_ZONE', $siteLangId); ?>">
                                        <svg class="svg btn-icon-start" width="18" height="18">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                            </use>
                                        </svg><?php echo Labels::getLabel('LBL_ADD', $siteLangId); ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                            <div id="listing-zones"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>