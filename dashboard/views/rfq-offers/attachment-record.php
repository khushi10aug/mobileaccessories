<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if (isset($isBuyer) && true == $isBuyer) {
    $position = $row['rom_user_type'] == User::USER_TYPE_SELLER ? 'left' : 'right';
} else {
    $position = $row['rom_user_type'] == User::USER_TYPE_SELLER ? 'right' : 'left';
}
$msg = preg_replace("/\r\n|\r|\n/", '<br/>', $row['rom_message']);
$title = '';
if (1 > $row['rom_buyer_access']) {
    $title = Labels::getLabel('LBL_THE_CONTENTS_OF_THIS_MESSAGE_ARE_NOT_ACCESSIBLE_BY_THE_BUYER.');
}
?>
<?php if (!isset($displayDate) || $displayDate != FatDate::format($row['rom_added_on'])) { ?>
    <div class="date romDateJs<?php echo date('Ymd', strtotime($row['rom_added_on'])); ?>"><?php echo FatDate::format($row['rom_added_on']); ?></div>
<?php
    $displayDate = FatDate::format($row['rom_added_on']);
} ?>
<div class="messages-chat-item msg-<?php echo $position; ?> <?php echo 1 > $row['rom_buyer_access'] ? 'border-red' : ''; ?>" title="<?php echo $title; ?>">
    <div class="messages-chat-bubble">
        <?php if (100 < strlen((string)$msg)) {
            $subStr = preg_replace('/\s+?(\S+)?$/', '', substr($msg, 0, 100));
        ?>
            <span class="lessContent<?php echo $row['rom_id']; ?>Js">
                <?php echo $subStr; ?> &nbsp;
                <button class="dots showMoreJs" data-row-id="<?php echo $row['rom_id']; ?>">...</button>
            </span>
            <span class="moreContent<?php echo $row['rom_id']; ?>Js" style="display:none">
                <?php echo $msg; ?>
            </span>
        <?php } else {
            echo $msg;
        } ?>

        <?php if (0 < $row['afile_id']) { ?>
            <div class="attachments">
                <a class="attachments-item" target="blank" title="<?php echo Labels::getLabel('LBL_DOWNLOAD_FILE', $siteLangId); ?>" href="<?php echo UrlHelper::generateUrl(($isSeller ? 'Seller' : '') . 'RfqOffers', 'downloadAttachmentFile', array($row['rom_id'], $row['rom_primary_offer_id'])); ?>">
                    <span class="attachments-thumb">
                        <svg class="svg" width="14" height="14">
                            <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#download"></use>
                        </svg>
                    </span>
                    <span class="attachments-file-name"> <?php echo $row['afile_name']; ?></span>
                </a>
            </div>
        <?php } ?>

    </div>
    <div class="bubble-foot">
        <div class="time">
            <?php echo date('H:i', strtotime($row['rom_added_on'])); ?>
        </div>
    </div>
</div>