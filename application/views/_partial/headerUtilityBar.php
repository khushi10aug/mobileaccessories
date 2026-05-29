<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

// Ensure $siteLangId is available
if (!isset($siteLangId)) {
    $siteLangId = CommonHelper::getLangId();
}

// Utility links for top header bar (like TVCMALL)
$contactUrl = UrlHelper::generateUrl('custom', 'contactUs');
$faqUrl = UrlHelper::generateUrl('custom', 'faq');
$aboutUrl = UrlHelper::generateUrl('cms', 'view');
$sellerUrl = UrlHelper::generateUrl('supplier', 'form');

$dialCode = FatApp::getConfig('CONF_SITE_PHONE_DCODE', FatUtility::VAR_STRING, '');
$sitePhone = FatApp::getConfig('CONF_SITE_PHONE', FatUtility::VAR_INT, '');
$contactEmail = FatApp::getConfig('CONF_CONTACT_EMAIL', FatUtility::VAR_STRING, '');
?>

<div class="header-utility">
    <div class="container">
        <div class="header-utility__inner">
            <ul class="header-utility__links">
                <li><a class="header-utility__link" href="<?php echo $contactUrl; ?>"><?php echo Labels::getLabel('LBL_Contact_Us', $siteLangId); ?></a></li>
                <li><a class="header-utility__link" href="<?php echo $faqUrl; ?>"><?php echo Labels::getLabel('LBL_FAQs', $siteLangId); ?></a></li>
                <li><a class="header-utility__link" href="<?php echo $sellerUrl; ?>"><?php echo Labels::getLabel('LBL_Become_A_Seller', $siteLangId); ?></a></li>
                <?php if (FatApp::getConfig('CONF_BLOG_MODULE', FatUtility::VAR_INT, 0)) { ?>
                <li><a class="header-utility__link" href="<?php echo UrlHelper::generateUrl('blog'); ?>"><?php echo Labels::getLabel('LBL_Blog', $siteLangId); ?></a></li>
                <?php } ?>
            </ul>
            <div class="header-utility__contact">
                <?php if ($sitePhone) { ?>
                    <span><?php echo ValidateElement::formatDialCode($dialCode) . $sitePhone; ?></span>
                <?php } ?>
                <?php if ($contactEmail) { ?>
                    <a href="mailto:<?php echo $contactEmail; ?>"><?php echo $contactEmail; ?></a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
