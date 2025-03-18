<?php defined('SYSTEM_INIT') or die('Invalid Usage');
$frm->setFormTagAttribute('action', UrlHelper::generateUrl('TransferBankPay', 'send', array($orderInfo['id'])));
$frm->setFormTagAttribute('class', 'form form--payment transferBankPaymentFormJs');
$frm->setFormTagAttribute('style', 'display:none;');
$frm->developerTags['colClassPrefix'] = 'col-lg-12 col-md-12 col-sm-12 col-xs-';
$frm->developerTags['fld_default_col'] = 12;
?>
<section class="payment-section paymentSectionJs">
    <div class="payable-amount">
        <div class="payable-amount-head">
            <div class="payable-amount-logo">
                <?php $this->includeTemplate('_partial/paymentPageLogo.php', array('siteLangId' => $siteLangId)); ?>
            </div>
            <div class="payable-amount-total">
                <p> <span class="label"> <?php echo Labels::getLabel('LBL_Total_Payable', $siteLangId); ?>:</span>
                    <span class="value"> <?php echo CommonHelper::displayMoneyFormat($paymentAmount) ?> </span>
                </p>
                <p> <span class="label"> <?php echo Labels::getLabel('LBL_Order_Invoice', $siteLangId); ?>: </span>
                    <span class="value"><?php echo $orderInfo["invoice"]; ?></span>
                </p>
            </div>
        </div>
        <div class="payable-amount-body from-payment">
            <ul class="transfer-payment-detail mt-4">
                <li>
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bussiness-name" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bussiness-name"></use>
                        </svg>
                    </i>
                    <div class="lable">
                        <h6><?php echo Labels::getLabel('LBL_BUSSINESS_NAME', $siteLangId); ?></h6>
                        <?php echo $settings['business_name']; ?>
                    </div>

                </li>
                <li>
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bank-name" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bank-name"></use>
                        </svg>
                    </i>
                    <div class="lable">
                        <h6><?php echo Labels::getLabel('LBL_BANK_NAME', $siteLangId); ?></h6>
                        <?php echo $settings['bank_name']; ?>
                    </div>

                </li>
                <li>
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bank-branch" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bank-branch"></use>
                        </svg>
                    </i>
                    <div class="lable">
                        <h6><?php echo Labels::getLabel('LBL_BANK_BRANCH', $siteLangId); ?></h6>
                        <?php echo $settings['bank_branch']; ?>
                    </div>

                </li>
                <li>
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#account" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#account"></use>
                        </svg>
                    </i>
                    <div class="lable">
                        <h6><?php echo Labels::getLabel('LBL_ACCOUNT_#', $siteLangId); ?></h6>
                        <?php echo $settings['account_number']; ?>
                    </div>

                </li>
                <li>
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#ifsc" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#ifsc"></use>
                        </svg>
                    </i>
                    <div class="lable">
                        <h6><?php echo Labels::getLabel('LBL_IFSC_/_MICR', $siteLangId); ?></h6>
                        <?php echo $settings['ifsc']; ?>
                    </div>

                </li>
                <li>
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#routing" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#routing"></use>
                        </svg>
                    </i>
                    <div class="lable">
                        <h6><?php echo Labels::getLabel('LBL_ROUTING_#', $siteLangId); ?></h6>
                        <?php echo $settings['routing']; ?>
                    </div>

                </li>
                <li class="notes">
                    <i class="icn">
                        <svg class="svg">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bank-notes" href="<?php echo CONF_WEBROOT_URL; ?>images/retina/bank.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#bank-notes"></use>
                        </svg>
                    </i>
                    <div class="lable">
                        <h6><?php echo Labels::getLabel('LBL_OTHER_NOTES', $siteLangId); ?></h6>
                        <?php echo $settings['bank_notes']; ?>
                    </div>
                </li>
            </ul>
            <div class="my-3">
                <?php echo HtmlHelper::configureSwitchForCheckboxStatic(
                    'transfer_bank_pay_lator',
                    applicationConstants::ACTIVE,
                    'checked class="transferBankPayLaterJs"',
                    Labels::getLabel('LBL_PAY_LATER', $siteLangId)
                ); ?>
                <span class="form-text text-muted">
                    <?php echo Labels::getLabel('LBL_LEAVE_IT_ENABLED_IF_YOU_WANT_TO_PAY_LATER.'); ?>
                </span>
            </div>
            <?php
            if (!isset($error)) :
                $frm->setFormTagAttribute('onsubmit', 'confirmPayment(this); return(false);');
            ?>
                <?php echo $frm->getFormTag(); ?>
                <div class="payable-form-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="caption-wraper">
                                    <label class="form-label"><?php echo $frm->getField('opayment_method')->getCaption(); ?></label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $frm->getFieldHtml('opayment_method'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="caption-wraper">
                                    <label class="form-label"><?php echo $frm->getField('opayment_gateway_txn_id')->getCaption(); ?></label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $frm->getFieldHtml('opayment_gateway_txn_id'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="caption-wraper">
                                    <label class="form-label"><?php echo $frm->getField('opayment_amount')->getCaption(); ?></label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $frm->getFieldHtml('opayment_amount'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="caption-wraper">
                                    <label class="form-label"><?php echo $frm->getField('opayment_comments')->getCaption(); ?></label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $frm->getFieldHtml('opayment_comments'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php echo $frm->getFieldHtml('opayment_order_id'); ?>
                    <div id="ajax_message"></div>
                </div>
                <div class="payable-form-footer">
                    <div class="row">
                        <div class="col-6">
                            <?php if (FatUtility::isAjaxCall()) { ?>
                                <a href="javascript:void(0);" onclick="loadPaymentSummary()" class="btn btn-outline-brand">
                                    <?php echo Labels::getLabel('LBL_Cancel', $siteLangId); ?>
                                </a>
                            <?php } else { ?>
                                <a href="<?php echo $cancelBtnUrl; ?>" class="btn btn-outline-gray btn-block"><?php echo Labels::getLabel('LBL_Cancel', $siteLangId); ?></a>
                            <?php } ?>
                        </div>
                        <div class="col-6">
                            <?php
                            $btn = $frm->getField('btn_submit');
                            $btn->addFieldTagAttribute('class', 'btn btn-brand btn-block');
                            $btn->addFieldTagAttribute('data-processing-text', Labels::getLabel('LBL_PLEASE_WAIT..', $siteLangId));
                            echo $frm->getFieldHtml('btn_submit');
                            ?>
                        </div>
                    </div>
                </div>
                <?php echo '</form>' . $frm->getExternalJs(); ?>
                <?php
                /* When no form display. */
                $btn->addFieldTagAttribute('class', 'btn btn-brand transferBankSubmitBtnJs');
                $btn->addFieldTagAttribute('onclick', 'confirmPayment($("#' . $frm->getFormTagAttribute('id') . '")[0]); return false;');
                echo $frm->getFieldHtml('btn_submit');
                ?>
            <?php else : ?>
                <div class="alert alert-danger"><?php echo $error ?></div>
            <?php endif; ?>
            <?php if (CommonHelper::getCurrencyId() != FatApp::getConfig('CONF_CURRENCY', FatUtility::VAR_INT, 1)) { ?>
                <p class="form-text text-muted mt-4"><?php echo CommonHelper::currencyDisclaimer($siteLangId, $paymentAmount); ?> </p>
            <?php } ?>
        </div>
    </div>
</section>
<?php include(CONF_THEME_PATH . '_partial/footer-part/fonts.php'); ?>
<script>
    var confirmPayment = function(frm) {
        var me = $(frm);
        if (me.data('requestRunning')) {
            return;
        }
        if (!me.validate())
            return;
        $("input[type='submit']").val(langLbl.processing);
        var data = fcom.frmData(frm);
        var action = me.attr('action');
        $('.paymentSectionJs').prepend(fcom.getLoader());
        fcom.displayProcessing();
        fcom.ajax(action, data, function(t) {
            fcom.removeLoader();
            fcom.closeProcessing();
            try {
                var json = $.parseJSON(t);
                var el = $('#ajax_message');
                if (json['error']) {
                    el.html('<div class="alert alert-danger">' + json['error'] + '<div>');
                }
                if (json['redirect']) {
                    $(location).attr("href", json['redirect']);
                }
            } catch (exc) {
                console.log(t);
            }
        });
    };

    $(document).on('click', '.transferBankPayLaterJs', function() {
        if ($(this).is(':checked')) {
            $('.transferBankPaymentFormJs').slideUp();
            $('.transferBankSubmitBtnJs').fadeIn();
        } else {
            $('.transferBankSubmitBtnJs').hide();
            $('.transferBankPaymentFormJs').slideDown();
        }
    });
</script>