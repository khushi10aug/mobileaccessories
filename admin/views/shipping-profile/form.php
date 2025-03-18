<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupProfile(this); return(false);');

$proFld = $frm->getField('shipprofile_name');
$proFld->addFieldTagAttribute('placeholder', Labels::getLabel('FRM_PROFILE_NAME', $siteLangId));

$langFld = $frm->getField('lang_id');
$langFld->addFieldTagAttribute('onChange', "loadLangData()");
$langFld->addFieldTagAttribute('autocomplete', "off"); ?>

<main class="main mainJs">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8"> <?php $this->includeTemplate('_partial/header/header-breadcrumb.php', [], false); ?>
                <div class="card">
                    <?php echo $frm->getFormTag(); ?>
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Labels::getLabel('LBL_PROFILE_NAME', $siteLangId); ?></h3>
                        </div>
                        <div class="add-stock-column-head-action">
                            <div class="input-group">
                                <?php echo $frm->getFieldHtml('lang_id'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" id="profile-name-form">
                        <?php echo $this->includeTemplate('shipping-profile/profile-name-form.php', [
                            'frm' => $frm,
                            'siteDefaultLangId' => $siteDefaultLangId,
                            'siteLangId' => $siteLangId,
                            'languages' => $languages,
                            'langId' => $langId,
                        ], false, true); ?>
                    </div>
                    </form>
                    <?php echo $frm->getExternalJs(); ?>
                </div>
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Labels::getLabel('LBL_PRODUCTS', $siteLangId); ?></h3>
                        </div>
                        <div class="card-toolbar">
                            <a class="btn btn-icon btn-outline-brand btn-add" href="javascript:void(0);" onclick="profileProductForm(<?php echo $profile_id; ?>)" title="<?php echo Labels::getLabel("LBL_Edit", $siteLangId); ?>">
                                <svg class="svg btn-icon-start" width="18" height="18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                    </use>
                                </svg>
                                <span><?php echo Labels::getLabel('LBL_NEW', $siteLangId); ?></span>
                            </a>
                        </div>

                    </div>
                    <div class="card-body">
                        <?php if (empty($profileData) || ((isset($profileData['shipprofile_default'])))) { ?>
                            <div id="product-section--js"></div>
                        <?php } ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Labels::getLabel('LBL_Shipping_to', $siteLangId); ?></h3>
                        </div>
                        <div class="card-toolbar">
                            <a class="btn btn-icon btn-outline-brand btn-add" href="javascript:void(0);" onclick="zoneForm(<?php echo $profile_id; ?>, 0)" title="<?php echo Labels::getLabel("LBL_Edit", $siteLangId); ?>">
                                <svg class="svg btn-icon-start" width="18" height="18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite-actions.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#add">
                                    </use>
                                </svg>
                                <span><?php echo Labels::getLabel('LBL_NEW', $siteLangId); ?></span>
                            </a>

                        </div>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                        <div id="listing-zones"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>