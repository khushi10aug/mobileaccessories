<?php defined('SYSTEM_INIT') or die('Invalid Usage'); ?>
<?php if (FatApp::getConfig('CONF_ENABLE_NEWSLETTER_SUBSCRIPTION', FatUtility::VAR_INT, 0)) {



    $class = (isset($blogPage)) ? 'form form-subscribe' : 'newsletter-form';
    $frm->setFormTagAttribute('class', $class);
    if (isset($formId) && $formId != '') {
        $frm->setFormTagAttribute('id', $formId);
    }

    $frm->setFormTagAttribute('onSubmit', 'setUpNewsLetter(this); return false;');
    $emailFld = $frm->getField('email');
    $emailFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_Email', $siteLangId));
    $emailFld->setFieldTagAttribute('class', 'newsletter-form-input');
    //$emailFld->setFieldTagAttribute('class', "no--focus"); 
    ?>



<div class="container">
    <div class="newsletter">
        <hgroup class="newsletter-head">
            <h3>
                <svg class="svg" width="32" height="32">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#go">
                    </use>
                </svg>
                <?php echo Labels::getLabel('LBL_Sign_up_for_newsletter'); ?>
            </h3>
            <!-- <h6><?php echo Labels::getLabel('LBL_NEWSLETTER_FORM_DESCRIPTION'); ?></h6> -->
        </hgroup>


        <?php if (FatApp::getConfig('CONF_NEWSLETTER_SYSTEM') == applicationConstants::NEWS_LETTER_SYSTEM_MAILCHIMP) {
                ?>
        <?php echo $frm->getFormTag(); ?>
        <?php echo $frm->getFieldHtml('email'); ?>

        <button class="newsletter-form-submit" type="submit">
            <!-- <svg class="svg" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-arrow-right"
                viewBox="0 0 16 16">
                <path fill-rule="evenodd"
                    d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" />
            </svg> -->
            <span class="txt"><?php echo Labels::getLabel('LBL_Subscribe'); ?></span>
        </button>
        <?php echo "</form>"; ?>


        <?php
            } elseif (FatApp::getConfig('CONF_NEWSLETTER_SYSTEM') == applicationConstants::NEWS_LETTER_SYSTEM_AWEBER) { ?>
        <span class='d-none aweber-js'><?php echo FatApp::getConfig('CONF_AWEBER_SIGNUP_CODE'); ?></span>
        <a href="javascript:void(0)" class="btn btn-brand" onclick="awebersignup();">
            <?php echo Labels::getLabel('LBL_NEWSLETTER_SIGNUP_AWEBER', $siteLangId); ?>
        </a>
        <?php }
            ?>
    </div>

</div>


<?php echo $frm->getExternalJS(); ?>
<?php


} else { ?>
<div class="gap"></div>
<?php } ?>







<script>
(function() {
    setUpNewsLetter = function(frm) {
        if (!$(frm).validate()) return;
        ykevents.newsLetterSubscription();
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('MyApp', 'setUpNewsLetter'), data, function(t) {
            fcom.removeLoader();
            if (t.status) {
                frm.reset();
            }
        });
    };
})();
</script>