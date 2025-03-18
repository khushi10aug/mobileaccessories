<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$contactFrm->setFormTagAttribute('class', 'form form--normal');
$contactFrm->setFormTagAttribute('action', UrlHelper::generateUrl('Custom', 'contactSubmit'));
$contactFrm->developerTags['colClassPrefix'] = 'col-md-';
$contactFrm->developerTags['fld_default_col'] = 6;
$fld = $contactFrm->getField('phone');
$fld->developerTags['col'] = 12;
$fld = $contactFrm->getField('message');
$fld->developerTags['col'] = 12;

$fld = $contactFrm->getField('htmlNote');
if (null != $fld) {
    $fld->developerTags['col'] = 12;
}

$fld = $contactFrm->getField('btn_submit');
$fld->addFieldTagAttribute('class', 'btn btn-brand btn-wide');
$fld->developerTags['col'] = 12;

?>
<script>
    ykevents.contactUs();
</script>
<div id="body" class="body">
    <?php $this->includeTemplate('_partial/page-head-section.php', ['headLabel' => Labels::getLabel('LBL_GET_IN_TOUCH', $siteLangId), 'subHeadLabel' => Labels::getLabel('LBL_GET_IN_TOUCH_TXT', $siteLangId)]); ?>
    <section class="section" data-section="section">
        <div class="container">
            <div class="row justify-content-center mt-3">

                <div class="col-xl-9">
                    <div class="row">
                        <div class="col-md-7">
                            <?php echo $contactFrm->getFormTag(); ?>
                            <?php
                            if (null != $contactFrm->getField('g-recaptcha-response')) {
                                echo $contactFrm->getFieldHTML('g-recaptcha-response');
                            }
                            ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <?php $fld = $contactFrm->getField('name');
                                            echo $fld->getCaption();
                                            ?>
                                            <span class="spn_must_field">*</span>
                                        </label> <?php echo $contactFrm->getFieldHtml('name'); ?>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <?php $fld = $contactFrm->getField('email');
                                            echo $fld->getCaption();
                                            ?>
                                            <span class="spn_must_field">*</span>
                                        </label>
                                        <?php echo $contactFrm->getFieldHtml('email'); ?>

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">

                                        <label class="form-label">
                                            <?php $fld = $contactFrm->getField('phone');
                                            echo $fld->getCaption();
                                            ?>
                                            <span class="spn_must_field">*</span>
                                        </label>


                                        <?php echo $contactFrm->getFieldHtml('phone'); ?>

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <?php $fld = $contactFrm->getField('message');
                                            echo $fld->getCaption();
                                            ?>
                                            <span class="spn_must_field">*</span>
                                        </label>
                                        <?php echo $contactFrm->getFieldHtml('message'); ?>

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="checkbox">
                                            <?php
                                            $fld = $contactFrm->getFieldHTML('agree');
                                            $fld = str_replace("<label >", "", $fld);
                                            $fld = str_replace("</label>", "", $fld);
                                            echo $fld;
                                            ?>

                                            <?php
                                            $arr = [
                                                "{terms-conditions}" => "<a href='" . $termsAndConditionsLinkHref . "'>" . Labels::getLabel('LBL_Terms_Conditions', $siteLangId) . "</a>",
                                                "{privacy-policy}" => "<a href='" . $privacyPolicyLinkHref . "'>" . Labels::getLabel('LBL_Privacy_Policy', $siteLangId) . "</a>"
                                            ];
                                            echo strtr(Labels::getLabel('LBL_I_agree_to_the_{terms-conditions}_and_{privacy-policy}', $siteLangId), $arr);
                                            ?>
                                        </label>

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <?php echo $contactFrm->getFieldHtml('btn_submit'); ?>
                                        <?php echo $contactFrm->getFieldHtml('fatpostsectkn'); ?>
                                    </div>
                                </div>
                            </div>
                            </form>
                            <?php echo $contactFrm->getExternalJs(); ?>
                        </div>
                        <div class="col-md-4 offset-lg-1">
                            <div class="contact-address">
                                <?php
                                ?>
                                <div class="contact-address-item">
                                    <h6><?php echo Labels::getLabel('LBL_GENERAL_INQUIRY', $siteLangId); ?>
                                    </h6>
                                    <ul class="list-contact">
                                        <?php
                                        $phone = FatApp::getConfig('CONF_SITE_PHONE', FatUtility::VAR_INT, '');
                                        if (!empty($phone)) {
                                            ?>
                                            <li>
                                                <span class="icon">
                                                    <svg class="svg" width="18" height="18">
                                                        <use
                                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#phones">
                                                        </use>
                                                    </svg>
                                                </span>
                                                <span class="label">
                                                    <span class="default-ltr"><?php $dialCode = FatApp::getConfig('CONF_SITE_PHONE_DCODE', FatUtility::VAR_STRING, '');
                                                        echo ValidateElement::formatDialCode($dialCode) . FatApp::getConfig('CONF_SITE_PHONE', FatUtility::VAR_INT, ''); ?></span>
                                                </span>
                                            </li>
                                        <?php } ?>
                                        <?php
                                        $fax = FatApp::getConfig('CONF_SITE_FAX', FatUtility::VAR_INT, '');
                                        if (!empty($fax)) {
                                            ?>
                                            <li>
                                                <span class="icon"><svg class="svg" width="18" height="18">
                                                        <use
                                                            xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#fax">
                                                        </use>
                                                    </svg>
                                                </span>
                                                <span class="label">
                                                    <span class="default-ltr"><?php $dialCode = FatApp::getConfig('CONF_SITE_FAX_DCODE', FatUtility::VAR_STRING, '');
                                                        echo ValidateElement::formatDialCode($dialCode) . FatApp::getConfig('CONF_SITE_FAX', FatUtility::VAR_STRING, ''); ?></span>
                                                </span>
                                            </li>
                                        <?php } ?>
                                        <li>
                                            <span class="icon"><svg class="svg" width="18" height="18">
                                                    <use
                                                        xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#customer-care">
                                                    </use>
                                                </svg>
                                            </span>
                                            <span
                                                class="label"><?php echo Labels::getLabel('LBL_24_A_DAY_7_DAYS_WEEK', $siteLangId); ?></span>
                                        </li>
                                    </ul>
                                </div>
                                <?php if (!empty(FatApp::getConfig('CONF_ADDRESS_' . $siteLangId, FatUtility::VAR_STRING, ''))) { ?>
                                    <div class="contact-address-item">
                                        <h6><?php echo Labels::getLabel('LBL_Address', $siteLangId); ?>
                                        </h6>
                                        <p>
                                            <?php echo nl2br(FatApp::getConfig('CONF_ADDRESS_' . $siteLangId, FatUtility::VAR_STRING, '')); ?>
                                        </p>
                                    </div>
                                <?php } ?>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php if (FatApp::getConfig('CONF_MAP_IFRAME_CODE', FatUtility::VAR_STRING, '') != '') { ?>
        <section class="g-map">
            <?php echo FatApp::getConfig('CONF_MAP_IFRAME_CODE', FatUtility::VAR_STRING); ?>
        </section>
    <?php } ?>
</div>
<?php
$siteKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
$secretKey = FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '');
if (!empty($siteKey) && !empty($secretKey)) { ?>
    <script defer
        src='https://www.google.com/recaptcha/api.js?onload=googleCaptcha&render=<?php echo $siteKey; ?>'></script>
<?php } ?>