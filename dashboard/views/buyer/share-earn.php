<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$sharingFrm->addFormTagAttribute('class', 'form form--inline form--fly');
$sharingFrm->addFormTagAttribute('onsubmit', 'sendMailShareEarn(this);return false;');
$sharingFrm->developerTags['colClassPrefix'] = 'col-xs-12 col-md-';
$sharingFrm->developerTags['fld_default_col'] = 12;
$submitFld = $sharingFrm->getField('btn_submit');
$submitFld->setFieldTagAttribute('class', 'btn btn-brand btn-block');
$submitFld->developerTags['col'] = 2;

$email = $sharingFrm->getField('email');
$email->setFieldTagAttribute('class', 'emailAddressJs');
$email->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_EMAIL_ADDRESS', $siteLangId));

$this->includeTemplate('_partial/dashboardNavigation.php'); ?>

<div class="content-wrapper content-space">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <?php
            $data = [
                'headingLabel' => Labels::getLabel('LBL_Share_and_Earn', $siteLangId),
                'siteLangId' => $siteLangId,
            ];
            $this->includeTemplate('_partial/header/content-header.php', $data); ?>
            <div class="content-body">
                <div class="card">
                    <div class="card-body">
                        <div class="invite-box">
                            <div class="share-earn">
                                <img src="<?php echo CONF_WEBROOT_URL; ?>images/share-earn.png<?php echo UrlHelper::getCacheTimestamp($siteLangId) ?>" alt="">
                                <h2>
                                    <?php echo Labels::getLabel('LBL_INVITE_YOUR_FRIENDS', $siteLangId); ?>
                                </h2>
                                <p>
                                    <?php echo CommonHelper::replaceStringData(Labels::getLabel('LBL_INVITE_YOUR_FRIENDS_TO_JOIN_{WEBSITE-NAME}_AND_EARN_ONCE_THEY_SIGNUP.', $siteLangId),["{WEBSITE-NAME}" => FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId)]); ?>
                                </p>
                            </div>
                            <div class="invite-by-email">
                                <?php echo $sharingFrm->getFormTag(); ?>
                                <div class="form-group">
                                    <?php echo $sharingFrm->getFieldHTML('email'); ?>
                                    <button type="submit" disabled="disabled" class="btn-fly submitBtnJs">
                                        <svg class="svg">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#submitfly">
                                            </use>
                                        </svg>
                                    </button>
                                </div>
                                </form>
                                <?php echo $sharingFrm->getExternalJS(); ?>
                            </div>
                            <ul class="social-invites ">
                                <li>
                                    <a href="javascript:void(0);" title="<?php echo Labels::getLabel('MSG_COPY_TO_CLIPBOARD', $siteLangId); ?>" onclick="copyText(this, false)" data-url="<?php echo $referralTrackingUrl; ?>" data-bs-toggle="tooltip" data-placement="top" class="btn">
                                        <span class="icon">
                                            <i class="svg--icon">
                                                <svg class="svg">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icon_link">
                                                    </use>
                                                </svg>
                                            </i>
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" title="<?php echo Labels::getLabel('MSG_SHARE_ON_FACEBOOK', $siteLangId); ?>" class="share-network-facebook st-custom-button" data-network="facebook" data-url="<?php echo $referralTrackingUrl; ?>">
                                        <span class="icon">
                                            <i class="svg--icon">
                                                <svg class="svg">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-facebook" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-facebook">
                                                    </use>
                                                </svg>
                                            </i>
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" title="<?php echo Labels::getLabel('MSG_SHARE_ON_LINKEDIN', $siteLangId); ?>" class="share-network-linkedin st-custom-button" data-network="linkedin" data-url="<?php echo $referralTrackingUrl; ?>">
                                        <span class="icon">
                                            <i class="svg--icon">
                                                <svg class="svg">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-linkedin" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-linkedin">
                                                    </use>
                                                </svg>
                                            </i>
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" title="<?php echo Labels::getLabel('MSG_SHARE_ON_REDDIT', $siteLangId); ?>" class="share-network-reddit st-custom-button" data-network="reddit" data-url="<?php echo $referralTrackingUrl; ?>">
                                        <span class="icon">
                                            <i class="svg--icon">
                                                <svg class="svg">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-reddit" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-reddit">
                                                    </use>
                                                </svg>
                                            </i>
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" title="<?php echo Labels::getLabel('MSG_SHARE_ON_SKYPE', $siteLangId); ?>" class="share-network-skype st-custom-button" data-network="skype" data-url="<?php echo $referralTrackingUrl; ?>">
                                        <span class="icon">
                                            <i class="svg--icon">
                                                <svg class="svg">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-skype" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-skype">
                                                    </use>
                                                </svg>
                                            </i>
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" title="<?php echo Labels::getLabel('MSG_SHARE_ON_TELEGRAM', $siteLangId); ?>" class="share-network-telegram st-custom-button" data-network="telegram" data-url="<?php echo $referralTrackingUrl; ?>">
                                        <span class="icon">
                                            <i class="svg--icon">
                                                <svg class="svg">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-telegram" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-telegram">
                                                    </use>
                                                </svg>
                                            </i>
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" title="<?php echo Labels::getLabel('MSG_SHARE_ON_TWITTER', $siteLangId); ?>" class="share-network-twitter st-custom-button" data-network="twitter" data-url="<?php echo $referralTrackingUrl; ?>">
                                        <span class="icon">
                                            <i class="svg--icon">
                                                <svg class="svg">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-twitter" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-twitter">
                                                    </use>
                                                </svg>
                                            </i>
                                        </span>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" title="<?php echo Labels::getLabel('MSG_SHARE_ON_WHATSAPP', $siteLangId); ?>" class="share-network-whatsapp st-custom-button" data-network="whatsapp" data-url="<?php echo $referralTrackingUrl; ?>">
                                        <span class="icon">
                                            <i class="svg--icon">
                                                <svg class="svg">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-whatsapp" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#share-whatsapp">
                                                    </use>
                                                </svg>
                                            </i>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>