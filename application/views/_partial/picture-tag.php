<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$webpImageUrl = $webpImageUrl ?? [];
$jpgImageUrl = $jpgImageUrl ?? [];
$lazyLoading = $lazyLoading ?? true;
$fetchPriority = $fetchPriority ?? '';
$dimensionType = (int) ($dimensionType ?? 0);
$dimensionSize = $dimensionSize ?? '';
$sizeTypes = $sizeTypes ?? [];
$sizes = ImageDimension::getPictureSizes($sizes ?? '');

$imageUrl = $imageUrl ?? '';
$ratio = $ratio ?? '';

$alt = isset($alt) ? htmlspecialchars_decode($alt) : FatApp::getConfig("CONF_WEBSITE_NAME_" . $siteLangId, FatUtility::VAR_STRING, '');
$title = isset($title) ? htmlspecialchars_decode($title) : $alt;

$imgWidth = '';
$imgHeight = '';
if (0 < $dimensionType && '' !== $dimensionSize) {
    $imgDims = ImageDimension::getData($dimensionType, $dimensionSize);
    $imgWidth = (int) ($imgDims[ImageDimension::WIDTH] ?? 0);
    $imgHeight = (int) ($imgDims[ImageDimension::HEIGHT] ?? 0);
}

$fallbackSrc = empty($imageUrl)
    ? rtrim($jpgImageUrl[ImageDimension::VIEW_DESKTOP] ?? reset($jpgImageUrl) ?: '', ',')
    : rtrim($imageUrl, ',');

$webpSrcset = (0 < $dimensionType)
    ? ImageDimension::buildPictureSrcset($webpImageUrl, $dimensionType, $sizeTypes)
    : '';
$jpgSrcset = (0 < $dimensionType)
    ? ImageDimension::buildPictureSrcset($jpgImageUrl, $dimensionType, $sizeTypes)
    : '';

$useSrcset = ('' !== $webpSrcset || '' !== $jpgSrcset);
$loadingAttr = $lazyLoading ? 'loading="lazy"' : 'loading="eager"';
$fetchPriorityAttr = ('' !== $fetchPriority) ? 'fetchpriority="' . htmlspecialchars($fetchPriority) . '"' : '';
$dimAttr = (0 < $imgWidth && 0 < $imgHeight) ? 'width="' . $imgWidth . '" height="' . $imgHeight . '"' : '';
?>
<picture>
    <?php if ($useSrcset && '' !== $webpSrcset) { ?>
        <source srcset="<?php echo $webpSrcset; ?>" type="image/webp" sizes="<?php echo $sizes; ?>">
    <?php } elseif (!$useSrcset) {
        $emptyWebpUrlCount = 0;
        $webpItemsCount = count($webpImageUrl);
        foreach ($webpImageUrl as $key => $url) {
            if (empty($url)) {
                $emptyWebpUrlCount++;
                continue;
            }
            $key = strtoupper($key);
            $mediaArr = ImageDimension::getPictureTagMedia($key);
            $media = 'media="(' . $mediaArr['key'] . ':' . $mediaArr['value'] . 'px)"';
            if (1 < $emptyWebpUrlCount || 1 === $webpItemsCount) {
                $media = '';
            }
            ?>
        <source srcset="<?php echo $url; ?>" type="image/webp" <?php echo $media; ?>>
        <?php }
    } ?>
    <?php if ($useSrcset && '' !== $jpgSrcset) { ?>
        <source srcset="<?php echo $jpgSrcset; ?>" type="image/jpeg" sizes="<?php echo $sizes; ?>">
    <?php } elseif (!$useSrcset) {
        $emptyJpgUrlCount = 0;
        $jpgItemsCount = count($jpgImageUrl);
        foreach ($jpgImageUrl as $key => $url) {
            if (empty($url)) {
                $emptyJpgUrlCount++;
                continue;
            }
            $key = strtoupper($key);
            $mediaArr = ImageDimension::getPictureTagMedia($key);
            $media = 'media="(' . $mediaArr['key'] . ':' . $mediaArr['value'] . 'px)"';
            if (1 < $emptyJpgUrlCount || 1 === $jpgItemsCount) {
                $media = '';
            }
            ?>
        <source srcset="<?php echo $url; ?>" type="image/jpeg" <?php echo $media; ?>>
        <?php }
    } ?>
    <img decoding="async" <?php echo $loadingAttr; ?> <?php echo $fetchPriorityAttr; ?> <?php echo $dimAttr; ?> <?php echo !empty($ratio) ? "data-ratio='" . $ratio . "'" : ""; ?> src="<?php echo $fallbackSrc; ?>" <?php echo ($useSrcset && '' !== $jpgSrcset) ? 'srcset="' . $jpgSrcset . '" sizes="' . $sizes . '"' : ''; ?> alt="<?php echo $alt; ?>" title="<?php echo $title; ?>">
</picture>
