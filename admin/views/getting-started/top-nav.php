<?php if (isset($tourStep)) { ?>
    <div class="onboarding-top">
        <div class="pagers">
            <button type="button" class="btn btn-icon btn-outline-brand gStartedBtnJs <?php echo (1 >= $tourStep) ? 'disabled' : ''; ?>" data-url="<?php echo SiteTourHelper::getPrevLink($tourStep); ?>">
                <svg class="svg btn-icon-start" width="16" height="16">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icn-prev">
                    </use>
                </svg>
                <span> <?php echo Labels::getLabel('LBL_PREV', $siteLangId); ?></span>
            </button>
            <button type="button" class="btn btn-icon btn-outline-brand gStartedBtnJs" data-url="<?php echo SiteTourHelper::getNextLink($tourStep); ?>">
                <span> <?php echo Labels::getLabel('LBL_NEXT', $siteLangId); ?> </span>
                <svg class="svg btn-icon-end" width="16" height="16">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.yokart.svg<?php echo AttachedFile::setTimeParam(RELEASE_DATE); ?>#icn-next">
                    </use>
                </svg>
            </button>
        </div>

    </div>
<?php } ?>
<script>
    $(document).on("click", ".gStartedBtnJs", function(e) {
        location.href = $(this).attr('data-url');
    });
</script>