<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<script>
ykevents.viewContent();
</script>

<main id="main" class="main">
    <?php
    $wholesaleStatsShown = false;

    foreach ($collectionTemplates as $collection) {
        echo FatUtility::decodeHtmlEntities($collection['html']);

        // Stats bar directly under hero (services are in hero right column)
        if (!$wholesaleStatsShown && strpos($collection['html'], 'wholesale-hero-block') !== false) {
            $this->includeTemplate('_partial/wholesale/home-wholesale-hero.php');
            $wholesaleStatsShown = true;
        }
    }

    if (!$wholesaleStatsShown) {
        $this->includeTemplate('_partial/wholesale/home-wholesale-hero.php');
    }

    $this->includeTemplate('_partial/wholesale/home-featured-categories.php');
    $this->includeTemplate('_partial/footerTrustBanners.php');
    ?>
</main>