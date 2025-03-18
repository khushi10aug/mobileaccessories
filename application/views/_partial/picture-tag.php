<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$webpImageUrl = $webpImageUrl ?? [];
$jpgImageUrl = $jpgImageUrl ?? [];
$imgSrcSet = $imgSrcSet ?? [];
$lazyLoading = $lazyLoading ?? true;

$imageUrl = isset($imageUrl) ? $imageUrl : '';
$ratio = isset($ratio) ? $ratio : '';

$alt = isset($alt) ? htmlspecialchars_decode($alt) : FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId, FatUtility::VAR_STRING, '');
$title = isset($title) ? htmlspecialchars_decode($title) : $alt;
?>
<picture>
    <?php
    $emptyWebpUrlCount = 0;
    $webpItmesCount =  count($webpImageUrl);
    foreach ($webpImageUrl as $key => $url) {
        if (empty($url)) {
            $emptyWebpUrlCount++;
            continue;
        }
        $key = strtoupper($key);
        $mediaArr = ImageDimension::getPictureTagMedia($key);

        $media = 'media="(' . $mediaArr['key'] . ':' . $mediaArr['value'] . 'px)"';
        if (1 < $emptyWebpUrlCount || $webpItmesCount == 1) {
            $media = '';
        }

    ?>
        <source srcset="<?php echo $url; ?>" type="image/webp" <?php echo $media; ?>>
    <?php } ?>
    <?php
    $emptyJpgUrlCount = 0;
    $jpgItmesCount =  count($jpgImageUrl);

    $srcSet = '';
    $sizes = '';
    foreach ($jpgImageUrl as $key => $url) {
        if (empty($url)) {
            $emptyJpgUrlCount++;
            continue;
        }
        $key = strtoupper($key);
        $mediaArr = ImageDimension::getPictureTagMedia($key);

        $media = 'media="(' . $mediaArr['key'] . ':' . $mediaArr['value'] . 'px)"';
        $sizes .= '(' . $mediaArr['key'] . ':' . $mediaArr['value'] . 'px),';
        $srcSet .= $url . ' ' . $mediaArr['value'] . 'w,';

        if (1 < $emptyJpgUrlCount  || $jpgItmesCount == 1) {
            $media = '';
            $srcSet = '';
            $sizes = '';
        }
    ?>

        <source srcset="<?php echo $url; ?>" type="image/jpeg" <?php echo $media; ?>>
    <?php } ?>
    <img <?php (true == $lazyLoading) ? "loading='lazy'" : ""; ?> <?php echo !empty($ratio) ? "data-ratio='" . $ratio . "'" : ""; ?> src="<?php echo empty($imageUrl) ? rtrim($jpgImageUrl[ImageDimension::VIEW_DESKTOP], ',') : rtrim($imageUrl, ','); ?>" alt="<?php echo $alt; ?>" title="<?php echo $title; ?>">
</picture>