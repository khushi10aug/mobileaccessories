<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<section class="section" data-section="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="message message-failed cms cms-center">
                    <svg class="svg" width="80" height="80">
                        <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND; ?>images/retina/sprite.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#payment-failed">
                        </use>
                    </svg>
                    <header class="section-head  section-head-center mt-2">
                        <div class="section-heading">
                            <h2><?php echo Labels::getLabel('LBL_Payment_Failed', $siteLangId); ?></h2>
                        </div>
                    </header>
                    <p>
                        <?php echo CommonHelper::renderHtml($textMessage); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>