<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

if (!isset($siteLangId)) {
    $siteLangId = CommonHelper::getLangId();
}

$allCategories = ProductCategory::getProdCatParentChildWiseArr($siteLangId, 0, false, false);

if (!empty($allCategories)) {
    $count = 0;
    foreach ($allCategories as $category) {
        if ($count >= 16) {
            break;
        }
        $uploadedTime = AttachedFile::setTimeParam($category['prodcat_updated_on'] ?? '');
        $bannerUrl = UrlHelper::getCachedUrl(
            UrlHelper::generateFileUrl('Category', 'banner', [
                $category['prodcat_id'],
                $siteLangId,
                'DESKTOP',
                applicationConstants::SCREEN_DESKTOP
            ], CONF_WEBROOT_FRONT_URL) . $uploadedTime,
            CONF_IMG_CACHE_TIME,
            '.jpg'
        );
        $iconUrl = UrlHelper::getCachedUrl(
            UrlHelper::generateFileUrl('Category', 'icon', [
                $category['prodcat_id'],
                $siteLangId,
                'COLLECTION_PAGE'
            ], CONF_WEBROOT_FRONT_URL) . $uploadedTime,
            CONF_IMG_CACHE_TIME,
            '.jpg'
        );

        $count++;
    }
}
 ?>