<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); 

/**
 * Wholesale-themed Home Page Template
 * 
 * This is an enhanced version of home/index.php with wholesale sections integrated
 * To use this template, either:
 * 1. Rename home/index.php to home/index-original.php (backup)
 * 2. Rename this file to home/index.php
 * 
 * OR modify your existing home/index.php based on this structure
 */

// Apply wholesale theme
$this->includeTemplate('_partial/wholesale/apply-theme.php');
?>

<script>
ykevents.viewContent();
</script>

<main id="main" class="main">
    <?php 
    $heroShown = false;
    $categoriesShown = false;
    $wholesaleServicesShown = false;
    
    foreach ($collectionTemplates as $key => $collection) {
        $html = FatUtility::decodeHtmlEntities($collection['html']);
        
        // Output the collection
        echo $html;
        
        // After first hero slider, show featured categories
        if (!$categoriesShown && strpos($html, 'hero-slider') !== false) {
            $this->includeTemplate('_partial/wholesale/home-featured-categories.php');
            $categoriesShown = true;
            $heroShown = true;
        }
        
        // After categories or first product section, show wholesale services
        if (!$wholesaleServicesShown && $categoriesShown) {
            $this->includeTemplate('_partial/wholesale/home-wholesale-hero.php');
            $wholesaleServicesShown = true;
        }
    }
    
    // If we haven't shown them yet (no hero slider), show them now
    if (!$categoriesShown) {
        $this->includeTemplate('_partial/wholesale/home-featured-categories.php');
    }
    
    if (!$wholesaleServicesShown) {
        $this->includeTemplate('_partial/wholesale/home-wholesale-hero.php');
    }
    
    // Trust banners at bottom
    $this->includeTemplate('_partial/footerTrustBanners.php');
    ?>
</main>
