<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="block-empty m-auto text-center">
    <div id="dvAlert">
        <div class="cards-message">
            <div class="cards-message-icon">
                <svg class="svg" width="20" height="20">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#exclamation-triangle">
                    </use>
                </svg>
            </div>
            <div class="cards-message-text">
                <?php
                if (isset($message)) {
                    echo $message;
                } else {
                    echo Labels::getLabel('LBL_No_record_found', $siteLangId);
                } ?>
            </div>
        </div>
    </div>
</div>