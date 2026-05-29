<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<?php if ($footerData) { ?>
    <section class="py-4">
        <?php echo FatUtility::decodeHtmlEntities($footerData['epage_content']); ?>
    </section>
<?php } ?>
