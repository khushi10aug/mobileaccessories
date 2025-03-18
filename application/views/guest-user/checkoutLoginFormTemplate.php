<?php
$showSignUpLink = isset($showSignUpLink) ? $showSignUpLink : true;
$onSubmitFunctionName = isset($onSubmitFunctionName) ? $onSubmitFunctionName : 'defaultSetUpLogin';
?>
<section>
    <h3><?php echo Labels::getLabel('LBL_Login', $siteLangId); ?></h3>
    <div class="check-login-wrapper step__body">
        <div id="" class="tabz--checkout-login tabs--flat-js">
            <ul>
                <li class="is-active"><a href="#user-1"> <i class="icn"><svg class="svg">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#tick" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#tick"></use>
                            </svg></i><?php echo Labels::getLabel('LBL_Existing_User', $siteLangId); ?> </a></li>
                <li><a href="#user-2"> <i class="icn"><svg class="svg">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#tick" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#tick"></use>
                            </svg></i><?php echo Labels::getLabel('LBL_Guest_User', $siteLangId); ?> </a></li>
            </ul>
        </div>
        <div id="user-1" class="tabs-content tabs-content-js">
            <?php
            //$frm->setRequiredStarPosition(Form::FORM_REQUIRED_STAR_POSITION_NONE);
            $loginFrm->setFormTagAttribute('class', 'form form-checkout-login');
            $loginFrm->setFormTagAttribute('name', 'formLoginPage');
            $loginFrm->setFormTagAttribute('id', 'formLoginPage');
            $loginFrm->setValidatorJsObjectName('loginFormObj');

            $loginFrm->setFormTagAttribute('onsubmit', 'return ' . $onSubmitFunctionName . '(this, loginFormObj);');
            $loginFrm->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-12 col-xs-';
            $loginFrm->developerTags['fld_default_col'] = 12;
            $loginFrm->removeField($loginFrm->getField('remember_me'));
            $fldforgot = $loginFrm->getField('forgot');
            $fldforgot->value = '<a href="' . UrlHelper::generateUrl('GuestUser', 'forgotPasswordForm') . '"
			class="forgot">' . Labels::getLabel('LBL_Forgot_Password?', $siteLangId) . '</a>';
            $fldSubmit = $loginFrm->getField('btn_submit');
            $fldSubmit->addFieldTagAttribute("class", "btn-block");
            echo $loginFrm->getFormHtml();
            ?>
        </div>
        <div id="user-2" class="tabs-content tabs-content-js">
            <?php
            $guestLoginFrm->setFormTagAttribute('class', 'form form-checkout-login');
            $guestLoginFrm->setFormTagAttribute('name', 'frmGuestLogin');
            $guestLoginFrm->setFormTagAttribute('id', 'frmGuestLogin');
            $guestLoginFrm->setValidatorJsObjectName('guestLoginFormObj');

            $guestLoginFrm->setFormTagAttribute('onsubmit', 'return guestUserLogin(this, guestLoginFormObj);');
            $guestLoginFrm->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-12 col-xs-';
            $guestLoginFrm->developerTags['fld_default_col'] = 12;

            $fldSpace = $guestLoginFrm->getField('space');
            $fldSpace->value = '<a href="#" class="forgot">&nbsp;</a>';

            $fldSubmit = $guestLoginFrm->getField('btn_submit');
            $fldSubmit->addFieldTagAttribute("class", "btn-block");
            echo $guestLoginFrm->getFormHtml(); ?>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-12 ">
                <div class=""><span class="or"><?php echo Labels::getLabel('LBL_Or', $siteLangId); ?></span></div>

                <ul class="buttons-list buttons-list-checkout">
                    <?php
                    if (!empty($socialLoginApis) && 0 < count($socialLoginApis)) {
                        foreach ($socialLoginApis as $plugin) { ?>
                            <li class="buttons-list-item">
                                <a class="buttons-list-link" href="<?php echo UrlHelper::generateUrl($plugin['plugin_code']); ?>">
                                    <span class="buttons-list-wrap">
                                        <span class="buttons-list-icon btn-social btn-<?php echo $plugin['plugin_code']; ?>">
                                            <img class="svg" width="30" height="30" src="<?php echo CONF_WEBROOT_URL; ?>images/retina/social-icons/<?php echo $plugin['plugin_code']; ?>.svg">
                                        </span>
                                        <?php echo  Labels::getLabel('LBL_Sign_in_with_Facebook'); ?>
                                    </span>
                                </a>
                            </li>
                    <?php }
                    } ?>
                </ul>

            </div>
        </div>
        <div class="gap"></div>
        <div class="term">
            <?php if ($showSignUpLink) { ?><p class="text--dark"> <a href="" class="text text--uppercase"></a></p><?php } ?>
            <h6><?php echo sprintf(Labels::getLabel('LBL_New_to', $siteLangId), FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId)); ?>? <a href="<?php echo UrlHelper::generateUrl('GuestUser', 'RegistrationForm'); ?>" class="link"><?php echo Labels::getLabel('LBL_Sign_Up', $siteLangId); ?></a></h6>
            <!-- <p>If this is your first time shopping with us, please enter an email address to use as your Newegg ID and create a password for your account. Your Newegg account allows you to conveniently place orders, create wishlists, check the status of your recent orders and much more.</p> -->
        </div>
    </div>
</section>

<script>
    /*Tabs*/
    $(function() {
        $(".tabs-content-js").hide();
        $(".tabs--flat-js li:first").addClass("is-active").show();
        $(".tabs-content-js:first").show();
        $(".tabs--flat-js li").on('click', function() {
            $(".tabs--flat-js li").removeClass("is-active");
            $(this).addClass("is-active");
            $(".tabs-content-js").hide();
            var activeTab = $(this).find("a").attr("href");
            $(activeTab).fadeIn();
            return false;
            setSlider();
        });
    });
</script>