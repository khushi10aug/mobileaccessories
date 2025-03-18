<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('data-onclear', 'attachmentForm(' . $primaryOfferId . ')');
$frm->setFormTagAttribute('class', 'form messages-chat-foot modalFormJs');
$frm->setFormTagAttribute('onsubmit', 'saveAttachment($("#' . $frm->getFormTagAttribute('id') . '")); return(false);');

$fld = $frm->getField('rom_message');
$fld->setFieldTagAttribute('class', 'chat-textarea chatTextareaJs');

$fld = $frm->getField('attachment_file');
$fld->setFieldTagAttribute('class', 'hidden-input attachmentFileInputJs');

if (0 < $onlyWithAttachments) {
    $formTitle = Labels::getLabel('LBL_ATTACHMENTS');
} else {
    $formTitle = Labels::getLabel('LBL_MESSAGES_&_ATTACHMENTS');
}

$fld = $frm->getField('rom_buyer_access');
if (null != $fld) {
    $fld->setFieldTagAttribute('class', 'hidden-input buyerAccessInputJs');
    $fld->changeCaption('');
}

require_once(CONF_THEME_PATH . '_partial/listing/form-head.php'); ?>
<div class="form-edit-body p-0 loaderContainerJs">
    <div class="messages-chat">
        <div class="messages-chat-head"></div>
        <div class="messages-chat-body messageChatBodyJs">
            <?php
            if (!empty($data)) {
                require 'attachment-rows.php';
            } else {
                $this->includeTemplate('_partial/no-record-found.php', ['msg' => Labels::getLabel('LBL_SORRY,_NO_RECORD_FOUND.'), 'includeSubHeading' => false]);
            } ?>
        </div>
        <?php echo $frm->getFormTag(); ?>
        <?php echo $frm->getFieldHtml('rom_primary_offer_id'); ?>
        <button class="btn-attachments btnAttachmentsJs" type="button">
            <?php echo $frm->getFieldHtml('attachment_file'); ?>
            <svg class="svg" width="20" height="20">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#attachments"></use>
            </svg>
        </button>
        <?php
        $fld = $frm->getField('rom_buyer_access');
        if (null != $fld) {
        ?>
            <button class="btn-access btnSccessJs active" type="button" data-bs-toggle="tooltip" title="<?php echo Labels::getLabel('FRM_BUYER_ACCESS'); ?>">
                <?php
                $fld = $frm->getFieldHtml('rom_buyer_access');
                $fld = str_replace("<label >", "", $fld);
                echo $fld = str_replace("</label>", "", $fld);
                ?>
                <svg class="svg" width="20" height="20">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#user-follow-line"></use>
                </svg>
            </button>
        <?php } ?>
        <?php echo $frm->getFieldHtml('rom_message'); ?>
        <button class="btn-send btnSubmitJs" type="submit" disabled>
            <svg class="svg" width="20" height="20">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#send"></use>
            </svg>
        </button>
        <?php echo '</form>' . $frm->getExternalJs(); ?>
    </div>
</div>
</div>