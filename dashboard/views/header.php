<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

if (isset($includeEditor) && $includeEditor == true) {
    $extendEditorJs = 'true';
} else {
    $extendEditorJs = 'false';
}

array_walk($jsVariables, function (&$item1, $key) {
    $item1 = html_entity_decode($item1, ENT_QUOTES, 'UTF-8');
});
$commonHeadData = array(
    'siteLangId' => $siteLangId,
    'controllerName' => $controllerName,
    'action' => $action,
    'jsVariables' => $jsVariables,
    'extendEditorJs' => $extendEditorJs,
    'currencySymbolRight' => CommonHelper::getCurrencySymbolRight(),
    'canonicalUrl' => isset($canonicalUrl) ? $canonicalUrl : '',
);

if (isset($socialShareContent) && $socialShareContent != '') {
    $commonHeadData['socialShareContent'] = $socialShareContent;
}
if (isset($includeEditor) && $includeEditor == true) {
    $commonHeadData['includeEditor'] = $includeEditor;
}

if ($controllerName != 'GuestUser' && $controllerName != 'Error') {
    $_SESSION['referer_page_url'] = UrlHelper::getCurrUrl();
}

$htmlClass = '';
$actionName = FatApp::getAction();
if ($controllerName == 'Products' && $actionName == 'view') {
    $htmlClass = 'product-view';
}
$additionalAttributes = (CommonHelper::getLayoutDirection() == 'rtl') ? 'direction="rtl" style="direction: rtl;"' : '';
?>
<!DOCTYPE html>
<html lang="<?php echo strtolower(CommonHelper::getLangCode()); ?>" data-version="<?php echo CONF_WEB_APP_VERSION; ?>" data-theme="light" dir="<?php echo CommonHelper::getLayoutDirection(); ?>" prefix="og: http://ogp.me/ns#" <?php echo $additionalAttributes; ?> class="<?php echo $htmlClass; ?> <?php if (FatApp::getConfig('CONF_AUTO_RESTORE_ON', FatUtility::VAR_INT, 1) && CommonHelper::demoUrl()) {
                                                                                                                                                                                                                                                                                                            echo "sticky-demo-header";
                                                                                                                                                                                                                                                                                                        } ?>">

<head>
    <!-- Yo!Kart -->
    <meta charset="utf-8">
    <meta name="author" content="">
    <!-- Mobile Specific Metas ===================== -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (CommonHelper::demoUrl() && $controllerName != 'Home') { ?>
        <meta name="robots" content="noindex" />
    <?php } ?>
    <!-- F!YK -->
    <!-- favicon ================================================== -->
    <meta name="theme-color" content="#<?php echo FatApp::getConfig('CONF_THEME_COLOR', FatUtility::VAR_STRING, "#FF3A59"); ?>">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="<?php echo UrlHelper::generateFileUrl('Image', 'appleTouchIcon', array($siteLangId, '144-144'), CONF_WEBROOT_FRONTEND); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="msapplication-navbutton-color" content="#<?php echo FatApp::getConfig('CONF_THEME_COLOR', FatUtility::VAR_STRING, "#FF3A59"); ?>">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="msapplication-starturl" content="/">
    <?php
    $metaData = $this->writeMetaTags(true);
    $title = isset($metaData['meta_title']) ? $metaData['meta_title'] . ' ' : ' ';
    $title .= FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '');
    $description = (isset($metaData['meta_description'])) ? $metaData['meta_description'] : $title;
    $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_META_IMAGE, 0, 0, $siteLangId);
    $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
    if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
        $image = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']);
    } else {
        $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
        $image = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'metaImage', array($siteLangId), CONF_WEBROOT_FRONTEND) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
    }
    ?>
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php echo $title; ?>" />
    <meta property="og:site_name" content="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, ''); ?>" />
    <meta property="og:url" content="<?php echo UrlHelper::getCurrUrl(); ?>" />
    <meta property="og:description" content="<?php echo $description; ?> " />
    <meta property="og:image" content="<?php echo $image; ?>" />
    <?php if (!empty(FatApp::getConfig("CONF_TWITTER_USERNAME", FatUtility::VAR_STRING, ''))) { ?>
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:site" content="@<?php echo FatApp::getConfig("CONF_TWITTER_USERNAME", FatUtility::VAR_STRING, ''); ?>">
        <meta name="twitter:title" content="<?php echo $title; ?>">
        <meta name="twitter:description" content="<?php echo $description; ?>">
        <meta name="twitter:image" content="<?php echo $image; ?>">

    <?php }

    /* This is not included in common head, because, commonhead file not able to access the $this->Controller and $this->action[ */
    echo $this->writeMetaTags();
    /* ] */
    $this->includeTemplate('_partial/header/commonHeadMiddle.php', $commonHeadData, false);

    /* This is not included in common head, because, if we are adding any css/js from any controller then that file is not included[ */
    echo $this->getJsCssIncludeHtml(!CONF_DEVELOPMENT_MODE);
    /* ] */

    $this->includeTemplate('_partial/header/commonHeadBottom.php', $commonHeadData, false);

    if (!$isAppUser) {
        $controllerName = strtolower($controllerName);
        switch ($controllerName) {
            case 'subscriptioncheckout':
                $this->includeTemplate('_partial/header/checkout-header.php', array('siteLangId' => $siteLangId, 'headerData' => $headerData, 'controllerName' => $controllerName), false);
                break;
        }
    }

    if ($controllerName != 'SubscriptionCheckout') { ?>
        <div class="wrapper">
        <?php } ?>