<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
if ($trustBannerData) { ?>
    <section class="section bg-gray" data-section="section">
        <?php echo FatUtility::decodeHtmlEntities($trustBannerData['epage_content']); ?>
    </section>
<?php } ?>
