<?php

class RazorpaySettingsController extends PaymentMethodSettingsController
{
    public static function form($langId)
    {
        $frm = new Form('frmPaymentMethods');
        $frm->addRequiredField(Labels::getLabel('FRM_KEY_ID', $langId), 'merchant_key_id');
        $frm->addRequiredField(Labels::getLabel('FRM_KEY_SECRET', $langId), 'merchant_key_secret');
        $fld = $frm->addTextBox(Labels::getLabel('FRM_WEBHOOK_SECRET', $langId), 'webhook_secret');
        $fld->requirements()->setRequired(false);
        $webhookUrl = UrlHelper::generateFullUrl('RazorpayPay', 'webhook', [], CONF_WEBROOT_FRONT_URL);
        $fld->htmlAfterField = '<small class="form-text text-muted">'
            . 'Paste the Webhook Secret from Razorpay Dashboard. '
            . 'Webhook URL: <code>' . htmlspecialchars($webhookUrl) . '</code> '
            . '(or existing <code>/razorpay-pay/callback</code>). '
            . 'Enable events: payment.captured, payment.authorized.'
            . '</small>';
        return $frm;
    }
}
